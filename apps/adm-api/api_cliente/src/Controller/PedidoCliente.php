<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\service\Fila\FilaService;

class PedidoCliente
{
    public function criaPedido(FilaService $fila)
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_colaborador_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_transacao' => [Validador::NAO_NULO],
        ]);

        $fila->conteudoArray = [
            'id_colaborador_tipo_frete' => $dadosJson['id_colaborador_tipo_frete'],
            'id_transacao' => $dadosJson['id_transacao'],
            'user' => [
                'id' => Auth::user()->id,
                'id_colaborador' => Auth::user()->id_colaborador,
            ]
        ];
        $fila->url_fila = $_ENV['SQS_ENDPOINTS']['MS_FECHAR_PEDIDO'];
        $fila->envia();

        return $fila->id;
    }
}
