DROP TABLE IF EXISTS reposicoes_grades;
DROP TABLE IF EXISTS reposicoes;

UPDATE configuracoes
SET configuracoes.json_logistica = '{"separacao_fulfillment": {"horarios": ["08:00", "11:00", "15:00"], "horas_carencia_retirada": "02:00"}, "periodo_retencao_sku": {"anos_apos_entregue": 2, "dias_aguardando_entrada": 120}}'
WHERE TRUE;

DROP TABLE IF EXISTS produtos_logistica;

CREATE TABLE produtos_logistica (
    sku CHAR(12) NOT NULL PRIMARY KEY COLLATE 'utf8_bin',
    id_produto INT NOT NULL,
    nome_tamanho VARCHAR(50) NOT NULL COLLATE 'utf8_general_ci',
    situacao ENUM(
        'AGUARDANDO_ENTRADA',
        'EM_ESTOQUE'
    ) NOT NULL DEFAULT 'AGUARDANDO_ENTRADA',
    id_usuario INT(11) NOT NULL,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    INDEX idx_id_produto (id_produto),
    CONSTRAINT fk_produtos_id FOREIGN KEY (id_produto) REFERENCES produtos (id) ON DELETE CASCADE,
    UNIQUE INDEX unique_sku_produto (sku)
);

ALTER TABLE logistica_item
    ADD sku CHAR(12) NULL COLLATE 'utf8_bin' AFTER uuid_produto;

CREATE INDEX idx_sku
    ON logistica_item (sku);

CREATE TABLE IF NOT EXISTS produtos_logistica_logs (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    sku CHAR(12) NOT NULL COLLATE 'utf8_bin',
    mensagem longtext NOT NULL,
    data_criacao timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2162912 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DELIMITER $$
DROP TRIGGER IF EXISTS produtos_logistica_after_update$$
CREATE TRIGGER produtos_logistica_after_update AFTER UPDATE ON produtos_logistica FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                NEW.sku,
                JSON_OBJECT(
                    'sku', NEW.sku,
                    'id_produto', NEW.id_produto,
                    'nome_tamanho', NEW.nome_tamanho,
                    'situacao', NEW.situacao,
                    'id_usuario', NEW.id_usuario,
                    'data_criacao', NEW.data_criacao,
                    'data_atualizacao', NEW.data_atualizacao
                )
           );
END$$

DROP TRIGGER IF EXISTS produtos_logistica_after_insert$$
CREATE TRIGGER produtos_logistica_after_insert AFTER INSERT ON produtos_logistica FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                NEW.sku,
                JSON_OBJECT(
                    'sku', NEW.sku,
                    'id_produto', NEW.id_produto,
                    'nome_tamanho', NEW.nome_tamanho,
                    'situacao', NEW.situacao,
                    'id_usuario', NEW.id_usuario,
                    'data_criacao', NEW.data_criacao,
                    'data_atualizacao', NEW.data_atualizacao
                )
           );
END$$

DROP TRIGGER IF EXISTS produtos_logistica_after_delete$$
CREATE TRIGGER produtos_logistica_after_delete AFTER DELETE ON produtos_logistica FOR EACH ROW
BEGIN
    INSERT INTO produtos_logistica_logs (sku, mensagem)
    VALUES (
                OLD.sku,
                JSON_OBJECT(
                    'REGISTRO_APAGADO', true,
                    'sku', OLD.sku,
                    'id_produto', OLD.id_produto,
                    'nome_tamanho', OLD.nome_tamanho,
                    'situacao', OLD.situacao,
                    'id_usuario', OLD.id_usuario,
                    'data_criacao', OLD.data_criacao,
                    'data_atualizacao', OLD.data_atualizacao
                )
           );
END$$

DROP TRIGGER IF EXISTS logistica_item_before_insert $$
CREATE TRIGGER logistica_item_before_insert
    BEFORE INSERT
    ON logistica_item
    FOR EACH ROW
BEGIN
	IF (NEW.situacao <> 'PE' OR NEW.id_entrega IS NOT NULL) THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Um item de logística não pode ser criado já movimentado';
	END IF;

	INSERT INTO logistica_item_logs (
		logistica_item_logs.uuid_produto,
		logistica_item_logs.mensagem
	) VALUES (
		NEW.uuid_produto,
		JSON_OBJECT(
			'id', NEW.id,
			'id_usuario', NEW.id_usuario,
			'id_cliente', NEW.id_cliente,
			'id_transacao', NEW.id_transacao,
			'id_produto', NEW.id_produto,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_colaborador_tipo_frete', NEW.id_colaborador_tipo_frete,
			'id_entrega', NEW.id_entrega,
			'nome_tamanho', NEW.nome_tamanho,
			'situacao', NEW.situacao,
			'preco', NEW.preco,
			'uuid_produto', NEW.uuid_produto,
            'sku', NEW.sku,
			'data_atualizacao', NEW.data_atualizacao,
			'data_criacao', NEW.data_criacao,
			'observacao', NEW.observacao
		)
	);
END$$

DROP TRIGGER IF EXISTS logistica_item_after_update$$
CREATE TRIGGER logistica_item_after_update AFTER UPDATE ON logistica_item FOR EACH ROW BEGIN
	DECLARE DESCRICAO_MOVIMENTACAO VARCHAR(255) DEFAULT '';
	DECLARE TIPO_MOVIMENTACAO CHAR(1) DEFAULT '';
	DECLARE QUANTIDADE_MOVIMENTACAO TINYINT DEFAULT 0;
	DECLARE EXISTE_NEGOCIACAO BOOLEAN DEFAULT FALSE;
	DECLARE FOI_CLIENTE BOOLEAN DEFAULT FALSE;

	IF (
		(
			OLD.situacao <> 'SE'
			AND NEW.situacao = 'SE'
		) OR (
			OLD.situacao <> 'RE'
			AND NEW.situacao = 'RE'
		)
	) THEN
		SET EXISTE_NEGOCIACAO = EXISTS(
			SELECT 1
			FROM negociacoes_produto_temp
			WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto
		);
		IF (NEW.situacao = 'RE') THEN
			SET FOI_CLIENTE = NEW.id_usuario NOT IN (
				2,
				(
					SELECT usuarios.id
					FROM usuarios
					WHERE usuarios.id_colaborador = NEW.id_responsavel_estoque
					LIMIT 1
				)
			);
		END IF;

		IF (NEW.situacao = 'SE') THEN
			
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item separado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (NOT FOI_CLIENTE AND OLD.situacao = 'PE') THEN
			
			
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item cancelado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (EXISTE_NEGOCIACAO) THEN
			
			
			SET TIPO_MOVIMENTACAO = 'S';
			SET QUANTIDADE_MOVIMENTACAO = -1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item tinha negociação aberta e foi cancelado. transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSEIF (FOI_CLIENTE AND NOT EXISTE_NEGOCIACAO) THEN
			
			SET TIPO_MOVIMENTACAO = IF (OLD.situacao = 'SE', 'E', 'M');
			SET QUANTIDADE_MOVIMENTACAO = 1;
			SET DESCRICAO_MOVIMENTACAO = CONCAT(
				'Item removido da logistica, transacao ',
				NEW.id_transacao,
				' uuid ',
				NEW.uuid_produto
			);
		ELSE
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;

		UPDATE estoque_grade SET
			estoque_grade.estoque = estoque_grade.estoque + QUANTIDADE_MOVIMENTACAO,
			estoque_grade.tipo_movimentacao = TIPO_MOVIMENTACAO,
			estoque_grade.descricao = DESCRICAO_MOVIMENTACAO
		WHERE estoque_grade.id_produto = NEW.id_produto
			AND estoque_grade.nome_tamanho = NEW.nome_tamanho
			AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

		IF(ROW_COUNT() = 0) THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
		END IF;

		IF (
			NOT FOI_CLIENTE
			AND NEW.situacao = 'RE'
			AND NEW.id_responsavel_estoque > 1
			AND EXISTS(
				SELECT 1
				FROM estoque_grade
				WHERE estoque_grade.id_produto = NEW.id_produto
					AND estoque_grade.nome_tamanho = NEW.nome_tamanho
					AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque
					AND estoque_grade.estoque > 0
			)
		) THEN
			UPDATE estoque_grade SET
				estoque_grade.estoque = 0,
				estoque_grade.tipo_movimentacao = 'X',
				estoque_grade.descricao = CONCAT(
					'Estoque zerado por cancelamento. transacao ',
					NEW.id_transacao,
					' uuid ',
					NEW.uuid_produto
				)
			WHERE estoque_grade.id_produto = NEW.id_produto
				AND estoque_grade.nome_tamanho = NEW.nome_tamanho
				AND estoque_grade.id_responsavel = NEW.id_responsavel_estoque;

			IF(ROW_COUNT() = 0) THEN
				SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.';
			END IF;
		END IF;

		IF (EXISTE_NEGOCIACAO) THEN
			INSERT INTO negociacoes_produto_log (
				negociacoes_produto_log.uuid_produto,
				negociacoes_produto_log.mensagem,
				negociacoes_produto_log.situacao,
				negociacoes_produto_log.id_usuario
			) VALUES (
				NEW.uuid_produto,
				JSON_OBJECT(
					'produto_negociado', JSON_OBJECT(
						'id_produto', NEW.id_produto,
						'nome_tamanho', NEW.nome_tamanho
					),
					'produtos_oferecidos', (
						SELECT negociacoes_produto_temp.itens_oferecidos
						FROM negociacoes_produto_temp
						WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto
					),
					'produto_substituto', NULL
				),
				IF (NEW.situacao = 'SE', 'CANCELADA', 'RECUSADA'),
				NEW.id_usuario
			);

			DELETE FROM negociacoes_produto_temp
			WHERE negociacoes_produto_temp.uuid_produto = NEW.uuid_produto;

			IF (ROW_COUNT() = 0) THEN
				SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao cancelar negociacao, reporte a equipe de T.I.';
			END IF;
		END IF;
	END IF;

	IF(OLD.situacao <> NEW.situacao) THEN

		INSERT INTO logistica_item_data_alteracao (
			logistica_item_data_alteracao.id_linha,
			logistica_item_data_alteracao.uuid_produto,
			logistica_item_data_alteracao.situacao_anterior,
			logistica_item_data_alteracao.situacao_nova,
			logistica_item_data_alteracao.id_usuario
		) VALUES(
			NEW.id,
			NEW.uuid_produto,
			OLD.situacao,
			NEW.situacao,
			NEW.id_usuario
		);
	END IF;
	
	INSERT INTO logistica_item_logs (
		logistica_item_logs.uuid_produto,
		logistica_item_logs.mensagem
	) VALUES (
		NEW.uuid_produto,
		JSON_OBJECT(
			'id', NEW.id,
			'id_usuario', NEW.id_usuario,
			'id_cliente', NEW.id_cliente,
			'id_transacao', NEW.id_transacao,
			'id_produto', NEW.id_produto,
			'id_responsavel_estoque', NEW.id_responsavel_estoque,
			'id_colaborador_tipo_frete', NEW.id_colaborador_tipo_frete,
			'id_entrega', NEW.id_entrega,
			'nome_tamanho', NEW.nome_tamanho,
			'situacao', NEW.situacao,
			'preco', NEW.preco,
			'uuid_produto', NEW.uuid_produto,
			'sku', NEW.sku,
			'data_atualizacao', NEW.data_atualizacao,
			'data_criacao', NEW.data_criacao,
			'observacao', NEW.observacao
		)
	);
END$$


DELIMITER ;
