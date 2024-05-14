-- Active: 1713881408892@@127.0.0.1@3306@MOBILE_ENTREGAS
UPDATE produtos
SET
    produtos.valor_custo_produto = '0.10'
WHERE
    produtos.id = 82042;

ALTER TABLE municipios
ADD COLUMN id_colaborador_frete_expresso INT (11) NOT NULL DEFAULT 32257 AFTER valor_adicional,
ADD COLUMN dias_entrega TINYINT (2) NOT NULL DEFAULT 1 AFTER id_colaborador_frete_expresso;