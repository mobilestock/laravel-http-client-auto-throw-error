<?php

namespace api_cliente\Controller;

use Illuminate\Http\Request;
use MobileStock\helper\Validador;
use MobileStock\service\ConsumidorFinal\ConsumidorFinalService;
use PDO;

class ConsumidorFinal
{
    public function salvaConsumidorFinal(PDO $conexao, Request $request)
    {
        $dadosJson = $request->all();
        $dadosJson['telefone'] = $request->telefone();
        Validador::validar($dadosJson, [
            'uuid_produto' => [Validador::OBRIGATORIO],
            'nome' => [Validador::OBRIGATORIO],
        ]);
        $uuidProduto = $dadosJson['uuid_produto'];
        $observacao = [
            'nome' => $dadosJson['nome'],
            'telefone' => $dadosJson['telefone'],
        ];
        ConsumidorFinalService::salvaConsumidorFinal($conexao, json_encode($observacao), $uuidProduto);
    }
}
