-- Active: 1713881408892@@127.0.0.1@3306@MOBILE_ENTREGAS
UPDATE produtos
SET
    produtos.valor_custo_produto = '0.10'
WHERE
    produtos.id = 82042;

ALTER TABLE municipios
ADD COLUMN eh_frete_expresso TINYINT (1) NOT NULL DEFAULT 0 AFTER valor_adicional,
ADD COLUMN dias_entrega TINYINT (2) NOT NULL DEFAULT 1 AFTER eh_frete_expresso;
