<?php

namespace api_estoque\Controller;

use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\service\Separacao\separacaoService;

class SeparacaoPublic
{
    public function listarEtiquetasSeparacao()
    {
        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'etiqueta_mobile' => [Validador::ENUM('TODAS', 'PRONTAS', 'COLETAS')],
        ]);

        $consultaClienteXProdutos = separacaoService::listarEtiquetasSeparacao($dadosJson['etiqueta_mobile']);

        return $consultaClienteXProdutos;
    }
}
