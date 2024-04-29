ALTER TABLE catalogo_personalizado
    CHANGE COLUMN catalogo_personalizado.produtos catalogo_personalizado.json_produtos TEXT DEFAULT '[]';
