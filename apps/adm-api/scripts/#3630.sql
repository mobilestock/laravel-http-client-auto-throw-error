ALTER TABLE catalogo_fixo
	DROP FOREIGN KEY catalogo_fixo.catalogo_fixo_ibfk_1,
	DROP INDEX catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao_produto;

ALTER TABLE configuracoes
    DROP COLUMN configuracoes.qtd_maxima_dias_produto_fulfillment_parado,
	ADD COLUMN configuracoes.json_configuracoes_job_gerencia_estoque_parado JSON
        DEFAULT '{"qtd_maxima_dias":365,"percentual_desconto":30,"dias_carencia":30}'
        AFTER produtos_promocoes;
