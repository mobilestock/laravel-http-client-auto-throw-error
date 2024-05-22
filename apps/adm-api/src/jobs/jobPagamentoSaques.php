<?php

namespace MobileStock\jobs;

use Exception;
use Illuminate\Support\Facades\App;
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
        if (!App::isProduction()) {
            throw new Exception('Job deve ser executado apenas em produção.');
        }

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
                $iugu->listaCodigosPermitidos = [200];
                $iugu->post("accounts/{$transferencia['id_iugu']}/request_withdraw", [
                    'amount' => $transferencia['valor_pagamento'],
                ]);
                TransferenciasService::atualizaTransferenciaSaque($transferencia['id'], $iugu->body['id']);

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
