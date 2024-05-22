<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ExceptionHandler;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\TransferenciasService;
use Psr\Log\LogLevel;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY];

    public function run(ExceptionHandler $exceptionHandler)
    {
        $transferencias = TransferenciasService::buscaTransferenciasNaoSacadas();
        if (empty($transferencias)) {
            return;
        }

        $erros = [];
        foreach ($transferencias as $transferencia) {
            try {
                DB::beginTransaction();

                $iugu = new IuguHttpClient();
                $iugu->apiToken = $transferencia['iugu_token_live'];
                $retorno = $iugu->post("accounts/{$transferencia['id_iugu']}/request_withdraw", [
                    'amount' => $transferencia['valor_pagamento'],
                ]);
                TransferenciasService::atualizaTransferenciaSaque($transferencia['id'], $retorno->body['id']);

                DB::commit();
            } catch (Throwable $exception) {
                DB::rollBack();
                $erros[] = $exception;
            }
        }

        foreach ($erros as $erro) {
            $exceptionHandler->report($erro);
        }
    }
};
