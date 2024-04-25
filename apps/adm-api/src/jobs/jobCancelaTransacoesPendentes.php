<?php

namespace Mobilestockjob;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(
        PDO $conexao,
        TransacaoFinanceiraService $transacaoFinanceiraService,
        IuguHttpClient $iugu
    ): void {
        $iugu->listaCodigosPermitidos = [200];

        $transacoes = TransacaoConsultasService::buscaTransacoesASeremCanceladas($conexao);
        foreach ($transacoes as $transacao) {
            try {
                $conexao->beginTransaction();
                # cancela transação internamente
                $transacaoFinanceiraService->motivo_cancelamento = 'CLIENTE_DESISTIU';
                $transacaoFinanceiraService->id = $transacao->id_transacao;
                $transacaoFinanceiraService->status = 'PE';
                $transacaoFinanceiraService->pagador = $transacao->pagador;
                $transacaoFinanceiraService->origem_transacao = $transacao->origem_transacao;
                $transacaoFinanceiraService->metodo_pagamento = $transacao->metodo_pagamento;
                $transacaoFinanceiraService->emissor_transacao = $transacao->emissor_transacao;
                $transacaoFinanceiraService->cod_transacao = $transacao->cod_transacao;
                $transacaoFinanceiraService->removeTransacaoPaga($conexao, 2);
                $conexao->commit();
            } catch (\Throwable $exception) {
                $conexao->rollBack();
                throw $exception;
            }
        }
    }
};
