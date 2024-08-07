<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * @property int id_produto
 * @property string nome_tamanho
 * @property string situacao
 * @property string origem
 * @property string sku
 */
class ProdutoLogistica extends Model
{
    protected $table = 'produtos_logistica';
    protected $fillable = ['id_produto', 'nome_tamanho', 'situacao', 'origem'];
    protected $primaryKey = 'sku';
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function (self $model): void {
            $codigo = implode('', array_map(fn() => rand(0, 9), range(1, 12)));
            $model->sku = $codigo;
        });

        //        self::updated(function (self $model): void {
        //            DB::insert('', []);
        //        });
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
            ORDER BY produtos_logistica.id DESC",
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
                        produtos_logistica.origem,
                        produtos_grade.cod_barras
                    FROM produtos_logistica
                    INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = produtos_logistica.nome_tamanho
                        AND produtos_grade.id_produto = produtos_logistica.id_produto
                    WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku]
        )->first();

        return [$produtoLogistica, $produtoLogistica->cod_barras];
    }

    public function buscarAguardandoEntrada(): array
    {
        $orderBy = 'ORDER BY produtos_logistica.id_produto, produtos_logistica.nome_tamanho';
        $groupBy = 'GROUP BY produtos_logistica.id_produto';
        $where = 'AND produtos_logistica.id_produto = :id_produto';
        $binds = ['origem' => $this->origem, 'id_produto' => $this->id_produto];

        if ($this->origem === 'DEVOLUCAO') {
            $localizacao = DB::selectOneColumn(
                "SELECT produtos.localizacao
                FROM produtos
                WHERE produtos.id = :id_produto",
                ['id_produto' => $this->id_produto]
            );

            $orderBy = 'ORDER BY produtos.localizacao, produtos_logistica.id_produto, produtos_logistica.nome_tamanho';
            $groupBy = 'GROUP BY produtos.localizacao';
            unset($binds['id_produto']);

            if ($localizacao !== null) {
                $binds['localizacao'] = $localizacao;
                $where = 'AND produtos.localizacao = :localizacao';
            } else {
                $where = 'AND produtos.localizacao IS NULL';
            }
        }

        $sql = "SELECT
                produtos.localizacao,
                produtos_logistica.origem,
                CONCAT(
                    '[',
                        GROUP_CONCAT(DISTINCT(
                            JSON_OBJECT(
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
                            )
                        ),
                    ']'
                ) AS `json_produtos`
            FROM produtos_logistica
            INNER JOIN produtos ON produtos.id = produtos_logistica.id_produto
            WHERE produtos_logistica.origem = :origem
                AND produtos_logistica.situacao = 'AGUARDANDO_ENTRADA'
                $where
            $groupBy
            $orderBy";

        $listaProdutos = DB::selectOne($sql, $binds);

        return $listaProdutos;
    }
}
