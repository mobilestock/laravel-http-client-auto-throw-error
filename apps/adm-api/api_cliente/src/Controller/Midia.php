<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
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
            $arquivo = Http::get($dadosJson['foto']);
        }

        if (isset($dadosJson['video'])) {
            ProdutosVideo::baixaVideo($dadosJson['video']);
        }

        return $arquivo;
    }
}
