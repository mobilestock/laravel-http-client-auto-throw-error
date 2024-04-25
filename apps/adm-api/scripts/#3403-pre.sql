ALTER TABLE `entregas`
	ADD COLUMN `id_raio` INT(11) NULL DEFAULT NULL COMMENT 'Deve ter valor apenas quando for Ponto Móvel' AFTER `id_transporte`;

UPDATE transportes_cidades
SET transportes_cidades.id_usuario = 2
WHERE transportes_cidades.id_usuario IS NULL;

ALTER TABLE `transportes_cidades`
	CHANGE COLUMN `id_raio` `id` INT(11) NOT NULL AUTO_INCREMENT FIRST,
	CHANGE COLUMN `id_cidade` `id_cidade` INT(11) NOT NULL AFTER `id_colaborador`,
	CHANGE COLUMN `ativo` `esta_ativo` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0: Inativo | 1: Ativo' AFTER `valor`,
	CHANGE COLUMN `id_usuario` `id_usuario` INT(11) NOT NULL AFTER `data_atualizacao`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`id`) USING BTREE;

RENAME TABLE `transportes_cidades` TO `transportadores_raios`;

DROP TRIGGER IF EXISTS `tipo_frete_after_insert`;
DELIMITER //
CREATE TRIGGER `tipo_frete_after_insert` AFTER INSERT ON `tipo_frete` FOR EACH ROW BEGIN
    SET @ID_CIDADE_ = (SELECT colaboradores_enderecos.id_cidade FROM colaboradores_enderecos WHERE colaboradores_enderecos.id_colaborador = NEW.id_colaborador AND colaboradores_enderecos.eh_endereco_padrao = 1);
    
	IF(NEW.categoria = 'ML' AND (LENGTH(COALESCE(NEW.latitude, '')) = 0 OR LENGTH(COALESCE(NEW.longitude, '')) = 0)) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Para cadastrar um ponto de retirada é necessário ter a localização cadastrada no usuário';
	END IF;

	
	IF(NEW.categoria = 'ML') THEN
		
		UPDATE usuarios
		SET usuarios.permissao = IF(
			LOCATE(',60',usuarios.permissao) = 0,
			CONCAT(usuarios.permissao, ',60'),
			usuarios.permissao
		)
		WHERE usuarios.id_colaborador=NEW.id_colaborador;
	END IF;

	
	IF(NEW.categoria = 'MS') THEN
		
		UPDATE usuarios
		SET usuarios.permissao = IF(
			LOCATE(',61',usuarios.permissao) = 0,
			CONCAT(usuarios.permissao, ',61'),
			usuarios.permissao
		)
		WHERE usuarios.id_colaborador=NEW.id_colaborador;
	END IF;

	
	INSERT INTO tipo_frete_log (
		tipo_frete_log.mensagem,
		tipo_frete_log.id_usuario
	) VALUES (
		JSON_OBJECT(
			'NEW_id', NEW.id,
			'NEW_nome', NEW.nome,
			'NEW_titulo', NEW.titulo,
			'NEW_mensagem', NEW.mensagem,
			'NEW_tipo_ponto', NEW.tipo_ponto,
			'NEW_mensagem_cliente', NEW.mensagem_cliente,
			'NEW_mapa', NEW.mapa,
			'NEW_foto', NEW.foto,
			'NEW_id_colaborador', NEW.id_colaborador,
			'NEW_latitude', NEW.latitude,
			'NEW_longitude', NEW.longitude,
			'NEW_previsao_entrega', NEW.previsao_entrega,
			'NEW_categoria', NEW.categoria,
			'NEW_percentual_comissao', NEW.percentual_comissao,
			'NEW_horario_de_funcionamento', NEW.horario_de_funcionamento,
			'NEW_emitir_nota_fiscal', NEW.emitir_nota_fiscal,
			'NEW_id_usuario', NEW.id_usuario,
			'NEW_id_colaborador_ponto_coleta', NEW.id_colaborador_ponto_coleta
		),
		NEW.id_usuario
	);
    
    IF (NEW.tipo_ponto = 'PP') THEN
        INSERT IGNORE INTO transportadores_raios
            (
                transportadores_raios.id_colaborador,
                transportadores_raios.id_cidade,
                transportadores_raios.valor,
                transportadores_raios.esta_ativo,
                transportadores_raios.id_usuario
            )
        VALUES
            (
                NEW.id_colaborador,
                @ID_CIDADE_,
                0,
                TRUE,
                NEW.id_usuario
            );
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS `transacao_financeiras_metadados_before_insert`;
DROP TRIGGER IF EXISTS `entregas_before_insert`;
DROP TRIGGER IF EXISTS `entregas_after_update`;
DELIMITER //
CREATE TRIGGER `entregas_after_update` AFTER UPDATE ON `entregas` FOR EACH ROW BEGIN
	INSERT INTO entregas_logs (
		entregas_logs.id_entrega,
		entregas_logs.id_usuario,
		entregas_logs.situacao_anterior,
		entregas_logs.situacao_nova,
		entregas_logs.mensagem
	) VALUES (
		OLD.id,
		OLD.id_usuario,
		OLD.situacao,
		NEW.situacao,
		JSON_OBJECT(
			'NEW_id', NEW.id,
			'NEW_id_usuario', NEW.id_usuario,
			'NEW_id_cliente', NEW.id_cliente,
			'NEW_id_tipo_frete', NEW.id_tipo_frete,
			'NEW_id_transporte', NEW.id_transporte,
			'NEW_id_raio', NEW.id_raio,
			'NEW_situacao', NEW.situacao,
			'NEW_volumes', NEW.volumes,
			'NEW_uuid_entrega', NEW.uuid_entrega,
			'NEW_data_entrega', NEW.data_entrega,
			'NEW_data_criacao', NEW.data_criacao,
			'NEW_data_atualizacao', NEW.data_atualizacao
		)
	);

END//
DELIMITER ;

DROP TRIGGER IF EXISTS `estoque_grade_before_update`;
DELIMITER //
CREATE TRIGGER `estoque_grade_before_update` BEFORE UPDATE ON `estoque_grade` FOR EACH ROW BEGIN

   	DECLARE NUM_PARES_ADICIONADOS INT(11) DEFAULT 0;
	DECLARE NUM_PARES_REMOVIDOS INT(11) DEFAULT 0;
	DECLARE NUM_TOTAL_PARES INT(11);
	DECLARE CONTADOR INT(11) DEFAULT 0;
	DECLARE UUID_TEMP VARCHAR(56) DEFAULT '';
	DECLARE MENSAGEM LONGTEXT DEFAULT '';
	DECLARE TEMP_PEDIDO_ITEM_UUID VARCHAR(56) DEFAULT '';

	IF(OLD.id_responsavel <> NEW.id_responsavel) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Não é possível alterar o responsável dessa grade';
	END IF;

	IF(NEW.estoque <> OLD.estoque) THEN
	  SET NUM_TOTAL_PARES =
		CASE
			WHEN NEW.tipo_movimentacao = 'M' THEN OLD.estoque + OLD.vendido
			WHEN NEW.tipo_movimentacao = 'C' THEN OLD.estoque + NEW.vendido
			ELSE NEW.estoque + OLD.vendido
	   END;
		IF ((NEW.tipo_movimentacao = 'M') OR (NEW.tipo_movimentacao = 'E')) THEN
			IF (NEW.estoque > OLD.estoque) THEN
				SET NUM_PARES_ADICIONADOS = NEW.estoque - OLD.estoque;
				IF(NEW.tipo_movimentacao = 'M') THEN
					SET NEW.vendido = NEW.vendido - NUM_PARES_ADICIONADOS;
				END IF;

			ELSEIF ((NEW.estoque < OLD.estoque) AND (NEW.tipo_movimentacao = 'M'))  THEN
				SET NUM_PARES_REMOVIDOS = OLD.estoque - NEW.estoque;
				SET NEW.vendido = NEW.vendido + NUM_PARES_REMOVIDOS;
			ELSE
				SET MENSAGEM = 'Erro! forma de atualizacao do estoque esta incorreto nao permite retirada do estoque com o tipo E';
				signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
			END IF;
		ELSEIF ((NEW.tipo_movimentacao = 'S') AND (NEW.estoque < OLD.estoque)) THEN
			SET NUM_PARES_REMOVIDOS = OLD.estoque - NEW.estoque;
			SET NEW.vendido = NEW.vendido - NUM_PARES_REMOVIDOS;
			SET NEW.estoque = OLD.estoque;
			SET CONTADOR = 0;
			WHILE ((NEW.vendido < 0) AND (CONTADOR < NUM_PARES_REMOVIDOS)) DO
				SET NEW.estoque = NEW.estoque - 1;
				SET NEW.vendido = NEW.vendido + 1;
				SET CONTADOR = CONTADOR + 1;
			END WHILE;
		ELSEIF ((NEW.tipo_movimentacao = 'X') AND (NEW.estoque < OLD.estoque)) THEN
			IF (NEW.estoque < 0) THEN
				signal sqlstate '45000' set MESSAGE_TEXT = 'Erro! Estoque nao pode ficar negativo na movimentacao X';
			END IF;


		ELSEIF ((NEW.tipo_movimentacao = 'N') AND (NEW.estoque > OLD.estoque)) THEN
			SET NUM_PARES_ADICIONADOS = NEW.estoque - OLD.estoque;
			SET NEW.estoque = OLD.estoque;
			SET NEW.vendido = OLD.vendido + NUM_PARES_ADICIONADOS;
		ELSEIF ((NEW.tipo_movimentacao = 'C') AND (CHAR_LENGTH(NEW.descricao) > 0)) THEN
			SET MENSAGEM = CONCAT(MENSAGEM, 'Correcaoo manual');
		ELSE
			SET MENSAGEM = CONCAT('Nao e permitido alterar estoque sem indicar o tipo de movimentacao correta ou descricao. ',MENSAGEM);
			signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
		END IF;

	END IF;

    IF ((NEW.estoque < 0) OR (NEW.vendido < 0)) THEN
		SET MENSAGEM = CONCAT('Erro nao e permitido estoque negativo ',MENSAGEM);
		signal sqlstate '45060' set MESSAGE_TEXT = MENSAGEM;
    END IF;
	IF (NUM_TOTAL_PARES <> (NEW.estoque + NEW.vendido)) THEN
		SET MENSAGEM = CONCAT(MENSAGEM, 'Erro na conferencia de dados do estoque. ');
		signal sqlstate '45000' set MESSAGE_TEXT = MENSAGEM;
	END IF;

	INSERT INTO log_estoque_movimentacao(
		log_estoque_movimentacao.id_produto,
		log_estoque_movimentacao.nome_tamanho,
		log_estoque_movimentacao.oldEstoque,
		log_estoque_movimentacao.newEstoque,
		log_estoque_movimentacao.oldVendido,
		log_estoque_movimentacao.newVendido,
		log_estoque_movimentacao.tipo_movimentacao,
		log_estoque_movimentacao.descricao,
		log_estoque_movimentacao.id_responsavel_estoque
	) VALUES (
		NEW.id_produto,
		NEW.nome_tamanho,
		OLD.estoque,
		NEW.estoque,
		OLD.vendido,
		NEW.vendido,
		NEW.tipo_movimentacao,
		CONCAT(MENSAGEM, NEW.descricao),
		NEW.id_responsavel
	);

	SET NEW.tipo_movimentacao = '';
	SET NEW.descricao = '';
END//
DELIMITER ;
