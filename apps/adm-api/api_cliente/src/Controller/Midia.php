<?php

namespace api_cliente\Controller;

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
            if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $dadosJson['fonte_midia'])) {
                throw new BadRequestHttpException('Id de vídeo inválido');
            }

            $fluxoVideo = ProdutosVideo::baixaVideo($dadosJson['fonte_midia']);

            $resposta = new StreamedResponse(function () use ($fluxoVideo){
                foreach ($fluxoVideo as $bloco) {
                    echo $bloco;
                    flush();
                }
            }, 200, [
                'Content-Type' => 'video/webm',
                'Content-Disposition' => 'attachment; filename="video.webm"',
            ]);

            return $resposta;
        }
    }
}
