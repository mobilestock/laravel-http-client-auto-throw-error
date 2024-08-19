<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;

/**
 * @property int $id_produto
 * @property string $link
 */

class ProdutosVideo extends Model
{
    public $timestamps = false;
    protected $fillable = ['id_produto', 'id_usuario', 'link'];

    const REGEX_URL_YOUTUBE = '/(?:youtube\.com.*(?:\?v=|\/shorts\/|\/embed\/)|youtu.be\/)(.{11})/';

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

        $video = current($resposta['items']);

        if (empty($video)) {
            throw new NotFoundHttpException('Vídeo não encontrado.');
        }

        return $video['snippet']['title'];
    }

    public static function baixaVideo(string $videoId): Generator
    {
        $url = "https://www.youtube.com/watch?v={$videoId}";

        $comando = [__DIR__ . '/../../yt-dlp/yt-dlp', '-f', 'bestvideo[height<=1080]+bestaudio', '-o', '-', $url];

        $process = new Process($comando);

        $process->setTimeout(60 * 30); // 30 minutos

        if (!App::isProduction()) {
            /**
             * @issue https://github.com/mobilestock/backend/issues/492
             */
            $envTemporario = $_ENV;
            $_ENV = [];
        }

        try {
            $process->start();
            return $process->getIterator($process::ITER_SKIP_ERR);
        } finally {
            if (!App::isProduction()) {
                $_ENV = $envTemporario;
            }
        }
    }
}
