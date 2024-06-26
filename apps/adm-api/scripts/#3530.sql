ALTER TABLE catalogo_personalizado
    CHANGE COLUMN catalogo_personalizado.ativo catalogo_personalizado.esta_ativo TINYINT(1) DEFAULT 1,
    CHANGE COLUMN catalogo_personalizado.produtos catalogo_personalizado.json_produtos TEXT NOT NULL DEFAULT '[]',
    CHANGE COLUMN catalogo_personalizado.plataformas_filtros catalogo_personalizado.json_plataformas_filtros VARCHAR(20) DEFAULT '["MS","ML","MED"]';
