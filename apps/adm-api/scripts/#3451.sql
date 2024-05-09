-- Remove o produto 82044 dos carrinhos de todos os clientes
DELETE FROM pedido_item
WHERE
    pedido_item.id_produto = 82044;
