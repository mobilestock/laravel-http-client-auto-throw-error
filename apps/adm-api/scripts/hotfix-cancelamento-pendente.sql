DROP TRIGGER IF EXISTS `transacao_financeiras_produtos_itens_after_delete`;
DELIMITER //
CREATE TRIGGER `transacao_financeiras_produtos_itens_after_delete` AFTER DELETE ON `transacao_financeiras_produtos_itens` FOR EACH ROW BEGIN
    -- @issue https://github.com/mobilestock/web/issues/3167
    IF (OLD.tipo_item IN ('PR', 'RF')) THEN
        UPDATE pedido_item
        SET pedido_item.situacao = '1'
        WHERE pedido_item.uuid = OLD.uuid_produto
          AND pedido_item.situacao IN ('2', '3');
        IF (ROW_COUNT() <> 1) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Erro ao atualizar situação do item do pedido';
        END IF;
    END IF;
END//
DELIMITER ;
