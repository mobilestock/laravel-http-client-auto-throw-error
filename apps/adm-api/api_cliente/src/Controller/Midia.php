<?php

namespace api_cliente\Controller;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutosVideo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Midia {

    public function baixaVideoOuFoto()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'foto' => [Validador::SE(Validador::OBRIGATORIO, Validador::STRING)],
            'video' => [Validador::SE(Validador::OBRIGATORIO, Validador::STRING)],
        ]);

        if (isset($dadosJson['foto'])) {
            $resposta = Http::get($dadosJson['foto']);
            if (!$resposta->successful()) {
                throw new Exception('Erro ao baixar a foto');
            }

            $arquivo = $resposta->getBody();

            return new Response($arquivo, 200, [
                'Content-Type' => $resposta->header('Content-Type'),
            ]);
        }

        if (isset($dadosJson['video'])) {
            $caminho = __DIR__ . '/../../../downloads/video.mp4';
            ProdutosVideo::baixaVideo($dadosJson['video']);
            $resposta = new StreamedResponse(function () use ($caminho) {
                $stream = fopen($caminho, 'rb');
                fpassthru($stream);
                fclose($stream);
            });

            $resposta->headers->set('Content-Type', 'video/mp4');
            $resposta->headers->set('Content-Disposition', 'attachment; filename="video.mp4"');

            Storage::delete($caminho);

            return $resposta;
        }
    }
}
