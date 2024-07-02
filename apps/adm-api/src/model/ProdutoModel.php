<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\DB;
use MobileStock\service\ConfiguracaoService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @issue: https://github.com/mobilestock/backend/issues/131
 *
 * @property int $id
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class ProdutoModel extends Model
{
    protected $table = 'produtos';
    protected $fillable = ['permitido_reposicao', 'eh_moda', 'valor_custo_produto'];
    protected $casts = [
        'eh_moda' => 'boolean',
    ];
    public $timestamps = false;

    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE = 82044;
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const ID_PRODUTO_FRETE_EXPRESSO = 82042;

    public static function buscarProdutoPorId(int $idProduto): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos.id,
                produtos.eh_moda,
                produtos.permitido_reposicao
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function buscaEstoqueFulfillmentParado(): array
    {
        $configuracoes = ConfiguracaoService::buscaConfiguracoesJobGerenciaEstoqueParado();
        $qtdDiasParado = $configuracoes['qtd_maxima_dias'];
        $produtos = DB::select(
            "SELECT
                estoque_grade.id_produto,
                SUM(estoque_grade.estoque) AS quantidade_estoque,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`,
                DATE_FORMAT(_logistica_item.data, '%d/%m/%Y %H:%i') AS `data_ultima_venda`,
                DATE_FORMAT(_log_estoque_movimentacao.data, '%d/%m/%Y %H:%i') AS `data_ultima_entrada`,
                colaboradores.telefone,
                produtos.nome_comercial,
                produtos.valor_custo_produto,
                DATE(GREATEST(
                    COALESCE(_logistica_item.data, 0),
                    _log_estoque_movimentacao.data
                )) <= CURRENT_DATE() - INTERVAL :dias_baixar_preco DAY AS `deve_baixar_preco`
            FROM estoque_grade
            INNER JOIN produtos ON produtos.id_fornecedor NOT IN (12, 6984)
                AND produtos.id = estoque_grade.id_produto
                AND produtos.valor_custo_produto > 1
            INNER JOIN colaboradores ON colaboradores.id = produtos.id_fornecedor
            INNER JOIN (
                SELECT
                    log_estoque_movimentacao.id_produto,
                    MAX(log_estoque_movimentacao.data) AS `data`
                FROM log_estoque_movimentacao
                WHERE log_estoque_movimentacao.tipo_movimentacao = 'E'
                    AND log_estoque_movimentacao.id_responsavel_estoque = 1
                    AND log_estoque_movimentacao.oldEstoque = 0
                GROUP BY log_estoque_movimentacao.id_produto
            ) AS `_log_estoque_movimentacao` ON _log_estoque_movimentacao.id_produto = estoque_grade.id_produto
            LEFT JOIN (
                SELECT
                    logistica_item.id_produto,
                    MAX(logistica_item.data_criacao) AS `data`
                FROM logistica_item
                GROUP BY logistica_item.id_produto
            ) AS `_logistica_item` ON _logistica_item.id_produto = estoque_grade.id_produto
            WHERE estoque_grade.id_responsavel = 1
                AND estoque_grade.estoque > 0
                AND DATE(GREATEST(
                    COALESCE(_logistica_item.data, 0),
                    _log_estoque_movimentacao.data
                )) <= CURRENT_DATE() - INTERVAL :dias_parado DAY
            GROUP BY estoque_grade.id_produto;",
            [
                'dias_parado' => $qtdDiasParado,
                'dias_baixar_preco' => $qtdDiasParado + $configuracoes['dias_carencia'],
            ]
        );

        return $produtos;
    }
}
