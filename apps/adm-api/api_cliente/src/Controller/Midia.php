<?php

namespace api_cliente\Controller;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutosVideo;
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

            return new StreamedResponse(function () use ($arquivo) {
                while (!$arquivo->eof()) {
                    echo $arquivo->read(1024);
                    ob_flush();
                    flush();
                }
            }, 200, [
                'Content-Type' => 'application/octet-stream',
            ]);
        }

        if (isset($dadosJson['video'])) {
            ProdutosVideo::baixaVideo($dadosJson['video']);
        }
    }
}
