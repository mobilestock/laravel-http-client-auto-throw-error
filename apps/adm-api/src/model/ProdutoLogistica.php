<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MobileStock\helper\ConversorArray;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @property int $id_produto
 * @property string $sku
 * @property string $nome_tamanho
 * @property string $situacao
 * @property string $origem
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

    public static function buscarVerificacaoPorSku(string $sku): self
    {
        $produtoLogistica = self::fromQuery(
            "SELECT
                produtos_logistica.sku,
                produtos_grade.cod_barras
            FROM produtos_logistica
            INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = produtos_logistica.nome_tamanho
                AND produtos_grade.id_produto = produtos_logistica.id_produto
            WHERE produtos_logistica.sku = :sku
                AND produtos_logistica.situacao = 'EM_ESTOQUE'
                AND NOT EXISTS(
                    SELECT 1
                    FROM logistica_item
                    WHERE logistica_item.sku = produtos_logistica.sku
                        AND logistica_item.situacao IN ('PE','CO','DF')
                )",
            ['sku' => $sku]
        )->first();

        if (empty($produtoLogistica)) {
            throw new Exception('O SKU bipado pertence a uma outra venda e não pode ser usado para conferência.');
        }

        return $produtoLogistica;
    }

    public static function buscarPorSku(string $sku): self
    {
        $produto = self::fromQuery(
            "SELECT
                produtos_logistica.id_produto,
                produtos_logistica.situacao,
                produtos_logistica.origem
            FROM produtos_logistica
            WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku]
        )->first();

        return $produto;
    }

    public static function buscarAguardandoEntrada(int $idProduto): array
    {
        $informacoesProduto = DB::selectOne(
            "SELECT
                produtos.localizacao,
                CONCAT(
                    '[',
                        GROUP_CONCAT(
                        DISTINCT JSON_OBJECT(
                            'id_produto', produtos_logistica.id_produto,
                            'nome_tamanho', produtos_logistica.nome_tamanho,
                            'sku', produtos_logistica.sku,
                            'referencia', CONCAT(produtos.descricao, '-', produtos.cores),
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
                        ),
                    ']'
                ) AS `json_produtos`
            FROM produtos_logistica
            INNER JOIN produtos ON produtos.id = produtos_logistica.id_produto
            WHERE produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                AND produtos_logistica.origem = 'REPOSICAO'
                AND produtos_logistica.id_produto = :id_produto",
            ['id_produto' => $idProduto]
        );

        return $informacoesProduto;
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
                        produtos_logistica.sku,
                        produtos_grade.cod_barras
                    FROM produtos_logistica
                    INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = produtos_logistica.nome_tamanho
                        AND produtos_grade.id_produto = produtos_logistica.id_produto
                    INNER JOIN logistica_item ON logistica_item.sku = produtos_logistica.sku
                        AND logistica_item.situacao = 'DE'
                    WHERE logistica_item.uuid_produto = :uuid_produto
                        AND produtos_logistica.situacao = 'EM_ESTOQUE'",
            ['uuid_produto' => $uuidProduto, 'situacao' => LogisticaItemModel::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        )->first();

        if (empty($produto)) {
            throw new NotFoundHttpException('As informações do produto não foram encontradas');
        }

        if ($produto->ja_conferido) {
            throw new Exception('Este produto já foi conferido');
        }

        return $produto;
    }

    /**
     * @param array<string> $codigosSku
     */
    public static function verificaPodeGuardarCodigosSku(array $codigosSku): void
    {
        [$sql, $binds] = ConversorArray::criaBindValues($codigosSku, 'sku');
        $codigosFalhos = DB::selectColumns(
            "SELECT
                produtos_logistica.sku
            FROM produtos_logistica
            WHERE produtos_logistica.sku IN ($sql)
              AND produtos_logistica.situacao <> 'AGUARDANDO_ENTRADA'",
            $binds
        );
        if (!empty($codigosFalhos)) {
            $codigosFalhos = array_map(fn(string $codigo): string => Str::formatarSku($codigo), $codigosFalhos);
            throw new UnprocessableEntityHttpException('Códigos já em estoque: ' . implode(', ', $codigosFalhos));
        }
    }

    public static function filtraCodigosSkuPorProdutos(array $produtos): array
    {
        $grades = array_map(
            fn(array $produto): string => "{$produto['id_produto']}{$produto['nome_tamanho']}",
            $produtos
        );
        [$sql, $binds] = ConversorArray::criaBindValues($grades, 'id_produto_nome_tamanho');

        $codigosSkuValidos = DB::select(
            "SELECT
                produtos_logistica.id_produto,
                produtos_logistica.nome_tamanho,
                CONCAT(
                        '[',
                            GROUP_CONCAT(
                                JSON_QUOTE(
                                    produtos_logistica.sku
                                ) ORDER BY produtos_logistica.data_criacao ASC
                            ),
                        ']'
                ) AS `json_codigos_sku`
            FROM produtos_logistica
                     LEFT JOIN logistica_item ON logistica_item.sku = produtos_logistica.sku
                     LEFT JOIN entregas_devolucoes_item ON entregas_devolucoes_item.uuid_produto = logistica_item.uuid_produto
                AND (
                   entregas_devolucoes_item.situacao <> 'CO'
                       OR entregas_devolucoes_item.tipo = 'DE'
                   )
                     LEFT JOIN produtos_aguarda_entrada_estoque ON produtos_aguarda_entrada_estoque.identificao = logistica_item.uuid_produto
                AND produtos_aguarda_entrada_estoque.em_estoque = 'F'
            WHERE CONCAT(produtos_logistica.id_produto, produtos_logistica.nome_tamanho) IN ($sql)
              AND entregas_devolucoes_item.id IS NULL
              AND produtos_aguarda_entrada_estoque.id IS NULL
              AND produtos_logistica.situacao = 'EM_ESTOQUE'
              AND (
                logistica_item.id IS NULL
                    OR logistica_item.situacao = 'DE'
                )
            GROUP BY produtos_logistica.id_produto, produtos_logistica.nome_tamanho",
            $binds
        );

        return $codigosSkuValidos;
    }
}
