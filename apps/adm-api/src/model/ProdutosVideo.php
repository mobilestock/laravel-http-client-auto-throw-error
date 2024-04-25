<?php

namespace MobileStock\model;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $id_produto
 * @property int $id_usuario
 * @property string $link
 * @property string $data_criacao
 * @property string $data_atualizacao
 */

class ProdutosVideo extends Model
{

    protected $fillable = ['id_produto', 'id_usuario', 'link'];

    public static function buscaIdPorLink(string $link, int $idProduto): ?int
    {
        $produtoVideo = DB::selectOneColumn(
            'SELECT produtos_videos.id
            FROM produtos_videos
            WHERE produtos_videos.link = :link
                AND produtos_videos.id_produto = :id_produto',
            ['link' => $link, 'id_produto' => $idProduto]);
        return $produtoVideo;
    }
}
