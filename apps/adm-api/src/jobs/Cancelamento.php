<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\WebhookHttpClient;

class Cancelamento implements ShouldQueue
{
    use Queueable;

    protected array $dadosTransacao;

    public function __construct(array $dadosTransacao)
    {
        $this->dadosTransacao = $dadosTransacao;
    }

    public function handle(WebhookHttpClient $httpClient): void
    {
        $transacao = (new TransacaoFinanceiraModel())->forceFill($this->dadosTransacao);
        $url = ColaboradoresService::consultaUrlWebhook($transacao->pagador);

        $arrTransacao = TransacaoFinanceiraService::listaTransacoesApi(
            DB::getPdo(),
            $transacao->pagador,
            $transacao->id,
            1
        );
        $arrInformacoes = [
            'transacao' => $arrTransacao,
            'evento' => 'transacao.cancelada',
        ];

        $httpClient->post($url, $arrInformacoes);
    }
}
