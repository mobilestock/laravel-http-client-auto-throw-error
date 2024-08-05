<?php

namespace MobileStock\model;

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

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function (self $model): void {
            // TODO: Pensar em algoritmo mais eficiente
            // TODO: Fazer usando o try catch e esperando o erro de: Integrity constraint
            do {
                $codigo = random_int(100000000000, 999999999999);
            } while (self::where('sku', $codigo)->exists());
            $model->sku = $codigo;
        });
    }

    /**
     * @param int $idProduto
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
}
