<?php

namespace MobileStock\service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MobileStock\helper\ConversorArray;

class ProdutosPontosService
{
    const QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS = 15;

    public function removeItensInvalidos(): void
    {
        DB::delete(
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

    public function geraNovosProdutos(): void
    {
        DB::insert(
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

    public function atualizaDadosProdutos(): array
    {
        $metadados = ConfiguracaoService::buscaFatoresPontuacaoProdutos();
        $qtdProdutos = DB::selectOneColumn('SELECT COUNT(produtos_pontos.id) FROM produtos_pontos;');

        if (!$qtdProdutos) {
            throw new Exception('Nenhum produto na tabela produtos_pontos');
        }

        $idsProdutosAtualizados = [];
        $diasSeparacaoAtrasada = ConfiguracaoService::buscaDiasAtrasoParaSeparacao();
        $quantidadeCiclos = self::QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS;
        $limit = ceil($qtdProdutos / $quantidadeCiclos);
        $sqlCriterioAfetarReputacao = ReputacaoFornecedoresService::sqlCriterioAfetarReputacao();

        for ($ciclo = 0; $ciclo < $quantidadeCiclos; $ciclo++) {
            DB::beginTransaction();
            $idsProdutos = DB::selectColumns(
                "SELECT produtos_pontos.id_produto
                FROM produtos_pontos
                WHERE produtos_pontos.data_atualizacao IS NULL
                    OR DATE(produtos_pontos.data_atualizacao) <> DATE(NOW())
                ORDER BY RAND()
                LIMIT $limit"
            );

            if (empty($idsProdutos)) {
                break;
            }

            [$binds, $valores] = ConversorArray::criaBindValues($idsProdutos, 'id_produto');
            $valores['dias_atraso_para_separacao'] = $diasSeparacaoAtrasada;
            $valores = array_merge($valores, $metadados);

            $rowCount = DB::update(
                "UPDATE produtos_pontos
                SET produtos_pontos.data_atualizacao = NOW(),
                    produtos_pontos.pontuacao_avaliacoes = COALESCE(
                        (
                            SELECT SUM(IF(avaliacao_produtos.qualidade = 5,
                                :avaliacao_5_estrelas,
                                :avaliacao_4_estrelas
                            ))
                            FROM avaliacao_produtos
                            WHERE avaliacao_produtos.id_produto = produtos_pontos.id_produto
                                AND avaliacao_produtos.qualidade IN (4, 5)
                                AND avaliacao_produtos.origem = 'ML'
                                AND avaliacao_produtos.data_avaliacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_avaliacoes DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontos.pontuacao_seller = COALESCE(
                        (
                            SELECT CASE reputacao_fornecedores.reputacao
                                WHEN 'MELHOR_FABRICANTE' THEN :reputacao_melhor_fabricante
                                WHEN 'EXCELENTE' THEN :reputacao_excelente
                                WHEN 'REGULAR' THEN :reputacao_regular
                                WHEN 'RUIM' THEN :reputacao_ruim
                            END
                            FROM reputacao_fornecedores
                            INNER JOIN produtos ON produtos.id_fornecedor = reputacao_fornecedores.id_colaborador
                            WHERE produtos.id = produtos_pontos.id_produto
                            LIMIT 1
                        ),
                        0
                    ),
                    produtos_pontos.pontuacao_fullfillment = COALESCE((
                        SELECT produtos.permitido_reposicao * :possui_fulfillment
                        FROM produtos
                        WHERE produtos.id = produtos_pontos.id_produto
                    ), 0),
                    produtos_pontos.quantidade_vendas = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :pontuacao_venda
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                AND logistica_item.situacao IN ('PE', 'SE', 'CO')
                                AND logistica_item.data_criacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_vendas DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontos.pontuacao_devolucao_normal = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :devolucao_normal
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                AND logistica_item.situacao = 'DE'
                                AND logistica_item.data_atualizacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_trocas_normais DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontos.pontuacao_devolucao_defeito = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :devolucao_defeito
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                AND logistica_item.situacao = 'DF'
                                AND logistica_item.data_atualizacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_trocas_defeito DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontos.pontuacao_cancelamento = COALESCE(
                        (
                            SELECT COUNT(pedido_item_meu_look.id) * :pontuacao_cancelamento
                            FROM pedido_item_meu_look
                            INNER JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = pedido_item_meu_look.uuid
                                AND logistica_item_data_alteracao.situacao_nova = 'RE'
                            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
                            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = logistica_item_data_alteracao.uuid_produto
                                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                            WHERE pedido_item_meu_look.id_produto = produtos_pontos.id_produto
                                AND $sqlCriterioAfetarReputacao
                                AND logistica_item_data_alteracao.data_criacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_cancelamento DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontos.atraso_separacao = COALESCE(
                        (
                            SELECT :atraso_separacao
                            FROM logistica_item
                            WHERE logistica_item.id_produto = produtos_pontos.id_produto
                                AND logistica_item.situacao = 'PE'
                                AND DATE(logistica_item.data_criacao) <= CURDATE() -
                                    INTERVAL :dias_atraso_para_separacao DAY
                            LIMIT 1
                        ),
                        0
                    )
                WHERE produtos_pontos.id_produto IN ($binds);",
                $valores
            );

            $qtdProdutos = count($idsProdutos);
            if ($rowCount !== $qtdProdutos) {
                Log::withContext([
                    'linhas_alteradas' => $rowCount,
                    'qtd_produtos' => $qtdProdutos,
                    'ciclo' => $ciclo,
                    'quantidade_ciclos' => $quantidadeCiclos,
                ]);

                throw new Exception(
                    "Row count não bateu com o tamanho do array de ids ao atualizar dados produtos pontos ($rowCount/$qtdProdutos)"
                );
            }

            $idsProdutosAtualizados = array_merge($idsProdutosAtualizados, $idsProdutos);

            DB::commit();
        }

        return $idsProdutosAtualizados;
    }

    public function calcularTotalNormalizado(): void
    {
        $rowCount = DB::update(
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
        if ($rowCount === 0) {
            throw new Exception('Não foi possível atualizar totais normalizados');
        }
    }
}
