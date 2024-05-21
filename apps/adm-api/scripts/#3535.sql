UPDATE produtos
SET
    produtos.valor_custo_produto = 0.10
WHERE
    produtos.id = 82042;

ALTER TABLE municipios
ADD COLUMN id_colaborador_transportador INT (11) NOT NULL DEFAULT 32257 COMMENT 'Depreciado\n\n@issue https://github.com/mobilestock/backend/issues/92' AFTER valor_adicional,
ADD COLUMN dias_entregar_frete TINYINT (2) NOT NULL DEFAULT 1 COMMENT 'Depreciado\n\n@issue https://github.com/mobilestock/backend/issues/92' AFTER id_colaborador_transportador;
