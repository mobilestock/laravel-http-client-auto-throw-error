ALTER TABLE produtos
    DROP COLUMN porcentagem_comissao,
    DROP COLUMN porcentagem_comissao_ml,
    DROP COLUMN porcentagem_comissao_ms;

ALTER TABLE configuracoes
    DROP COLUMN porcentagem_comissao,
    DROP COLUMN porcentagem_comissao_ml,
    DROP COLUMN porcentagem_comissao_ms;

UPDATE configuracoes
    SET comissoes_json = '{"comissao_direito_coleta": 10, "produtos_json": {"porcentagem_comissao_ml": 11, "porcentagem_comissao_ms": 12.28, "custo_max_aplicar_taxa_ml": 60, "custo_max_aplicar_taxa_ms": 60, "taxa_produto_barato_ml": 2, "taxa_produto_barato_ms": 2}}';

-- UPDATE produtos
--     SET valor_venda_ml = valor_venda_ml + 2,
--         valor_venda_ms = valor_venda_ms + 2
--     WHERE valor_custo_produto < 60
--     AND id NOT IN (82044, 82042, 99265, 93923);

DROP TRIGGER IF EXISTS produtos_before_insert;
DROP TRIGGER IF EXISTS produtos_before_update;

DELIMITER //

CREATE TRIGGER `produtos_before_insert` BEFORE INSERT ON `produtos` FOR EACH ROW BEGIN
	DECLARE
		VALOR_CALCULO_PORCENTAGEM_,
		COMISSAO_MS,
		COMISSAO_PONTO_COLETA,
		COMISSAO_ML,
        CUSTO_MAX_APLICAR_TAXA_ML,
        CUSTO_MAX_APLICAR_TAXA_MS,
        TAXA_PRODUTO_BARATO_ML,
        TAXA_PRODUTO_BARATO_MS
	DECIMAL(10,2) DEFAULT 0;

	SET NEW.proporcao_caixa = 1;
	SELECT
		configuracoes.porcentagem_comissao_ponto_coleta,
		JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ms'),
		JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.custo_max_aplicar_taxa_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.custo_max_aplicar_taxa_ms'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.taxa_produto_barato_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.taxa_produto_barato_ms')
		INTO
			COMISSAO_PONTO_COLETA,
			COMISSAO_MS,
			COMISSAO_ML,
            CUSTO_MAX_APLICAR_TAXA_ML,
            CUSTO_MAX_APLICAR_TAXA_MS,
            TAXA_PRODUTO_BARATO_ML,
            TAXA_PRODUTO_BARATO_MS
	FROM configuracoes;

	SET NEW.porcentagem_comissao_ponto_coleta = COMISSAO_PONTO_COLETA;

	IF(NEW.valor_custo_produto > 0)THEN

		IF(NEW.preco_promocao > 0 AND NEW.preco_promocao <= 100 ) THEN
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto - ( NEW.valor_custo_produto * ( NEW.preco_promocao / 100 ) );
		ELSE
			SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto;
		END IF;

        SET NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;

        IF (VALOR_CALCULO_PORCENTAGEM_ < CUSTO_MAX_APLICAR_TAXA_ML AND NEW.id NOT IN (82044, 82042, 99265, 93923)) THEN
            SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * COMISSAO_ML / 100, 2) + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2) + TAXA_PRODUTO_BARATO_ML;
        ELSE
            SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * COMISSAO_ML / 100, 2) + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2);
        END IF;

        IF (VALOR_CALCULO_PORCENTAGEM_ < CUSTO_MAX_APLICAR_TAXA_MS AND NEW.id NOT IN (82044, 82042, 99265, 93923)) THEN
            SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( COMISSAO_MS / ( 100 - COMISSAO_MS ) ) ) + TAXA_PRODUTO_BARATO_MS;
        ELSE
            SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( COMISSAO_MS / ( 100 - COMISSAO_MS ) ) );
        END IF;
	END IF;
END//

CREATE TRIGGER `produtos_before_update` BEFORE UPDATE ON `produtos` FOR EACH ROW
BEGIN
	DECLARE
		VALOR_CALCULO_PORCENTAGEM_,
		COMISSAO_MS,
		COMISSAO_ML,
        CUSTO_MAX_APLICAR_TAXA_ML,
        CUSTO_MAX_APLICAR_TAXA_MS,
        TAXA_PRODUTO_BARATO_ML,
        TAXA_PRODUTO_BARATO_MS
	DECIMAL(10,2) DEFAULT 0;

	IF( NEW.preco_promocao < 0 OR NEW.preco_promocao > 100 ) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'A porcentagem de promoção deve ter um valor valido de 0 a 100';
	END IF;

	IF(NEW.data_primeira_entrada IS NULL AND (OLD.data_entrada <> NEW.data_entrada ))THEN
		SET NEW.data_primeira_entrada = NOW();
	END IF;

	IF ( OLD.valor_custo_produto <> NEW.valor_custo_produto AND NEW.preco_promocao > 0 ) THEN
		SET NEW.preco_promocao = 0;
	END IF;

    SELECT
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ms'),
		JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.porcentagem_comissao_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.custo_max_aplicar_taxa_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.custo_max_aplicar_taxa_ms'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.taxa_produto_barato_ml'),
        JSON_VALUE(configuracoes.comissoes_json, '$.produtos_json.taxa_produto_barato_ms')
		INTO
			COMISSAO_MS,
			COMISSAO_ML,
            CUSTO_MAX_APLICAR_TAXA_ML,
            CUSTO_MAX_APLICAR_TAXA_MS,
            TAXA_PRODUTO_BARATO_ML,
            TAXA_PRODUTO_BARATO_MS
	FROM configuracoes;

	SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto;

	IF ( NEW.preco_promocao <> OLD.preco_promocao) THEN
		IF ( NEW.preco_promocao = 0) THEN
			SET NEW.valor_custo_produto = NEW.valor_custo_produto_historico;
		ELSE
			SET NEW.promocao = 1;

            IF (OLD.promocao = 0) THEN
                IF (TRUE IN (
                    OLD.data_ultima_entrega IS NULL,
                    OLD.data_atualizou_valor_custo IS NOT NULL AND OLD.data_ultima_entrega < OLD.data_atualizou_valor_custo
                )) THEN
                    SET @MENSAGEM = (
                        SELECT CONCAT('O produto ', OLD.id, ' precisa de uma venda entregue antes de entrar em promoção')
                    );
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @MENSAGEM;
                END IF;

                SET @HORAS_ESPERA_REATIVAR_PROMOCAO = COALESCE((
                    SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.HORAS_ESPERA_REATIVAR_PROMOCAO')
                    FROM configuracoes
                    LIMIT 1
                ), 72);

                IF(NOW() - INTERVAL @HORAS_ESPERA_REATIVAR_PROMOCAO HOUR < OLD.data_atualizou_valor_custo) THEN
                    SET @MENSAGEM = (
                        SELECT CONCAT('O produto ', OLD.id, ' só poderá entrar em promoção após ', @HORAS_ESPERA_REATIVAR_PROMOCAO, ' horas')
                    );
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @MENSAGEM;
                END IF;
            END IF;

			IF( OLD.preco_promocao >= 1 AND OLD.valor_custo_produto = NEW.valor_custo_produto ) THEN
				SET NEW.valor_custo_produto = NEW.valor_custo_produto_historico * ( ( 100 - NEW.preco_promocao ) / 100 );
				SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto_historico - ( NEW.valor_custo_produto_historico * ( NEW.preco_promocao / 100 ) );
			ELSE
				SET NEW.valor_custo_produto_historico = NEW.valor_custo_produto;
				SET NEW.valor_custo_produto = NEW.valor_custo_produto * ( ( 100 - NEW.preco_promocao ) / 100 );
				SET VALOR_CALCULO_PORCENTAGEM_ = NEW.valor_custo_produto ;

			END IF;

			IF( NEW.preco_promocao = 100 AND NEW.premio_pontos >= 100 ) THEN
				SET NEW.premio = 1;
			END IF;
		END IF;
	END IF;

    SET NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;

    IF (VALOR_CALCULO_PORCENTAGEM_ < CUSTO_MAX_APLICAR_TAXA_ML AND NEW.id NOT IN (82044, 82042, 99265, 93923)) THEN
        SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * COMISSAO_ML / 100, 2) + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2) + TAXA_PRODUTO_BARATO_ML;
    ELSE
        SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * COMISSAO_ML / 100, 2) + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2);
    END IF;

    IF (VALOR_CALCULO_PORCENTAGEM_ < CUSTO_MAX_APLICAR_TAXA_MS AND NEW.id NOT IN (82044, 82042, 99265, 93923)) THEN
        SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( COMISSAO_MS / ( 100 - COMISSAO_MS ) ) ) + TAXA_PRODUTO_BARATO_MS;
    ELSE
        SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( COMISSAO_MS / ( 100 - COMISSAO_MS ) ) );
    END IF;

	IF(OLD.promocao = 0 AND NEW.promocao = 1) THEN
         SET NEW.valor_venda_ms_historico = OLD.valor_venda_ms;
         SET NEW.valor_venda_ml_historico = OLD.valor_venda_ml;
	END IF;

	IF(
		( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.premio = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.promocao = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.preco_promocao = 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND COMISSAO_MS <= 0)
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.valor_venda_ms = 0 )
		OR ( NEW.valor_venda_ml_historico <= 0 AND NEW.valor_venda_ml <= 0 )
		)THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Opa, produto com valor zerado não, por favor verifique os valores do produto';
	END IF;
	IF( NEW.preco_promocao = 100 AND NEW.premio_pontos < 100 )THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'Opa, este produto não pode ser categorizado como premio, adicione um valor igual ou maior que 100 no campo premio_pontos';
	END IF;

	IF( ( NEW.preco_promocao > 0 AND NEW.preco_promocao <= 100) OR NEW.data_entrada <> OLD.data_entrada )THEN
		SET NEW.data_alteracao = NOW();
	END IF;

	IF(OLD.bloqueado = 0 AND
	   NEW.bloqueado = 1 AND
	   (EXISTS(SELECT 1 FROM estoque_grade
	           WHERE estoque_grade.id_produto = NEW.id AND
	           estoque_grade.estoque > 0))) THEN
	   signal sqlstate '45000' set MESSAGE_TEXT = 'Esse produto ainda não pode ser bloquado porque possui estoque';
	END IF;

    IF (OLD.valor_custo_produto <> NEW.valor_custo_produto) THEN
        SET NEW.data_atualizou_valor_custo = NOW();
    END IF;

	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'data_alteracao', OLD.data_alteracao, NEW.data_alteracao );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_custo_produto', OLD.valor_custo_produto, NEW.valor_custo_produto );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'preco_promocao', OLD.preco_promocao, NEW.preco_promocao );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'promocao', OLD.promocao, NEW.promocao );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'premio', OLD.premio, NEW.premio );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_venda_ms_historico', OLD.valor_venda_ms_historico, NEW.valor_venda_ms_historico );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_venda_ml_historico', OLD.valor_venda_ml_historico, NEW.valor_venda_ml_historico );
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_custo_produto_historico', OLD.valor_custo_produto_historico, NEW.valor_custo_produto_historico );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_venda_ms', OLD.valor_venda_ms, NEW.valor_venda_ms );
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_venda_sem_comissao', OLD.valor_venda_sem_comissao, NEW.valor_venda_sem_comissao );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_venda_ml', OLD.valor_venda_ml, NEW.valor_venda_ml );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'data_atualizou_valor_custo', OLD.data_atualizou_valor_custo, NEW.data_atualizou_valor_custo );
	CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'data_up', OLD.data_up, NEW.data_up );
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'id_fornecedor', OLD.id_fornecedor, NEW.id_fornecedor );
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_custo_produto_fornecedor', OLD.valor_custo_produto_fornecedor, NEW.valor_custo_produto_fornecedor );

	SET NEW.promocao = if(NEW.preco_promocao > 0,1,0);
END//

DELIMITER ;
