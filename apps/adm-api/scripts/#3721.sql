ALTER TABLE produtos
    DROP COLUMN porcentagem_comissao;

ALTER TABLE configuracoes
    DROP COLUMN porcentagem_comissao;

UPDATE configuracoes
    SET comissoes_json = '{"comissao_direito_coleta": 10, "produtos_json": {"porcentagem_comissao_ml": 11, "porcentagem_comissao_ms": 12.28, "custo_max_aplicar_taxa_ml": 60, "custo_max_aplicar_taxa_ms": 60, "taxa_produto_barato_ml": 2, "taxa_produto_barato_ms": 2}}';

DROP TRIGGER IF EXISTS produtos_before_insert;

DELIMITER //

CREATE TRIGGER `produtos_before_insert` BEFORE INSERT ON `produtos` FOR EACH ROW BEGIN
	DECLARE
		VALOR_CALCULO_PORCENTAGEM_,
		COMISSAO_MS,
		COMISSAO_PONTO_COLETA,
		COMISSAO_ML
	DECIMAL(10,2) DEFAULT 0;

	SET NEW.proporcao_caixa = 1;
	SELECT
		configuracoes.porcentagem_comissao_ponto_coleta,
		JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ms'),
		JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ml')
		INTO
			COMISSAO_PONTO_COLETA,
			COMISSAO_MS,
			COMISSAO_ML
	FROM configuracoes;

	SET NEW.porcentagem_comissao_ponto_coleta = COMISSAO_PONTO_COLETA;

	IF(NEW.valor_custo_produto > 0)THEN

		IF(NEW.preco_promocao > 0 AND NEW.preco_promocao <= 100 ) THEN
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto - ( NEW.valor_custo_produto * ( NEW.preco_promocao / 100 ) );
		ELSE
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto;
		END IF;

		SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( COMISSAO_MS / ( 100 - COMISSAO_MS ) ) ),
			 NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * COMISSAO_ML / 100, 2)
															 + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2),
			 NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;
	END IF;
END//
DELIMITER ;
