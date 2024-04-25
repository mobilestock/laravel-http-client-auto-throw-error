-- Dando permissão de fornecedor para o Santos Express
UPDATE usuarios
SET
    usuarios.permissao = '10,20,30,62'
WHERE
    usuarios.id_colaborador = 30726;

-- Remove o produto 82044 dos carrinhos de todos os clientes
DELETE FROM pedido_item
WHERE
    pedido_item.id_produto = 82044;