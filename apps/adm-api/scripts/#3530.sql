ALTER TABLE catalogo_personalizado
    CHANGE COLUMN catalogo_personalizado.ativo catalogo_personalizado.esta_ativo TINYINT(1) DEFAULT 1,
    CHANGE COLUMN catalogo_personalizado.produtos catalogo_personalizado.json_produtos TEXT NOT NULL DEFAULT '[]',
    CHANGE COLUMN catalogo_personalizado.plataformas_filtros catalogo_personalizado.json_plataformas_filtros VARCHAR(20) DEFAULT '["MS","ML","MED"]';

ALTER TABLE configuracoes
    CHANGE COLUMN configuracoes.filtros_pesquisa_ordenados configuracoes.json_filtros_pesquisa_ordenados LONGTEXT NOT NULL DEFAULT '[]' COMMENT 'Os filtros de ordenamento ordenados de acordo com a necessidade da plataforma',
    CHANGE COLUMN configuracoes.filtros_pesquisa_padrao configuracoes.json_filtros_pesquisa_padrao LONGTEXT NOT NULL DEFAULT '[{"id":"LANCAMENTO","nome":"Lançamentos"},{"id":"PROMOCAO","nome":"Promoções"},{"id":"MELHOR_FABRICANTE","nome":"Melhores Fabricantes"},{"id":"MENOR_PRECO","nome":"Menor Preço"}]' COMMENT 'Os filtros de ordenamento de catálogo comuns em todos os sites da plataforma';
