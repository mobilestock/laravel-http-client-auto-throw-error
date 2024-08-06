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
                        produtos_grade.cod_barras
                    FROM produtos_logistica
                    INNER JOIN produtos_grade ON produtos_grade.nome_tamanho = produtos_logistica.nome_tamanho
                        AND produtos_grade.id_produto = produtos_logistica.id_produto
                    WHERE produtos_logistica.sku = :sku",
            ['sku' => $sku]
        );

        return [$produtoLogistica, $produtoLogistica->cod_barras];
    }
}
