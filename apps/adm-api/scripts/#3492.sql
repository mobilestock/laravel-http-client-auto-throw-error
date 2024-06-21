TRUNCATE TABLE produtos_video;

RENAME TABLE produtos_video TO produtos_videos;

ALTER TABLE produtos_videos
    MODIFY COLUMN id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    DROP COLUMN sequencia,
    ADD id_produto INT NOT NULL,
    ADD id_usuario INT NOT NULL,
    ADD data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE `produtos_videos`
	ADD CONSTRAINT `FK_id_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `produtos_foto`
	DROP COLUMN `foto_calcada`;

ALTER TABLE produtos_categorias
    ADD id_usuario INT NOT NULL,
    ADD data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP();

ALTER TABLE produtos
    CHANGE COLUMN usuario id_usuario INT NOT NULL,
    MODIFY COLUMN premio INT NOT NULL DEFAULT 0 COMMENT 'Depreciado: https://github.com/mobilestock/backend/issues/370',
    MODIFY COLUMN premio_pontos INT NOT NULL DEFAULT 0 COMMENT 'Depreciado: https://github.com/mobilestock/backend/issues/370'
    DROP COLUMN especial,
    DROP COLUMN grade_min,
    DROP COLUMN grade_max,
    DROP COLUMN id_colaborador_publicador_padrao,
    DROP INDEX `idx_produtos`,
	ADD INDEX `idx_produtos` (`id_linha`, `id_fornecedor`, `descricao`, `nome_comercial`, `bloqueado`, `preco_promocao`) USING BTREE;

DROP TABLE IF EXISTS promocoes

DROP TRIGGER IF EXISTS produtos_after_update;

DELIMITER //
CREATE TRIGGER `produtos_after_update`
AFTER UPDATE ON `produtos`
FOR EACH ROW
BEGIN
    IF(NEW.valor_custo_produto <> OLD.valor_custo_produto) THEN
        UPDATE pedido_item
        SET pedido_item.preco = NEW.valor_venda_ms
        WHERE pedido_item.id_produto = NEW.id AND pedido_item.premio = 0;

        SET @ID_PROMOCAO_TEMPORARIA = (
            SELECT catalogo_fixo.id
            FROM catalogo_fixo
            WHERE catalogo_fixo.id_produto = NEW.id
                AND catalogo_fixo.tipo = 'PROMOCAO_TEMPORARIA'
            LIMIT 1
        );

        SET @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA = COALESCE((
            SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA')
            FROM configuracoes
            LIMIT 1
        ), 30);

        IF (@ID_PROMOCAO_TEMPORARIA IS NOT NULL) THEN
            IF (NEW.preco_promocao < @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                UPDATE catalogo_fixo
                SET catalogo_fixo.expira_em = NOW()
                WHERE catalogo_fixo.id = @ID_PROMOCAO_TEMPORARIA;
                IF (ROW_COUNT() = 0) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao deletar promoção temporária';
                END IF;
            ELSE
                UPDATE catalogo_fixo
                SET catalogo_fixo.valor_venda_ms = NEW.valor_venda_ms,
                    catalogo_fixo.valor_venda_ml = NEW.valor_venda_ml
                WHERE catalogo_fixo.id = @ID_PROMOCAO_TEMPORARIA;
                IF (ROW_COUNT() = 0) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar promoção temporária';
                END IF;
            END IF;
        ELSE
            IF (NEW.preco_promocao >= @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                SET @REPUTACAO_FORNECEDOR = (
                    SELECT reputacao_fornecedores.reputacao
                    FROM reputacao_fornecedores
                    WHERE reputacao_fornecedores.id_colaborador = NEW.id_fornecedor
                    LIMIT 1
                );

                IF((@REPUTACAO_FORNECEDOR IS NULL OR @REPUTACAO_FORNECEDOR <> 'RUIM')
                    AND OLD.promocao = 0
                    AND NEW.promocao = 1
                ) THEN
                    IF(NEW.preco_promocao >= @PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA) THEN
                        INSERT INTO catalogo_fixo (
                            id_publicacao,
                            tipo,
                            expira_em,
                            id_publicacao_produto,
                            id_produto,
                            nome_produto,
                            valor_venda_ml,
                            valor_venda_ml_historico,
                            valor_venda_ms,
                            valor_venda_ms_historico,
                            possui_fulfillment,
                            foto_produto,
                            quantidade_acessos,
                            quantidade_vendida,
                            id_fornecedor
                        ) VALUES (
                            (
                                SELECT publicacoes_produtos.id_publicacao
                                FROM publicacoes_produtos
                                WHERE publicacoes_produtos.id_produto = NEW.id
                                    AND publicacoes_produtos.situacao = 'CR'
                                ORDER BY RAND(NEW.id)
                                LIMIT 1
                            ),
                            'PROMOCAO_TEMPORARIA',
                            NOW() + INTERVAL COALESCE((
                                SELECT JSON_VALUE(configuracoes.produtos_promocoes, '$.HORAS_DURACAO_PROMOCAO_TEMPORARIA')
                                FROM configuracoes
                                LIMIT 1
                            ), 24) HOUR,
                            (
                                SELECT publicacoes_produtos.id
                                FROM publicacoes_produtos
                                WHERE publicacoes_produtos.id_produto = NEW.id
                                    AND publicacoes_produtos.situacao = 'CR'
                                ORDER BY RAND(NEW.id)
                                LIMIT 1
                            ),
                            NEW.id,
                            LOWER(IF(LENGTH(NEW.nome_comercial) > 0, NEW.nome_comercial, NEW.descricao)),
                            NEW.valor_venda_ml,
                            NEW.valor_venda_ml_historico,
                            NEW.valor_venda_ms,
                            NEW.valor_venda_ms_historico,
                            EXISTS(
                                SELECT 1
                                FROM estoque_grade
                                WHERE estoque_grade.id_produto = NEW.id
                                    AND estoque_grade.id_responsavel = 1
                                LIMIT 1
                            ),
                            (
                                SELECT produtos_foto.caminho
                                FROM produtos_foto
                                WHERE produtos_foto.id = NEW.id
                                ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                                LIMIT 1
                            ),
                            0,
                            (
                                SELECT COUNT(pedido_item_meu_look.uuid)
                                FROM pedido_item_meu_look
                                WHERE pedido_item_meu_look.id_produto = NEW.id
                                    AND pedido_item_meu_look.situacao = 'PA'
                            ),
                            NEW.id_fornecedor
                        );
                    END IF;
                END IF;
            END IF;
        END IF;
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS produtos_before_update;

DELIMITER //
CREATE TRIGGER `produtos_before_update` BEFORE UPDATE ON `produtos` FOR EACH ROW
BEGIN
	DECLARE VALOR_CALCULO_PORCENTAGEM_ DECIMAL(10,2) DEFAULT 0;

	IF( NEW.preco_promocao < 0 OR NEW.preco_promocao > 100 ) THEN
		signal sqlstate '45000' set MESSAGE_TEXT = 'A porcentagem de promoção deve ter um valor valido de 0 a 100';
	END IF;

	IF(NEW.data_primeira_entrada IS NULL AND (OLD.data_entrada <> NEW.data_entrada ))THEN
		SET NEW.data_primeira_entrada = NOW();
	END IF;

	IF ( OLD.valor_custo_produto <> NEW.valor_custo_produto AND NEW.preco_promocao > 0 ) THEN
		SET NEW.preco_promocao = 0;
	END IF;

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

			if( OLD.preco_promocao >= 1 AND OLD.valor_custo_produto = NEW.valor_custo_produto ) THEN
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

	IF(NEW.porcentagem_comissao_ms >= 0 )THEN
		SET NEW.valor_venda_ms = VALOR_CALCULO_PORCENTAGEM_ * ( 1 + ( NEW.porcentagem_comissao_ms / ( 100 - NEW.porcentagem_comissao_ms ) ) );
	END IF;

	SET NEW.valor_venda_ml = VALOR_CALCULO_PORCENTAGEM_ + ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ml / 100, 2)
														+ ROUND(VALOR_CALCULO_PORCENTAGEM_ * NEW.porcentagem_comissao_ponto_coleta / 100, 2),
        NEW.valor_venda_sem_comissao = VALOR_CALCULO_PORCENTAGEM_;


	IF(OLD.promocao = 0 AND NEW.promocao = 1) THEN
         SET NEW.valor_venda_ms_historico = OLD.valor_venda_ms;
         SET NEW.valor_venda_ml_historico = OLD.valor_venda_ml;
	END IF;

	IF(
		( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.premio = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.promocao = 0 )
		OR ( NEW.valor_custo_produto <= 0 AND NEW.preco_promocao = 0 )
		OR ( NEW.valor_custo_produto_historico <= 0 AND NEW.valor_custo_produto <= 0 AND NEW.porcentagem_comissao_ms <= 0)
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
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'porcentagem_comissao_ms', OLD.porcentagem_comissao_ms, NEW.porcentagem_comissao_ms );
    CALL salva_log_alteracao_produtos( NEW.id_usuario, NEW.id, 'valor_custo_produto_fornecedor', OLD.valor_custo_produto_fornecedor, NEW.valor_custo_produto_fornecedor );

	SET NEW.promocao = if(NEW.preco_promocao > 0,1,0);
END//
DELIMITER ;

DROP TRIGGER IF EXISTS produtos_foto_after_insert;

DELIMITER //
CREATE TRIGGER `produtos_foto_after_insert` AFTER INSERT ON `produtos_foto` FOR EACH ROW BEGIN
	UPDATE produtos SET produtos.data_entrada = NOW() WHERE produtos.id = NEW.id;
END//
DELIMITER ;
