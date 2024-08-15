<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutosVideo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Midia {

    public function baixaMidia()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'fonte_midia' => [Validador::OBRIGATORIO],
            'tipo' => [Validador::OBRIGATORIO, Validador::ENUM('FOTO', 'VIDEO')],
        ]);

        if ($dadosJson['tipo'] === 'FOTO') {
            $resposta = Http::get($dadosJson['fonte_midia'])->throw();

            $arquivo = $resposta->getBody();

            return new Response($arquivo, 200, [
                'Content-Type' => $resposta->header('Content-Type'),
            ]);
        } elseif ($dadosJson['tipo'] === 'VIDEO') {
            try {
                DB::getLock();
                if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $dadosJson['fonte_midia'])) {
                    throw new BadRequestHttpException('Id de vídeo inválido');
                }

                $caminho = __DIR__ . '/../../../downloads/videos/video.mp4';
                ProdutosVideo::baixaVideo($dadosJson['fonte_midia']);

                $stream = fopen($caminho, 'r');
                $resposta = new StreamedResponse(function () use ($stream) {
                    while (!feof($stream)) {
                        echo fread($stream, 1048576);
                    }
                }, 200, [
                    'Content-Type' => 'video/mp4',
                    'Content-Disposition' => 'attachment; filename="video.mp4"',
                ]);

                return $resposta;
            } finally {
                register_shutdown_function(function () use ($caminho) {
                    $dir = dirname($caminho);
                    foreach (glob($dir . '/*') as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                });
            }
        }
    }
}
