<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int $id_produto
 * @property string $sku
 * @property string $nome_tamanho
 * @property string $situacao
 * @property string $origem
 * @property string $cod_barras
 */
class ProdutoLogistica extends Model
{
    protected $table = 'produtos_logistica';
    protected $fillable = ['id_produto', 'nome_tamanho', 'situacao', 'origem', 'id_usuario'];
    protected $primaryKey = 'sku';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        self::creating([self::class, 'geraSku']);
    }

    public static function geraSku(self $model): void
    {
        $codigo = implode('', array_map(fn() => rand(0, 9), range(1, 12)));
        $model->sku = $codigo;
    }

    public function criarSkuPorTentativas(): void
    {
        $qtdMaxTentativas = 5;
        while ($qtdMaxTentativas-- >= 0) {
            try {
                if ($this->save()) {
                    return;
                }
            } catch (QueryException $e) {
                if ($e->errorInfo[1] !== 1062) {
                    throw $e;
                }
            }
        }
        throw new Exception('Erro ao salvar produto logística');
    }

    public static function buscarPorSku(string $sku): self
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
                        AND produtos_logistica.situacao = 'EM_ESTOQUE'
                    WHERE produtos_logistica.sku = :sku
                        AND NOT EXISTS(
                            SELECT 1
                            FROM logistica_item
                            WHERE logistica_item.sku = produtos_logistica.sku
                                AND logistica_item.situacao = :situacao
                        )",
            ['sku' => $sku, 'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        )->firstOrFail();

        return $produtoLogistica;
    }

    public static function buscarAguardandoEntrada(string $sku): array
    {
        $produto = DB::selectOne(
            "SELECT
                produtos_logistica.id_produto,
                produtos_logistica.situacao
            FROM produtos_logistica
            WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku]
        );

        if ($produto === null) {
            throw new NotFoundHttpException('Produto não encontrado');
        }

        if ($produto['situacao'] !== 'AGUARDANDO_ENTRADA') {
            throw new Exception('Este produto não está aguardando entrada');
        }

        $listaProdutos = DB::selectOne(
            "SELECT
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
            WHERE produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                AND produtos_logistica.id_produto = :id_produto AND logistica_item.id IS NULL
            ORDER BY produtos_logistica.id_produto, produtos_logistica.nome_tamanho",
            ['id_produto' => $produto['id_produto']]
        );

        return $listaProdutos;
    }

    public static function buscarOrigem(array $listaSku): string
    {
        [$skuSql, $binds] = ConversorArray::criaBindValues($listaSku);
        $origem = DB::selectOneColumn(
            "SELECT
                IF(
                    EXISTS(
                        SELECT 1
                        FROM logistica_item
                        WHERE logistica_item.sku = produtos_logistica.sku
                        AND logistica_item.situacao > :situacao
                ), 'DEVOLUCAO', 'REPOSICAO') AS `origem`
            FROM produtos_logistica
            WHERE produtos_logistica.sku IN ($skuSql)",
            $binds + ['situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        );

        return $origem;
    }

    public static function buscarPorUuid(string $uuidProduto): ?self
    {
        $produto = self::fromQuery(
            "SELECT
                        EXISTS(
                            SELECT 1
                            FROM logistica_item
                            WHERE logistica_item.sku = produtos_logistica.sku
                                AND logistica_item.situacao = :situacao
                        ) AS `ja_conferido`,
                        produtos_logistica.sku
                    FROM produtos_logistica
                    INNER JOIN logistica_item ON logistica_item.sku = produtos_logistica.sku
                        AND logistica_item.situacao = 'DE'
                    WHERE logistica_item.uuid_produto = :uuid_produto
                        AND produtos_logistica.situacao = 'EM_ESTOQUE'",
            ['uuid_produto' => $uuidProduto, 'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        )->first();

        if ($produto->ja_conferido) {
            throw new Exception('Este produto já foi conferido');
        }

        return $produto;
    }
}
