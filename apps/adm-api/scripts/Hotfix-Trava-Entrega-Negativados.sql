DROP TRIGGER IF EXISTS `entregas_faturamento_item_before_update`;
DELIMITER //
CREATE TRIGGER `entregas_faturamento_item_before_update` BEFORE UPDATE ON `entregas_faturamento_item` FOR EACH ROW BEGIN

    IF( OLD.situacao <> NEW.situacao) THEN

        IF( OLD.situacao IN ('AR','EN') AND NEW.situacao IN ('PE') OR OLD.situacao = 'EN') THEN
            signal sqlstate '45000' set MESSAGE_TEXT = 'voce não tem permissão para modificar para esta situacao';
        END IF;

        IF(NEW.situacao = 'EN' AND saldo_cliente(NEW.id_cliente) < 0) THEN
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

END//
DELIMITER ;
