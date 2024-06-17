ALTER TABLE transportadores_raios
ADD COLUMN valor_coleta DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Se o valor estiver como 0, então a coleta para esse raio ficará inativa.' AFTER valor;

UPDATE transportadores_raios
SET
    transportadores_raios.valor_coleta = 2.50
WHERE
    transportadores_raios.esta_ativo = 1;

ALTER TABLE transportadores_raios CHANGE valor valor_entrega DECIMAL(10, 2) NOT NULL DEFAULT 3.00;

ALTER TABLE transacao_financeiras_produtos_itens CHANGE COLUMN tipo_item tipo_item ENUM ('AC', 'AP', 'CC', 'CE', 'CL', 'CO', 'FR', 'PR', 'RF', 'CM_LOGISTICA', 'CM_PONTO_COLETA', 'CM_ENTREGA', 'DIREITO_COLETA') CHARACTER
SET
    'utf8' COLLATE 'utf8_swedish_ci' NOT NULL COMMENT 'PR- Produto FR-Frete AC-Adição de credito RF-Retorno Fornecedor AP-Acréscimo CNPJ CC-Comissão criador publicação CE-Comissão entregador CL-Comissão link CO-Comissão MED CM_LOGISTICA-Comissão logistica CM_PONTO_COLETA-Comissão ponto coleta CM_ENTREGA- Comissão tarifa de entrega DIREITO_COLETA-Comissão referênte à coleta do Mobile Entregas';

ALTER TABLE configuracoes
ADD COLUMN porcentagem_comissao_coleta DECIMAL(4, 2) NULL DEFAULT '10' AFTER porcentagem_comissao;

ALTER TABLE transacao_financeiras_metadados CHANGE COLUMN chave chave ENUM (
    'ID_COLABORADOR_TIPO_FRETE',
    'ENDERECO_CLIENTE_JSON',
    'PRODUTOS_JSON',
    'VALOR_FRETE',
    'ID_PEDIDO',
    'ID_UNICO',
    'PRODUTOS_TROCA',
    'ENDERECO_COLETA_JSON'
) NOT NULL COLLATE 'utf8_bin' AFTER id_transacao;


DELIMITER $$
DROP TRIGGER IF EXISTS transacao_financeiras_produtos_itens_before_insert$$
CREATE TRIGGER transacao_financeiras_produtos_itens_before_insert BEFORE INSERT ON transacao_financeiras_produtos_itens FOR EACH ROW BEGIN
	IF(NEW.tipo_item IN ('PR', 'RF') AND EXISTS(SELECT 1 FROM transacao_financeiras_produtos_itens WHERE transacao_financeiras_produtos_itens.tipo_item IN ('PR', 'RF') AND transacao_financeiras_produtos_itens.uuid_produto = NEW.uuid_produto)) THEN
		SIGNAL sqlstate '45000' set MESSAGE_TEXT = 'Erro produto já existe';
	END IF;
	IF(NEW.tipo_item IN ('PR', 'RF') AND COALESCE(NEW.nome_tamanho, '') = '') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'nome_tamanho não pode ser NULL';
	END IF;
	
	IF(NEW.momento_pagamento IS NULL) THEN
		SET NEW.momento_pagamento = IF(NEW.tipo_item IN ('PR','CC','CE','CL','CM_PONTO_COLETA','CM_ENTREGA') AND
										NEW.uuid_produto IS NOT NULL AND
										EXISTS(SELECT 1
											  FROM transacao_financeiras
											  WHERE transacao_financeiras.id = NEW.id_transacao
											    AND transacao_financeiras.origem_transacao = 'ML'),
									       'CARENCIA_ENTREGA',
									       'PAGAMENTO'
									);
	END IF;
	
	IF(NEW.sigla_lancamento IS NULL) THEN
		SET NEW.sigla_lancamento = CASE
										WHEN NEW.tipo_item = 'AC' THEN 'CM'
										WHEN NEW.tipo_item = 'PR' THEN 'SC'
										WHEN NEW.tipo_item IN ('CC','CE','CL','CM_PONTO_COLETA','CM_ENTREGA','DIREITO_COLETA')
											THEN NEW.tipo_item
										ELSE NULL
								   END;
	END IF;
	
	IF(NEW.sigla_estorno IS NULL) THEN
		SET NEW.sigla_estorno = CASE NEW.tipo_item
								   	WHEN 'PR' THEN 'TF'
									WHEN 'CC' THEN 'TC'
									WHEN 'CE' THEN 'TE'
									WHEN 'CL' THEN 'TL'
									WHEN 'CM_PONTO_COLETA' THEN 'TR_PONTO_COLETA'
									ELSE NULL
								END;
	END IF;

	IF (NEW.preco <= 0) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'preco deve ser maior que 0';
	END IF;
END$$


DROP TRIGGER IF EXISTS tipo_frete_after_insert$$
CREATE TRIGGER tipo_frete_after_insert AFTER INSERT ON tipo_frete FOR EACH ROW BEGIN
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
                transportadores_raios.valor_entrega,
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
END$$
DELIMITER ;


DELIMITER ;