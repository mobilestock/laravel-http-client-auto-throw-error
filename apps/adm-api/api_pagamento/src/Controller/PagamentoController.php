<?php

namespace api_pagamento\Controller;

use Illuminate\Support\Facades\Request;
use MobileStock\helper\CalculadorTransacao;
use MobileStock\helper\Validador;
use MobileStock\model\TaxasModel;
use MobileStock\service\ConfiguracaoService;

class PagamentoController
{
    public function simulaCalculo()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'calculos' => [Validador::ARRAY],
        ]);

        Validador::validar($dadosJson['calculos'][0] ?? [], [
            'metodo_pagamento' => [Validador::OBRIGATORIO],
            'numero_parcelas' => [],
            'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $dadosPagamentoPadrao = ConfiguracaoService::consultaDadosPagamentoPadrao();
        $dadosJson['calculos'] = array_map(function (array $calculo) use ($dadosPagamentoPadrao) {
            $numeroParcelas = $calculo['numero_parcelas'];
            $valor = $calculo['valor'];
            $metodoPagamento = $calculo['metodo_pagamento'];

            if ($numeroParcelas === 'padrao') {
                $calculo['numero_parcelas'] = $dadosPagamentoPadrao['parcelas'];
            }

            $calculador = new CalculadorTransacao($valor, $metodoPagamento, $calculo['numero_parcelas']);

            if ($metodoPagamento === 'PX') {
                $calculador->valor_taxa = TaxasModel::consultaValorTaxaParcela(CalculadorTransacao::PARCELAS_PADRAO_CARTAO);
            }

            if ($metodoPagamento === 'CA') {
                $calculador->parcelas = [];
                for ($index = 1; $index <= 12; $index++) {
                    $calculadorAux = new CalculadorTransacao($valor, $metodoPagamento, $index);
                    $calculadorAux->calcula();
                    $calculador->parcelas[] = $calculadorAux;
                }
            }

            $calculador->calcula();
            unset($calculador->valor_parcela);

            return $calculador;
        }, $dadosJson['calculos']);

        return ['calculos' => $dadosJson['calculos'], 'parcelas_padrao_cartao' => CalculadorTransacao::PARCELAS_PADRAO_CARTAO];
    }
}
