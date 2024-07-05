<?php

namespace MobileStock\model;

use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id_produto
 * @property int $id_categoria
 */

class ProdutosCategoria extends Model
{
    protected $fillable = ['id_produto', 'id_categoria'];

    public $timestamps = false;

    /**
     * @return Collection<self>
     */
    public static function buscaCategoriasProduto(int $idProduto): Collection
    {
        $caregorias = self::fromQuery(
            "SELECT
                produtos_categorias.id,
                produtos_categorias.id_produto,
                produtos_categorias.id_categoria
            FROM produtos_categorias
            WHERE produtos_categorias.id_produto = ?",
            [$idProduto]
        );
        return $caregorias;
    }
}
