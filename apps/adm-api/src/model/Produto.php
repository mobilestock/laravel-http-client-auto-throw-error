<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\ConversorArray;
use MobileStock\service\CatalogoFixoService;
use MobileStock\service\ConfiguracaoService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id
 * @property string $descricao
 * @property int $id_fornecedor
 * @property bool $bloqueado
 * @property int $id_linha
 * @property string $data_entrada
 * @property bool $promocao
 * @property int $grade
 * @property string $forma
 * @property string $nome_comercial
 * @property float $preco_promocao
 * @property float $valor_custo_produto
 * @property int $id_usuario
 * @property int $tipo_grade
 * @property string $sexo
 * @property string $cores
 * @property bool $fora_de_linha
 * @property string $embalagem
 * @property string $outras_informacoes
 * @property bool $permitido_reposicao
 * @property bool $eh_moda
 */
class Produto extends Model
{
    protected $fillable = [
        'descricao',
        'id_fornecedor',
        'bloqueado',
        'id_linha',
        'data_entrada',
        'promocao',
        'outras_informacoes',
        'forma',
        'embalagem',
        'nome_comercial',
        'preco_promocao',
        'valor_custo_produto',
        'id_usuario',
        'tipo_grade',
        'sexo',
        'cores',
        'fora_de_linha',
        'permitido_reposicao',
        'eh_moda',
    ];
    protected $casts = [
        'eh_moda' => 'boolean',
        'promocao' => 'boolean',
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
    public const PRECO_CUSTO_MINIMO = 0.5;

    protected static function boot()
    {
        parent::boot();

        self::updating(function (self $model) {
            if (!$model->isDirty('fora_de_linha') || $model->fora_de_linha) {
                return;
            }

            $ehExterno = DB::selectOneColumn(
                "SELECT EXISTS(
                SELECT 1
                FROM estoque_grade
                WHERE estoque_grade.id_responsavel <> 1
                    AND estoque_grade.estoque > 0
                    AND estoque_grade.id_produto = :id_produto
            ) `existe_estoque_externo`;",
                [':id_produto' => $model->id]
            );

            if (!$ehExterno) {
                return;
            }
            $linhasAfetadas = DB::update(
                "UPDATE estoque_grade SET
                estoque_grade.estoque = 0,
                estoque_grade.tipo_movimentacao = 'X',
                estoque_grade.descricao = 'Estoque zerado porque o produto foi colocado como fora de linha'
            WHERE estoque_grade.id_responsavel <> 1
                AND estoque_grade.estoque > 0
                AND estoque_grade.id_produto = :id_produto;",
                [':id_produto' => $model->id]
            );

            if ($linhasAfetadas < 1) {
                throw new Exception('Erro ao fazer movimentacao de estoque, reporte a equipe de T.I.');
            }
        });
    }

    public static function buscarProdutoPorId(int $idProduto): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos.id,
                produtos.descricao,
                produtos.id_fornecedor,
                produtos.bloqueado AS `bool_bloqueado`,
                produtos.id_linha,
                produtos.outras_informacoes,
                produtos.forma,
                produtos.embalagem,
                produtos.nome_comercial,
                produtos.valor_custo_produto,
                produtos.tipo_grade,
                produtos.sexo,
                produtos.cores,
                produtos.fora_de_linha AS `bool_fora_de_linha`,
                produtos.permitido_reposicao AS `bool_permitido_reposicao`,
                produtos.eh_moda
            FROM produtos
            WHERE produtos.id = :id_produto",
            [':id_produto' => $idProduto]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $produto;
    }

    public static function buscaEstoqueFulfillmentParado(bool $catalogo = false): array
    {
        $configuracoes = ConfiguracaoService::buscaFatoresEstoqueParado();
        $qtdDiasParado = $configuracoes['qtd_maxima_dias'];

        $binds = [
            'dias_parado' => $qtdDiasParado,
            'dias_baixar_preco' => $qtdDiasParado + $configuracoes['dias_carencia'],
        ];
        $select = ",
            produtos.nome_comercial,
            SUM(estoque_grade.estoque) AS `quantidade_estoque`,
            DATE_FORMAT(_logistica_item.data, '%d/%m/%Y %H:%i') AS `data_ultima_venda`,
            DATE_FORMAT(_log_estoque_movimentacao.data, '%d/%m/%Y %H:%i') AS `data_ultima_entrada`,
            colaboradores.telefone,
            produtos.valor_custo_produto,
            produtos.promocao AS `esta_em_promocao`,
            DATE(GREATEST(
                COALESCE(_logistica_item.data, 0),
                _log_estoque_movimentacao.data
            )) <= CURRENT_DATE() - INTERVAL :dias_baixar_preco DAY AS `deve_baixar_preco`";
        $whereIdsExistentes = '';

        if ($catalogo) {
            $binds['dias_parado'] = $binds['dias_baixar_preco'];
            unset($binds['dias_baixar_preco']);
            $select = ",
                produtos.nome_comercial AS `nome_produto`,
                produtos.id_fornecedor,
                produtos.valor_venda_ml,
                IF(produtos.promocao > 0, produtos.valor_venda_ml_historico, 0) AS `valor_venda_ml_historico`,
                produtos.valor_venda_ms,
                IF(produtos.promocao > 0, produtos.valor_venda_ms_historico, 0) AS `valor_venda_ms_historico`,
                SUM(estoque_grade.id_responsavel = 1) > 0 AS `possui_fulfillment`,
                produtos.quantidade_vendida";

            $idsProdutosParadosNoCatalogo = DB::selectColumns(
                "SELECT catalogo_fixo.id_produto
                FROM catalogo_fixo
                WHERE catalogo_fixo.tipo = :tipo_liquidacao",
                ['tipo_liquidacao' => CatalogoFixoService::TIPO_LIQUIDACAO]
            );

            if ($idsProdutosParadosNoCatalogo) {
                [$referenciasSql, $referenciasBind] = ConversorArray::criaBindValues($idsProdutosParadosNoCatalogo);
                $whereIdsExistentes = " AND estoque_grade.id_produto NOT IN ($referenciasSql)";
                $binds = array_merge($binds, $referenciasBind);
            }
        }

        $produtos = DB::select(
            "SELECT
                estoque_grade.id_produto,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.tipo_foto <> 'SM'
                        AND produtos_foto.id = estoque_grade.id_produto
                    ORDER BY produtos_foto.tipo_foto = 'MD' DESC
                    LIMIT 1
                ) AS `foto_produto`
                $select
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
                $whereIdsExistentes
            GROUP BY estoque_grade.id_produto
            HAVING `foto_produto` IS NOT NULL;",
            $binds
        );

        if ($catalogo) {
            $produtos = array_map(function (array $produto): array {
                $produto['tipo'] = CatalogoFixoService::TIPO_LIQUIDACAO;
                return $produto;
            }, $produtos);
        }

        return $produtos;
    }

    public static function buscarCatalogoLiquidacao(int $pagina, string $origem): array
    {
        $itensPorPagina = 100;
        $offset = ($pagina - 1) * $itensPorPagina;

        $chaveValor = 'produtos.valor_venda_ms';
        $chaveValorHistorico = 'produtos.valor_venda_ms_historico';
        if ($origem === Origem::ML) {
            $chaveValor = 'produtos.valor_venda_ml';
            $chaveValorHistorico = 'produtos.valor_venda_ml_historico';
        }

        $produtos = DB::select(
            "SELECT produtos.id `id_produto`,
                produtos.nome_comercial `nome_produto`,
                $chaveValor `valor_venda`,
                IF (produtos.promocao > 0, $chaveValorHistorico, 0) `valor_venda_historico`,
                produtos.quantidade_vendida,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = produtos.id
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) `foto_produto`,
                CONCAT(
                    '[',
                    GROUP_CONCAT(JSON_OBJECT(
                        'nome_tamanho', estoque_grade.nome_tamanho,
                        'estoque', estoque_grade.estoque
                    ) ORDER BY estoque_grade.sequencia),
                    ']'
                ) `json_grade_estoque`
            FROM catalogo_fixo
            INNER JOIN produtos ON produtos.id = catalogo_fixo.id_produto
            INNER JOIN estoque_grade ON estoque_grade.id_produto = catalogo_fixo.id_produto
                AND estoque_grade.id_responsavel = 1
                AND estoque_grade.estoque > 0
            WHERE produtos.bloqueado = 0
                AND catalogo_fixo.tipo = :tipo_liquidacao
            GROUP BY catalogo_fixo.id_produto
            ORDER BY SUM(estoque_grade.estoque) DESC
            LIMIT $itensPorPagina OFFSET $offset",
            [':tipo_liquidacao' => CatalogoFixoService::TIPO_LIQUIDACAO]
        );

        if (!empty($produtos)) {
            # @issue: https://github.com/mobilestock/backend/issues/397
            $produtos = array_map(function ($item) {
                $grades = ConversorArray::geraEstruturaGradeAgrupadaCatalogo($item['grade_estoque']);
                $categoria = (object) ['tipo' => '', 'valor' => ''];

                $valorParcela = CalculadorTransacao::calculaValorParcelaPadrao($item['valor_venda']);
                return [
                    'id_produto' => $item['id_produto'],
                    'nome' => $item['nome_produto'],
                    'preco' => $item['valor_venda'],
                    'preco_original' => $item['valor_venda_historico'],
                    'parcelas' => CalculadorTransacao::PARCELAS_PADRAO,
                    'valor_parcela' => $valorParcela,
                    'quantidade_vendida' => $item['quantidade_vendida'],
                    'foto' => $item['foto_produto'],
                    'grades' => $grades,
                    'categoria' => $categoria,
                ];
            }, $produtos);
        }

        return $produtos;
    }

    public static function desativaPromocaoMantemValores(int $idProduto): void
    {
        $produto = self::buscarProdutoPorId($idProduto);
        $valorCustoProduto = $produto->valor_custo_produto;
        $produto->preco_promocao = 0;
        $produto->save();

        $produto->refresh();

        $produto->valor_custo_produto = $valorCustoProduto;
        $produto->save();
    }
}
