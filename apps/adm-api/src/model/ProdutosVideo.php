<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Http;

/**
 * @property int $id_produto
 * @property string $link
 */

class ProdutosVideo extends Model
{
    public $timestamps = false;
    protected $fillable = ['id_produto', 'id_usuario', 'link'];

    const REGEX_URL_YOUTUBE = '/(?:youtube\.com.*(?:\?v=|\/embed\/)|youtu.be\/)(.{11})/';

    public static function buscaProdutoVideoPorLink(string $link, int $idProduto): ?self
    {
        $produtoVideo = self::fromQuery(
            'SELECT produtos_videos.id
            FROM produtos_videos
            WHERE produtos_videos.link = :link
                AND produtos_videos.id_produto = :id_produto',
            ['link' => $link, 'id_produto' => $idProduto]
        )->first();
        return $produtoVideo;
    }

    public static function buscaTituloVideo(string $videoId): string
    {
        $resposta = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet',
            'id' => $videoId,
            'key' => env('GOOGLE_TOKEN_PUBLICO'),
        ])->json();

        return $resposta['items'][0]['snippet']['title'];
    }
}
