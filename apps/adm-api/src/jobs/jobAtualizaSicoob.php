<?php

namespace MobileStock\jobs;

use api_webhooks\Models\TransacaoIugu;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\Pagamento\PagamentoPixSicoob;
use MobileStock\service\SicoobHttpClient;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL];

    // Esse job é um worker, ou seja, ele fica rodando em loop infinito
    public function run(SicoobHttpClient $sicoob): void
    {
        while (true) {
            $transacoesPendentes = TransacaoFinanceiraService::consultaTransacoesPendentesSicoob();

            foreach ($transacoesPendentes as $transacao) {
                $transacao->id_usuario = 2;

                $interfacePagamento = new PagamentoPixSicoob(DB::getPdo(), $transacao, $sicoob);

                if (!($situacao = $interfacePagamento->converteSituacaoApi())) {
                    continue;
                }

                $transacaoIugu = new TransacaoIugu(DB::getPdo(), [
                    'event' => 'invoice.due',
                    'data' => [
                        'id' => $transacao->cod_transacao,
                        'status' => $situacao,
                    ],
                ]);

                DB::beginTransaction();
                $transacaoIugu->atualizaFaturamentoIugo();
                DB::commit();
            }
            sleep(3);
        }
    }
};
