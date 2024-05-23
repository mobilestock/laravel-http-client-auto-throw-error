<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\service\LogsService;

class Logs
{
    public function consultar()
    {
        $request = Request::all();
        Validador::validar($request, [
            'select' => [Validador::OBRIGATORIO],
            'from' => [Validador::OBRIGATORIO],
            'where' => [Validador::OBRIGATORIO],
        ]);
        $dados = LogsService::consultar($request['select'], $request['from'], $request['where']);
        return $dados;
    }
}
