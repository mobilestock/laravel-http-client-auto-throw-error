<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MobileStock\helper\ConversorArray;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\ReputacaoFornecedoresService;

/**
 * @property int $id
 * @property int $id_produto
 * @property int $pontuacao_avaliacoes
 * @property int $pontuacao_seller
 * @property int $pontuacao_fullfillment
 * @property int $quantidade_vendas
 * @property int $pontuacao_devolucao_normal
 * @property int $pontuacao_devolucao_defeito
 * @property int $pontuacao_cancelamento
 * @property int $atraso_separacao
 * @property int $total
 * @property float $total_normalizado
 */
class ProdutosPontuacoes extends Model
{
    const QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS = 15;
    protected $table = 'produtos_pontuacoes';
    protected $fillable = [
        'id_produto',
        'pontuacao_avaliacoes',
        'pontuacao_seller',
        'pontuacao_fullfillment',
        'quantidade_vendas',
        'pontuacao_devolucao_normal',
        'pontuacao_devolucao_defeito',
        'pontuacao_cancelamento',
        'atraso_separacao',
        'total',
        'total_normalizado',
    ];

    public static function removeItensInvalidos(): void
    {
        DB::delete(
            "DELETE produtos_pontuacoes
            FROM produtos_pontuacoes
            INNER JOIN produtos ON produtos.id = produtos_pontuacoes.id_produto
            WHERE produtos.bloqueado = 1
                OR (
                    produtos.fora_de_linha = 1
                    AND (
                        SELECT SUM(estoque_grade.estoque)
                        FROM estoque_grade
                        WHERE estoque_grade.id_produto = produtos_pontuacoes.id_produto
                    ) = 0
                )
                OR NOT EXISTS(
                    SELECT 1
                    FROM publicacoes_produtos
                    INNER JOIN publicacoes ON publicacoes.id = publicacoes_produtos.id_publicacao
                    WHERE publicacoes_produtos.id_produto = produtos_pontuacoes.id_produto
                        AND publicacoes_produtos.situacao = 'CR'
                        AND publicacoes.situacao = 'CR'
                        AND publicacoes.tipo_publicacao = 'AU'
                )"
        );
    }

    public static function geraNovosProdutos(): void
    {
        DB::insert(
            "INSERT INTO produtos_pontuacoes(produtos_pontuacoes.id_produto)
            SELECT produtos.id
            FROM produtos
            LEFT JOIN produtos_pontuacoes ON produtos_pontuacoes.id_produto = produtos.id
            WHERE produtos.bloqueado = 0
                AND produtos_pontuacoes.id IS NULL
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

    public static function atualizaDadosProdutos(): array
    {
        $metadados = ConfiguracaoService::buscaFatoresPontuacaoProdutos();
        $qtdProdutos = DB::selectOneColumn('SELECT COUNT(produtos_pontuacoes.id) FROM produtos_pontuacoes;');

        if (!$qtdProdutos) {
            throw new Exception('Nenhum produto na tabela produtos_pontuacoes');
        }

        $idsProdutosAtualizados = [];
        $diasSeparacaoAtrasada = ConfiguracaoService::buscaDiasAtrasoParaSeparacao();
        $quantidadeCiclos = self::QUANTIDADE_CICLOS_ATUALIZAR_PRODUTOS_PONTOS;
        $limit = ceil($qtdProdutos / $quantidadeCiclos);
        $sqlCriterioAfetarReputacao = ReputacaoFornecedoresService::sqlCriterioCancelamentoAfetarReputacao();

        for ($ciclo = 0; $ciclo < $quantidadeCiclos; $ciclo++) {
            DB::beginTransaction();
            $idsProdutos = DB::selectColumns(
                "SELECT produtos_pontuacoes.id_produto
                FROM produtos_pontuacoes
                WHERE produtos_pontuacoes.data_atualizacao IS NULL
                    OR DATE(produtos_pontuacoes.data_atualizacao) <> DATE(NOW())
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
                "UPDATE produtos_pontuacoes
                SET produtos_pontuacoes.data_atualizacao = NOW(),
                    produtos_pontuacoes.pontuacao_avaliacoes = COALESCE(
                        (
                            SELECT SUM(IF(avaliacao_produtos.qualidade = 5,
                                :avaliacao_5_estrelas,
                                :avaliacao_4_estrelas
                            ))
                            FROM avaliacao_produtos
                            WHERE avaliacao_produtos.id_produto = produtos_pontuacoes.id_produto
                                AND avaliacao_produtos.qualidade IN (4, 5)
                                AND avaliacao_produtos.origem = 'ML'
                                AND avaliacao_produtos.data_avaliacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_avaliacoes DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontuacoes.pontuacao_seller = COALESCE(
                        (
                            SELECT CASE reputacao_fornecedores.reputacao
                                WHEN 'MELHOR_FABRICANTE' THEN :reputacao_melhor_fabricante
                                WHEN 'EXCELENTE' THEN :reputacao_excelente
                                WHEN 'REGULAR' THEN :reputacao_regular
                                WHEN 'RUIM' THEN :reputacao_ruim
                            END
                            FROM reputacao_fornecedores
                            INNER JOIN produtos ON produtos.id_fornecedor = reputacao_fornecedores.id_colaborador
                            WHERE produtos.id = produtos_pontuacoes.id_produto
                            LIMIT 1
                        ),
                        0
                    ),
                    produtos_pontuacoes.pontuacao_fullfillment = COALESCE((
                        SELECT produtos.permitido_reposicao * :pontuacao_fulfillment
                        FROM produtos
                        WHERE produtos.id = produtos_pontuacoes.id_produto
                    ), 0),
                    produtos_pontuacoes.quantidade_vendas = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :pontuacao_venda
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontuacoes.id_produto
                                AND logistica_item.situacao IN ('PE', 'SE', 'CO')
                                AND logistica_item.data_criacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_vendas DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontuacoes.pontuacao_devolucao_normal = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :devolucao_normal
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontuacoes.id_produto
                                AND logistica_item.situacao = 'DE'
                                AND logistica_item.data_atualizacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_trocas_normais DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontuacoes.pontuacao_devolucao_defeito = COALESCE(
                        (
                            SELECT COUNT(logistica_item.id) * :devolucao_defeito
                            FROM logistica_item
                            INNER JOIN pedido_item_meu_look ON pedido_item_meu_look.uuid = logistica_item.uuid_produto
                            WHERE logistica_item.id_produto = produtos_pontuacoes.id_produto
                                AND logistica_item.situacao = 'DF'
                                AND logistica_item.data_atualizacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_trocas_defeito DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontuacoes.pontuacao_cancelamento = COALESCE(
                        (
                            SELECT COUNT(pedido_item_meu_look.id) * :pontuacao_cancelamento
                            FROM pedido_item_meu_look
                            INNER JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = pedido_item_meu_look.uuid
                                AND logistica_item_data_alteracao.situacao_nova = 'RE'
                            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
                            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.uuid_produto = logistica_item_data_alteracao.uuid_produto
                                AND transacao_financeiras_produtos_itens.tipo_item = 'PR'
                            INNER JOIN colaboradores AS `fornecedor_colaboradores` ON fornecedor_colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
                            WHERE pedido_item_meu_look.id_produto = produtos_pontuacoes.id_produto
                                AND $sqlCriterioAfetarReputacao IS NOT NULL
                                AND logistica_item_data_alteracao.data_criacao >= DATE(
                                    DATE_SUB(NOW(), INTERVAL :dias_mensurar_cancelamento DAY)
                                )
                        ),
                        0
                    ),
                    produtos_pontuacoes.atraso_separacao = COALESCE(
                        (
                            SELECT :atraso_separacao
                            FROM logistica_item
                            WHERE logistica_item.id_produto = produtos_pontuacoes.id_produto
                                AND logistica_item.situacao = 'PE'
                                AND DATE(logistica_item.data_criacao) <= CURDATE() -
                                    INTERVAL :dias_atraso_para_separacao DAY
                            LIMIT 1
                        ),
                        0
                    )
                WHERE produtos_pontuacoes.id_produto IN ($binds);",
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
                    'Row count não bateu com o tamanho do array de ids ao atualizar dados produtos pontos'
                );
            }

            $idsProdutosAtualizados = array_merge($idsProdutosAtualizados, $idsProdutos);

            DB::commit();
        }

        return $idsProdutosAtualizados;
    }

    public static function calcularTotalNormalizado(): void
    {
        $rowCount = DB::update(
            "UPDATE produtos_pontuacoes
            SET produtos_pontuacoes.total_normalizado = COALESCE((
                SELECT ranking_produtos.posicao *
                    1 /
                    COALESCE((SELECT COUNT(produtos_pontuacoes.id_produto) FROM produtos_pontuacoes), 0)
                FROM (
                    SELECT RANK()
                    OVER (ORDER BY produtos_pontuacoes.total) posicao,
                        produtos_pontuacoes.id_produto
                        FROM produtos_pontuacoes
                ) ranking_produtos
                WHERE ranking_produtos.id_produto = produtos_pontuacoes.id_produto
            ), 0)"
        );
        if ($rowCount === 0) {
            throw new Exception('Não foi possível atualizar totais normalizados');
        }
    }
}
