-- Active: 1713881408892@@127.0.0.1@3306@MOBILE_ENTREGAS
UPDATE produtos
SET
    produtos.valor_custo_produto = '0.10'
WHERE
    produtos.id = 82042;

ALTER TABLE municipios
ADD COLUMN entregadores_frete_expresso VARCHAR(255) NOT NULL DEFAULT 32257 AFTER valor_adicional,
ADD COLUMN dias_entrega TINYINT (2) NOT NULL DEFAULT 1 AFTER entregadores_frete_expresso;