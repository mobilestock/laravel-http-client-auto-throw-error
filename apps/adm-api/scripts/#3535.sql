UPDATE produtos
SET
    produtos.valor_custo_produto = 0.10
WHERE
    produtos.id = 82042;

ALTER TABLE municipios
ADD COLUMN id_colaborador_ponto_coleta INT (11) NOT NULL DEFAULT 32254 COMMENT 'Depreciado\n\n@issue https://github.com/mobilestock/backend/issues/92' AFTER valor_adicional,
ADD COLUMN dias_entregar_cliente TINYINT (2) NOT NULL DEFAULT 1 COMMENT 'Depreciado\n\n@issue https://github.com/mobilestock/backend/issues/92' AFTER id_colaborador_ponto_coleta;

ALTER TABLE colaboradores CHANGE COLUMN id_tipo_entrega_padrao id_tipo_entrega_padrao INT (11) NOT NULL DEFAULT 0 COMMENT 'Depreciado\n\n@issue https://github.com/mobilestock/backend/issues/193';