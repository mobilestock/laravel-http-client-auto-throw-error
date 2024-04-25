ALTER TABLE entregas_log_entregas DROP FOREIGN KEY FK_entregas_log_entregas_id_entrega;
RENAME TABLE entregas_log_entregas TO entregas_logs;
DROP INDEX FK_entregas_log_entregas_id_entrega ON entregas_logs;
CREATE INDEX IDX_entregas_logs_id_entrega ON entregas_logs(id_entrega);

ALTER TABLE entregas_log_faturamento_item
	CHANGE COLUMN data_atualizacao data_criacao TIMESTAMP NOT NULL DEFAULT current_timestamp() AFTER situacao_nova;


DROP TRIGGER IF EXISTS entregas_faturamento_item_before_update;

DELIMITER //
CREATE  TRIGGER `entregas_faturamento_item_before_update` BEFORE UPDATE ON `entregas_faturamento_item` FOR EACH ROW BEGIN

    DECLARE CLIENTE_NEGATIVO BOOLEAN DEFAULT FALSE;

    IF( OLD.situacao <> NEW.situacao) THEN

        IF( OLD.situacao IN ('AR','EN') AND NEW.situacao IN ('PE') OR OLD.situacao = 'EN') THEN
            signal sqlstate '45000' set MESSAGE_TEXT = 'voce não tem permissão para modificar para esta situacao';
        END IF;

        SET CLIENTE_NEGATIVO = saldo_cliente(NEW.id_cliente) < 0;

        IF(
            NOT EXISTS(
                SELECT 1
                FROM usuarios
                WHERE
                    usuarios.id = NEW.id_usuario
                    AND usuarios.permissao REGEXP '[[:<:]](5[0-7])[[:>:]]'
                    AND usuarios.id <> 2
                )
            AND NEW.situacao = 'EN'
            AND CLIENTE_NEGATIVO
        ) THEN
            signal sqlstate '45050' set MESSAGE_TEXT = 'Para entregar esse produto é necessário entregar todas as trocas sinalizadas';
        END IF;

       	IF ( NEW.situacao = 'EN' ) THEN
       		SET NEW.data_base_troca = NOW();
       		SET NEW.data_entrega = NOW();
       	END IF;

    END IF;

	IF(NEW.data_criacao <> OLD.data_criacao) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'Não foi possível atualizar a entrega item. Notifique a equipe de TI.';
	END IF;

	IF (OLD.data_entrega IS NOT NULL AND OLD.data_entrega <> NEW.data_entrega) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'Não é permitido entregar o produto duas vezes. Notifique a equipe de TI.';
	END IF;
    
    SET @JSON_TEMP = JSON_OBJECT(
            'NEW_id', NEW.id,
            'NEW_id_usuario', NEW.id_usuario,
            'NEW_id_entrega', NEW.id_entrega,
            'NEW_id_transacao', NEW.id_transacao,
            'NEW_id_cliente', NEW.id_cliente,
            'NEW_situacao', NEW.situacao,
            'NEW_origem', NEW.origem,
            'NEW_uuid_produto', NEW.uuid_produto,
            'NEW_id_produto', NEW.id_produto,
            'NEW_nome_tamanho', NEW.nome_tamanho,
            'NEW_nome_recebedor', NEW.nome_recebedor,
            'NEW_data_criacao', NEW.data_criacao,
            'NEW_data_atualizacao', NEW.data_atualizacao,
            'NEW_id_responsavel_estoque', NEW.id_responsavel_estoque
	);
    
	IF CLIENTE_NEGATIVO THEN
        SET @JSON_TEMP = JSON_SET(@JSON_TEMP, '$.CLIENTE_NEGATIVO', TRUE);
    END IF;

    -- @issue https://github.com/mobilestock/web/issues/3176
    INSERT INTO entregas_log_faturamento_item (
        entregas_log_faturamento_item.id_usuario,
        entregas_log_faturamento_item.id_entregas_fi,
        entregas_log_faturamento_item.situacao_anterior,
        entregas_log_faturamento_item.situacao_nova,
        entregas_log_faturamento_item.mensagem
    ) VALUES (
        NEW.id_usuario,
        NEW.id,
        OLD.situacao,
        NEW.situacao,
        @JSON_TEMP
    );

END //
DELIMITER ;

DROP TRIGGER IF EXISTS entregas_after_update;
DELIMITER //
CREATE  TRIGGER entregas_after_update AFTER UPDATE ON entregas FOR EACH ROW BEGIN
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
			'OLD_id', OLD.id,
			'NEW_id', NEW.id,
			'OLD_id_usuario', OLD.id_usuario,
			'NEW_id_usuario', NEW.id_usuario,
			'OLD_id_cliente', OLD.id_cliente,
			'NEW_id_cliente', NEW.id_cliente,
			'OLD_id_tipo_frete', OLD.id_tipo_frete,
			'NEW_id_tipo_frete', NEW.id_tipo_frete,
			'OLD_id_transporte', OLD.id_transporte,
			'NEW_id_transporte', NEW.id_transporte,
			'OLD_id_cidade', OLD.id_cidade,
			'NEW_id_cidade', NEW.id_cidade,
			'OLD_situacao', OLD.situacao,
			'NEW_situacao', NEW.situacao,
			'OLD_volumes', OLD.volumes,
			'NEW_volumes', NEW.volumes,
			'OLD_uuid_entrega', OLD.uuid_entrega,
			'NEW_uuid_entrega', NEW.uuid_entrega,
			'OLD_data_entrega', OLD.data_entrega,
			'NEW_data_entrega', NEW.data_entrega,
			'OLD_data_criacao', OLD.data_criacao,
			'NEW_data_criacao', NEW.data_criacao,
			'OLD_data_atualizacao', OLD.data_atualizacao,
			'NEW_data_atualizacao', NEW.data_atualizacao
		)
	);

    IF (NEW.situacao IN ('PT','EN')) THEN

		DELETE FROM entregas_fechadas_temp WHERE entregas_fechadas_temp.id_entrega = NEW.id;
    

    END IF;

	IF (
		OLD.situacao <> NEW.situacao
		AND OLD.situacao IN ('EX','AB')
		AND NEW.situacao IN ('EX','PT','EN')
	) THEN
		DELETE FROM acompanhamento_temp
        WHERE
            acompanhamento_temp.id_tipo_frete = NEW.id_tipo_frete
            AND acompanhamento_temp.id_destinatario = NEW.id_cliente
            AND IF(NEW.id_cidade > 0,
                acompanhamento_temp.id_cidade = NEW.id_cidade,
                TRUE
            );
	END IF;

END//
DELIMITER ;

DROP TRIGGER IF EXISTS entregas_before_update;

DELIMITER //
CREATE TRIGGER `entregas_before_update` BEFORE UPDATE ON `entregas` FOR EACH ROW BEGIN
    IF(NEW.situacao <> OLD.situacao AND NEW.situacao = 'AB' AND (
                                                                  SELECT 1
                                                                  FROM entregas
                                                                  WHERE entregas.situacao = 'AB'
                                                                    AND entregas.id_cliente = new.id_cliente
                                                                    AND entregas.id_tipo_frete = NEW.id_tipo_frete
                                                                )) THEN
		SIGNAL sqlstate '45000'
		set MESSAGE_TEXT = 'Não é possivel ter duas entregas com situação Aberta para o mesmo cliente';
    END IF;

    IF(OLD.situacao IN ('AB', 'EX') AND NEW.situacao = 'PT') THEN
        UPDATE entregas_faturamento_item
        SET entregas_faturamento_item.id_usuario = NEW.id_usuario
        WHERE entregas_faturamento_item.id_entrega = NEW.id;
    END IF;

    IF (NEW.situacao <> OLD.situacao AND NEW.situacao = 'EN') THEN
    	SET NEW.data_entrega = NOW();
    END IF;

    IF(NEW.data_criacao <> OLD.data_criacao) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Não foi possível atualizar a entrega. Notifique a equipe de TI.';
	END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS entregas_faturamento_item_after_update;

DELIMITER $$
CREATE TRIGGER entregas_faturamento_item_after_update AFTER UPDATE ON entregas_faturamento_item FOR EACH ROW BEGIN
	DECLARE ID_COLABORADOR_TIPO_FRETE_, _ID_PONTO INT(11) DEFAULT 0;
    DECLARE TIPO_PONTO_ CHAR(2) DEFAULT NULL;

	IF(NEW.nome_tamanho <> OLD.nome_tamanho) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'O tamanho não pode ser modificado';
	END IF;

	IF( OLD.situacao <> NEW.situacao )THEN
		IF( OLD.id_usuario <> OLD.id_usuario AND NEW.id_usuario < 1 )THEN
			signal sqlstate '45000' set MESSAGE_TEXT = 'identifique se para alterar a situacao';
		END IF;
		IF( NEW.situacao = 'EN' )THEN

			UPDATE produtos
			SET produtos.data_ultima_entrega = NOW()
			WHERE produtos.id = NEW.id_produto;

			UPDATE lancamento_financeiro_pendente
				SET lancamento_financeiro_pendente.data_vencimento = CURRENT_DATE + INTERVAL 8 DAY
			WHERE lancamento_financeiro_pendente.tipo = 'P'
				AND lancamento_financeiro_pendente.transacao_origem = NEW.id_transacao
			   AND lancamento_financeiro_pendente.origem IN (SELECT DISTINCT transacao_financeiras_produtos_itens.sigla_lancamento
															FROM transacao_financeiras_produtos_itens
															WHERE transacao_financeiras_produtos_itens.uuid_produto = NEW.uuid_produto
															  AND transacao_financeiras_produtos_itens.sigla_lancamento IS NOT NULL)
				AND lancamento_financeiro_pendente.numero_documento = NEW.uuid_produto;

            IF (NEW.origem = 'ML') THEN
                UPDATE transacao_financeiras
                   SET transacao_financeiras.valor_credito_bloqueado = 0
                 WHERE transacao_financeiras.valor_credito_bloqueado > 0
                   AND transacao_financeiras.id = NEW.id_transacao;
            END IF;

			IF (NOT EXISTS(
				SELECT 1
				FROM avaliacao_produtos
				WHERE
					avaliacao_produtos.id_cliente = NEW.id_cliente AND
					avaliacao_produtos.id_produto = NEW.id_produto
				LIMIT 1
			)) THEN INSERT INTO avaliacao_produtos (id_cliente, id_produto, origem, data_avaliacao) VALUES (NEW.id_cliente, NEW.id_produto, 'ML', NULL);
			END IF;

			SELECT
				tipo_frete.id_colaborador,
				tipo_frete.tipo_ponto,
				entregas.id_tipo_frete
			INTO ID_COLABORADOR_TIPO_FRETE_, TIPO_PONTO_, _ID_PONTO
			FROM entregas
			JOIN tipo_frete ON tipo_frete.id = entregas.id_tipo_frete
			WHERE entregas.id = NEW.id_entrega;

			IF (EXISTS(SELECT 1
					   FROM logistica_item
					   WHERE logistica_item.id_cliente = NEW.id_cliente
					     AND logistica_item.id_transacao = NEW.id_transacao
						 AND logistica_item.id_produto = NEW.id_produto
						 AND logistica_item.nome_tamanho = NEW.nome_tamanho
						 AND logistica_item.id_responsavel_estoque = NEW.id_responsavel_estoque
						 AND logistica_item.uuid_produto = NEW.uuid_produto
						 AND logistica_item.id_colaborador_tipo_frete <> ID_COLABORADOR_TIPO_FRETE_)) THEN
				INSERT INTO entregas_fila_processo_alterar_entregador (
					entregas_fila_processo_alterar_entregador.uuid_produto,
					entregas_fila_processo_alterar_entregador.id_colaborador_tipo_frete
				) SELECT
					NEW.uuid_produto,
					ID_COLABORADOR_TIPO_FRETE_;
			END IF;

			IF (
				_ID_PONTO AND
				TIPO_PONTO_ = 'PP' AND
				NOT EXISTS(
					SELECT 1
					FROM avaliacao_tipo_frete
					WHERE
						avaliacao_tipo_frete.id_colaborador = NEW.id_cliente AND
						avaliacao_tipo_frete.id_tipo_frete = _ID_PONTO
					LIMIT 1
				)
			) THEN INSERT INTO avaliacao_tipo_frete (id_colaborador, id_tipo_frete) VALUES (NEW.id_cliente, _ID_PONTO);
			END IF;

		END IF;
	END IF;

END$$
DELIMITER ;

ALTER TABLE configuracoes ADD permite_monitoramento_sentry BOOL DEFAULT 1 NOT NULL COMMENT 'permite o monitoramento com o sentry';

DROP PROCEDURE IF EXISTS entrega_bip_entrega;
DROP TABLE IF EXISTS entregas_bipagem;
DROP TABLE IF EXISTS entregas_devolucoes_item_problemas;
DROP TABLE IF EXISTS entregas_etiquetas_problemas;
DROP TABLE IF EXISTS entregas_faturamento_item_problemas;
DROP TABLE IF EXISTS entregas_restringe_usuario_setor;
DROP TABLE IF EXISTS entregas_temp;
DROP TABLE IF EXISTS entregas_painel;
DROP TABLE IF EXISTS faturamento;
DROP TABLE IF EXISTS entregas_log_bipagem_usuario;

ALTER TABLE entregas DROP COLUMN observacao;
ALTER TABLE entregas DROP COLUMN id_lancamento;
ALTER TABLE entregas DROP COLUMN id_localizacao;
