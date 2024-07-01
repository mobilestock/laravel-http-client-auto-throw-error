ALTER TABLE catalogo_fixo
	DROP FOREIGN KEY catalogo_fixo.catalogo_fixo_ibfk_1,
	DROP INDEX catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao_produto
