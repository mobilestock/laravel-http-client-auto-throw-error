ALTER TABLE produtos
    DROP COLUMN porcentagem_comissao;

ALTER TABLE configuracoes
    DROP COLUMN porcentagem_comissao;

UPDATE configuracoes
    SET comissoes_json = '{"comissao_direito_coleta": 10, "produtos_json": {"porcentagem_comissao_ml": 11, "porcentagem_comissao_ms": 12.28, "custo_max_aplicar_taxa_ml": 60, "custo_max_aplicar_taxa_ms": 60, "taxa_produto_barato_ml": 2, "taxa_produto_barato_ms": 2}}';
