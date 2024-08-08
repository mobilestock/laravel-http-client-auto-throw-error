<?php

namespace api_cliente\Controller;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use MobileStock\helper\Validador;
use MobileStock\model\ProdutosVideo;

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
            if ($resposta->successful()) {
                $arquivo = $resposta->body();
                return Response::make($arquivo, 200, [
                    'Content-Type' => 'image/webp',
                    'Content-Disposition' => 'attachment; filename="foto.webp"'
                ]);
            } else {
                throw new Exception('Erro ao baixar a foto');
            }
        }

        if (isset($dadosJson['video'])) {
            ProdutosVideo::baixaVideo($dadosJson['video']);
        }
    }
}
