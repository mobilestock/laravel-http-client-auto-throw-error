<?php

namespace api_cliente\Controller;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutosVideo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Midia {

    public function baixaVideoOuFoto()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'url' => [Validador::OBRIGATORIO],
            'tipo' => [Validador::OBRIGATORIO, Validador::ENUM('foto', 'video')],
        ]);

        if ($dadosJson['tipo'] === 'foto') {
            $resposta = Http::get($dadosJson['url']);
            if (!$resposta->successful()) {
                throw new Exception('Erro ao baixar a foto');
            }

            $arquivo = $resposta->getBody();

            return new Response($arquivo, 200, [
                'Content-Type' => $resposta->header('Content-Type'),
            ]);
        }

        if ($dadosJson['tipo'] === 'video') {
            if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $dadosJson['url'])) {
                throw new BadRequestHttpException('Id de vídeo inválido');
            }

            $caminho = __DIR__ . '/../../../downloads/video.mp4';
            ProdutosVideo::baixaVideo($dadosJson['url']);

            $resposta = new Response(file_get_contents($caminho), 200, [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'attachment; filename="video.mp4"',
            ]);

            register_shutdown_function(function () use ($caminho) {
                if (file_exists($caminho)) {
                    unlink($caminho);
                }
            });

            return $resposta;
        }
    }
}
