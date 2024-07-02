ALTER TABLE catalogo_fixo
	DROP FOREIGN KEY catalogo_fixo.catalogo_fixo_ibfk_1,
	DROP INDEX catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao,
	DROP COLUMN catalogo_fixo.id_publicacao_produto;

ALTER TABLE catalogo_fixo
    CHANGE COLUMN catalogo_fixo.expira_em catalogo_fixo.data_expiracao TIMESTAMP NULL DEFAULT NULL AFTER data_criacao,
    CHANGE COLUMN catalogo_fixo.atualizado_em catalogo_fixo.data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao,
    CHANGE COLUMN catalogo_fixo.tipo catalogo_fixo.tipo ENUM('IMPULSIONAR','MELHOR_FABRICANTE','PROMOCAO_TEMPORARIA','VENDA_RECENTE','MELHOR_PONTUACAO','MODA_GERAL','MODA_20','MODA_40','MODA_60','MODA_80','MODA_100','LIQUIDACAO') AFTER data_expiracao;

ALTER TABLE configuracoes
    DROP COLUMN configuracoes.qtd_maxima_dias_produto_fulfillment_parado,
	ADD COLUMN configuracoes.json_configuracoes_job_gerencia_estoque_parado JSON
        DEFAULT '{"qtd_maxima_dias":365,"percentual_desconto":30,"dias_carencia":30}'
        AFTER produtos_promocoes;

DROP TRIGGER IF EXISTS `produtos_after_update`;

DELIMITER //
CREATE TRIGGER `produtos_after_update` AFTER UPDATE ON `produtos` FOR EACH ROW
BEGIN
		IF(NEW.especial <> OLD.especial OR NEW.id_fornecedor <> OLD.id_fornecedor OR NEW.bloqueado <> OLD.bloqueado) THEN
			UPDATE publicacoes
			SET publicacoes.id_colaborador = NEW.id_colaborador_publicador_padrao
			WHERE publicacoes.tipo_publicacao = 'AU' AND
				publicacoes.id IN (SELECT publicacoes_produtos.id_publicacao
									FROM publicacoes_produtos
									WHERE publicacoes_produtos.id_produto = NEW.id);
		END IF;

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
                    SET catalogo_fixo.data_expiracao = NOW()
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
END //
