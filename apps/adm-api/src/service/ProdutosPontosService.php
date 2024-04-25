<?php

namespace MobileStock\service;

use Exception;
use PDO;

class ProdutosPontosService
{
    const QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS = 15;

    public function removeItensInvalidos(PDO $conexao): void
    {
        $conexao->exec(
            "DELETE produtos_pontos
            FROM produtos_pontos
            INNER JOIN produtos ON produtos.id = produtos_pontos.id_produto
            WHERE produtos.bloqueado = 1
                OR (
                    produtos.fora_de_linha = 1
                    AND (
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos_pontos.id_produto
                    ) = 0
                )
                OR NOT EXISTS(
                    SELECT 1
                    FROM publicacoes_produtos
                    INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                    WHERE publicacoes_produtos.id_produto = produtos_pontos.id_produto
                        AND publicacoes_produtos.situacao = 'CR'
                        AND publicacoes.situacao = 'CR'
                        AND publicacoes.tipo_publicacao = 'AU'
                )"
        );
    }

    public function geraNovosProdutos(PDO $conexao): void
    {
        $conexao->exec(
            "INSERT INTO produtos_pontos(produtos_pontos.id_produto)
            SELECT produtos.id
            FROM produtos
            LEFT JOIN produtos_pontos ON produtos_pontos.id_produto = produtos.id
            WHERE produtos.bloqueado = 0
                AND produtos_pontos.id IS NULL
                AND (
                    produtos.fora_de_linha = 0
                    OR (
                        produtos.fora_de_linha = 1
                        AND (
                            SELECT SUM(estoque_grade.estoque)
                            FROM estoque_grade
                            WHERE estoque_grade.id_produto = produtos.id
                        ) > 0
                    )
                )
                AND EXISTS (
                    SELECT 1
                    FROM publicacoes_produtos
                    INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                    WHERE publicacoes_produtos.id_produto = produtos.id
                        AND publicacoes_produtos.situacao = 'CR'
                        AND publicacoes.situacao = 'CR'
                        AND publicacoes.tipo_publicacao = 'AU'
                )"
        );
    }

    public function atualizaDadosProdutos(PDO $conexao): array
    {
        $metadados = ProdutosPontosMetadadosService::buscaMetadados(
            $conexao,
            ProdutosPontosMetadadosService::GRUPO_PRODUTOS_PONTOS
        );
        $metadados = array_reduce($metadados, function ($inicial, $atual) {
            return array_merge($inicial, [ $atual['chave'] => $atual['valor'] ]);
        }, []);

        $qtdProdutos = $conexao->query(
            "SELECT COUNT(produtos_pontos.id) FROM produtos_pontos"
        )->fetchColumn();

        if (!$qtdProdutos) throw new Exception("Nenhum produto na tabela produtos_pontos");

        $idsProdutosAtualizados = [];
        $quantidadeCiclos = self::QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS;
        $limit = ceil($qtdProdutos / $quantidadeCiclos);

        for ($ciclo = 0; $ciclo < $quantidadeCiclos; $ciclo++) {
            try {
                $conexao->beginTransaction();
                $idsProdutos = $conexao->query(
                    "SELECT produtos_pontos.id_produto
                    FROM produtos_pontos
                    WHERE produtos_pontos.atualizado_em IS NULL
                        OR DATE(produtos_pontos.atualizado_em) <> DATE(NOW())
                    ORDER BY RAND()
                    LIMIT $limit"
                )->fetchAll(PDO::FETCH_COLUMN);

                if (empty($idsProdutos)) break;

                $stmt = $conexao->query(
                    "UPDATE produtos_pontos
                    SET produtos_pontos.atualizado_em = NOW(),
                        produtos_pontos.pontuacao_avaliacoes = COALESCE(
                            (
                                SELECT SUM(IF(avaliacao_produtos.qualidade = 5,
                                    {$metadados['AVALIACAO_5_ESTRELAS']},
                                    {$metadados['AVALIACAO_4_ESTRELAS']}
                                ))
                                FROM avaliacao_produtos
                                WHERE avaliacao_produtos.id_produto = produtos_pontos.id_produto
                                    AND avaliacao_produtos.qualidade IN (4, 5)
                                    AND avaliacao_produtos.origem = 'ML'
                                    AND avaliacao_produtos.data_avaliacao >= DATE(
                                        DATE_SUB(NOW(), INTERVAL {$metadados['DIAS_MENSURAR_AVALIACOES']} DAY)
                                    )
                            ),
                            0
                        ),
                        produtos_pontos.pontuacao_seller = COALESCE(
                            (
                                SELECT CASE reputacao_fornecedores.reputacao
                                    WHEN 'MELHOR_FABRICANTE' THEN {$metadados['REPUTACAO_MELHOR_FABRICANTE']}
                                    WHEN 'EXCELENTE' THEN {$metadados['REPUTACAO_EXCELENTE']}
                                    WHEN 'REGULAR' THEN {$metadados['REPUTACAO_REGULAR']}
                                    WHEN 'RUIM' THEN {$metadados['REPUTACAO_RUIM']}
                                END
                                FROM reputacao_fornecedores
                                INNER JOIN produtos ON produtos.id_fornecedor = reputacao_fornecedores.id_colaborador
                                WHERE produtos.id = produtos_pontos.id_produto
                                LIMIT 1
                            ),
                            0
                        ),
                        produtos_pontos.pontuacao_fullfillment = COALESCE((
                            SELECT produtos.permitido_reposicao * {$metadados['POSSUI_FULLFILLMENT']}
                            FROM produtos
                            WHERE produtos.id = produtos_pontos.id_produto
                        ), 0),
                        produtos_pontos.quantidade_vendas = COALESCE(
                            (
                                SELECT COUNT(logistica_item.id) * {$metadados['PONTUACAO_VENDA']}
                                FROM logistica_item
                                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                                WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                    AND logistica_item.situacao IN ('PE', 'SE', 'CO')
                                    AND logistica_item.data_criacao >= DATE(
                                        DATE_SUB(NOW(), INTERVAL {$metadados['DIAS_MENSURAR_VENDAS']} DAY)
                                    )
                            ),
                            0
                        ),
                        produtos_pontos.pontuacao_devolucao_normal = COALESCE(
                            (
                                SELECT COUNT(logistica_item.id) * {$metadados['DEVOLUCAO_NORMAL']}
                                FROM logistica_item
                                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                                WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                    AND logistica_item.situacao = 'DE'
                                    AND logistica_item.data_atualizacao >= DATE(
                                        DATE_SUB(NOW(), INTERVAL {$metadados['DIAS_MENSURAR_TROCAS_NORMAIS']} DAY)
                                    )
                            ),
                            0
                        ),
                        produtos_pontos.pontuacao_devolucao_defeito = COALESCE(
                            (
                                SELECT COUNT(logistica_item.id) * {$metadados['DEVOLUCAO_DEFEITO']}
                                FROM logistica_item
                                INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                                WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                    AND logistica_item.situacao = 'DF'
                                    AND logistica_item.data_atualizacao >= DATE(
                                        DATE_SUB(NOW(), INTERVAL {$metadados['DIAS_MENSURAR_TROCAS_DEFEITO']} DAY)
                                    )
                            ),
                            0
                        ),
                        produtos_pontos.cancelamento_automatico = COALESCE(
                            (
                                SELECT COUNT(pedido_item_meu_look.id) * {$metadados['CANCELAMENTO_AUTOMATICO']}
                                FROM pedido_item_meu_look
                                INNER JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = pedido_item_meu_look.uuid
                                    AND logistica_item_data_alteracao.id_usuario = 2
                                    AND logistica_item_data_alteracao.situacao_nova = 'RE'
                                WHERE pedido_item_meu_look.id_produto = produtos_pontos.id_produto
                                    AND logistica_item_data_alteracao.data_criacao >= DATE(
                                        DATE_SUB(NOW(), INTERVAL {$metadados['DIAS_MENSURAR_CANCELAMENTO']} DAY)
                                    )
                            ),
                            0
                        ),
                        produtos_pontos.atraso_separacao = COALESCE(
                            (
                                SELECT {$metadados['ATRASO_SEPARACAO']}
                                FROM logistica_item
                                WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                    AND logistica_item.situacao = 'PE'
                                    AND DATE(logistica_item.data_criacao) <= CURDATE() -
                                        INTERVAL COALESCE((SELECT dias_atraso_para_separacao FROM configuracoes LIMIT 1), 0) DAY
                                LIMIT 1
                            ),
                            0
                        )
                    WHERE produtos_pontos.id_produto IN (" . implode(',', $idsProdutos) . ")"
                );

                if ($stmt->rowCount() !== sizeof($idsProdutos))
                    throw new Exception(
                        "Row count não bateu com o tamanho do array de ids ao atualizar dados produtos pontos (" .
                        $stmt->rowCount() . "/" . sizeof($idsProdutos) . ")"
                    );

                $idsProdutosAtualizados = array_merge($idsProdutosAtualizados, $idsProdutos);

                $conexao->commit();
            } catch (Exception $exception) {
                $conexao->rollBack();
                throw new Exception(
                    "Erro ao atualizar dados produtos pontos, {$ciclo}/{$quantidadeCiclos} ciclos atualizados",
                    $exception->getCode(),
                    $exception
                );
            }
        }

        return $idsProdutosAtualizados;
    }

    public function calcularTotalNormalizado(PDO $conexao): void
    {
        $stmt = $conexao->query(
            "UPDATE produtos_pontos
            SET produtos_pontos.total_normalizado = COALESCE((
                SELECT ranking_produtos.posicao *
                    1 /
                    COALESCE((SELECT COUNT(produtos_pontos.id_produto) FROM produtos_pontos), 0)
                FROM (
                    SELECT RANK()
                    OVER (ORDER BY produtos_pontos.total) posicao,
                        produtos_pontos.id_produto
                        FROM produtos_pontos
                ) ranking_produtos
                WHERE ranking_produtos.id_produto = produtos_pontos.id_produto
            ), 0)"
        );
        if ($stmt->rowCount() === 0)
            throw new Exception("Não foi possível atualizar totais normalizados");
    }

}