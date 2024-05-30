<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\TransferenciasService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $ativado = ConfiguracaoService::informacaoPagamentoAutomaticoTransferenciasAtivo(DB::getPdo());
        if (!$ativado) {
            return;
        }

        $contemplados = TransferenciasService::prioridadePagamentoAutomatico();
        if (empty($contemplados)) {
            return;
        }

        $iugu = new IuguHttpClient();
        $dadosSubConta = $iugu->informacoesSubConta();
        $valorSubConta =
            ((float) preg_replace('/[^0-9]/', '', $dadosSubConta->body['balance_available_for_withdraw'])) / 100;
        var_dump($valorSubConta);
        $zeramos = false;
        foreach ($contemplados as $contemplado) {
            if ($zeramos) {
                continue;
            }
            if ($contemplado['valor_pagamento'] - $contemplado['valor_pago'] > $valorSubConta) {
                $zeramos = true;
                continue;
            }
            TransferenciasService::pagaTransferencia($contemplado['id']);
            sleep(5);
            $dadosSubConta = $iugu->informacoesSubConta();
            $valorSubConta =
                (float) (preg_replace('/[^0-9]/', '', $dadosSubConta->body['balance_available_for_withdraw'])) / 100;
            var_dump($valorSubConta);
        }
    }
};
