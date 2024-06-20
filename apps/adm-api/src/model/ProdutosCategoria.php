<?php

namespace MobileStock\model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $id_produto
 * @property int $id_categoria
 * @property int $id_usuario
 */

class ProdutosCategoria extends Model
{

    protected $fillable = ['id_produto', 'id_categoria', 'id_usuario'];

    public $timestamps = false;

    public static function buscaIdPorIdProduto(int $idProduto): array
    {
        $idsCategorias = DB::selectColumns(
            "SELECT produtos_categorias.id
            FROM produtos_categorias
            WHERE produtos_categorias.id_produto = ?",
            [$idProduto]);

        return $idsCategorias;
    }

}
