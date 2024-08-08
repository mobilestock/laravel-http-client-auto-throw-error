<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MobileStock\service\Estoque\EstoqueGradeService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int id_produto
 * @property int id_usuario
 * @property string sku
 * @property string nome_tamanho
 * @property string situacao
 * @property string data_criacao
 */
class ProdutoLogistica extends Model
{
    protected $table = 'produtos_logistica';
    protected $fillable = ['id_produto', 'nome_tamanho', 'situacao', 'id_usuario'];
    protected $primaryKey = 'sku';
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function (self $model): void {
            $codigo = implode('', array_map(fn() => rand(0, 9), range(1, 12)));
            $model->sku = $codigo;
        });

        self::updating(function (self $model): void {
            if ($model->situacao === 'EM_ESTOQUE') {
                $estoque = new EstoqueGradeService();
                $estoque->id_produto = $model->id_produto;
                $estoque->nome_tamanho = $model->nome_tamanho;
                $estoque->alteracao_estoque = 1;
                $estoque->tipo_movimentacao = 'E';
                $estoque->descricao = "Usuario $model->id_usuario adicionou par no estoque";
                $estoque->id_responsavel = $model->id_usuario;
                $estoque->movimentaEstoque(DB::getPdo(), $model->id_usuario);

                $produto = Produto::buscarProdutoPorId($model->id_produto);
                if ($produto->data_primeira_entrada === null) {
                    $produto->data_primeira_entrada = Carbon::now()->format('Y-m-d H:i:s');
                    $produto->save();
                }
            }
        });
    }

    public function criarSkuPorTentativas(): void
    {
        $foiSalvo = false;
        $qtdMaxTentativas = 5;
        do {
            $qtdMaxTentativas--;
            try {
                $foiSalvo = $this->save();
                break;
            } catch (QueryException $e) {
                if ($e->errorInfo[1] === 1062) {
                    continue;
                } else {
                    throw $e;
                }
            }
        } while ($qtdMaxTentativas > 0);

        if ($foiSalvo === false) {
            throw new Exception('Erro ao salvar produto logística', 0, $e);
        }
    }

    /**
     * @return array<ProdutoLogistica>
     */
    public static function buscaReposicoesAguardandoEntrada(int $idProduto): array
    {
        $reposicoes = DB::select(
            "
            SELECT
                produtos_logistica.id_produto,
                produtos_logistica.nome_tamanho,
                produtos_logistica.situacao,
                produtos_logistica.sku,
                DATE_FORMAT(produtos_logistica.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`
            FROM produtos_logistica
            WHERE produtos_logistica.id_produto = :id_produto
                AND produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
            ORDER BY produtos_logistica.data_criacao DESC",
            ['id_produto' => $idProduto]
        );
        return $reposicoes;
    }

    /**
     * @return array<self|string>
     */
    public static function buscarPorSku(string $sku): array
    {
        $produtoLogistica = self::fromQuery(
            "SELECT
                        produtos_logistica.id_produto,
                        produtos_logistica.nome_tamanho,
                        produtos_logistica.situacao,
                        produtos_logistica.sku,
                        produtos_grade.cod_barras
                    FROM produtos_logistica
                    INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = produtos_logistica.nome_tamanho
                        AND produtos_grade.id_produto = produtos_logistica.id_produto
                    WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku, 'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        )->first();

        return [$produtoLogistica, $produtoLogistica->cod_barras];
    }

    public static function buscarAguardandoEntrada(string $sku): array
    {
        $produtoLogistica = DB::selectOne(
            "SELECT
                produtos_logistica.id_produto,
                IF(
                    EXISTS(
                        SELECT 1
                        FROM logistica_item
                        WHERE logistica_item.sku = produtos_logistica.sku
                        AND logistica_item.situacao > :situacao
                ), 'DEVOLUCAO', 'REPOSICAO') AS `origem`
            FROM produtos_logistica
            WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku, 'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        );

        if ($produtoLogistica === null) {
            throw new NotFoundHttpException('Produto não encontrado');
        }

        $orderBy = 'ORDER BY produtos_logistica.id_produto, produtos_logistica.nome_tamanho';
        $groupBy = 'GROUP BY produtos_logistica.id_produto';

        $binds = ['id_produto' => $produtoLogistica['id_produto']];

        if ($produtoLogistica['origem'] === 'DEVOLUCAO') {
            $where = 'AND logistica_item.situacao > :situacao ';
            $binds['situacao'] = LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA;
            $localizacao = DB::selectOneColumn(
                "SELECT produtos.localizacao
                FROM produtos
                WHERE produtos.id = :id_produto",
                ['id_produto' => $produtoLogistica['id_produto']]
            );

            $orderBy = 'ORDER BY produtos.localizacao, produtos_logistica.id_produto, produtos_logistica.nome_tamanho';
            $groupBy = 'GROUP BY produtos.localizacao';
            unset($binds['id_produto']);

            if ($localizacao !== null) {
                $where .= 'AND produtos.localizacao = :localizacao';
                $binds['localizacao'] = $localizacao;
            } else {
                $where .= 'AND produtos.localizacao IS NULL';
            }
        } else {
            $where = 'AND produtos_logistica.id_produto = :id_produto AND logistica_item.id IS NULL';
        }

        $sql = "SELECT
                produtos.localizacao,
                CONCAT(
                    '[',
                        GROUP_CONCAT(DISTINCT(
                            JSON_OBJECT(
                                'id_produto', produtos_logistica.id_produto,
                                'nome_tamanho', produtos_logistica.nome_tamanho,
                                'sku', produtos_logistica.sku,
                                'referencia', CONCAT(produtos.descricao, '-', produtos.cores),
                                'id_usuario', produtos_logistica.id_usuario,
                                'foto', COALESCE(
                                        (
                                            SELECT produtos_foto.caminho
                                            FROM produtos_foto
                                            WHERE produtos_foto.id = produtos_logistica.id_produto
                                            ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                                            LIMIT 1
                                        ),
                                        '{$_ENV['URL_MOBILE']}/images/img-placeholder.png'
                                    )
                                )
                            )
                        ),
                    ']'
                ) AS `json_produtos`
            FROM produtos_logistica
            INNER JOIN produtos ON produtos.id = produtos_logistica.id_produto
            LEFT JOIN logistica_item ON logistica_item.sku = produtos_logistica.sku
            WHERE TRUE
                AND produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                $where
            $groupBy
            $orderBy";

        $listaProdutos = DB::selectOne($sql, $binds);
        $listaProdutos['origem'] = $produtoLogistica['origem'];

        return $listaProdutos;
    }
}
