<?php

namespace MobileStock\jobs;

use api_webhooks\Models\TransacaoIugu;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use PDO;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::CRITICAL, ReceiveFromQueue::class];

    /**
     * @return array|void
     */
    public function run(array $dados, PDO $conexao)
    {
        try {
            $precisaResposta = !empty($dados['precisa_resposta']);
            $statusNaoProcessavel = in_array($dados['data']['status'], [
                'pending',
                'refunded',
                'in_protest',
                'chargeback',
            ]);

            if ($precisaResposta && $statusNaoProcessavel) {
                return ['message' => 'ok'];
            } elseif ($statusNaoProcessavel) {
                return;
            }

            $conexao->beginTransaction();

            // https://github.com/mobilestock/backend/issues/175
            $transacaoIugu = new TransacaoIugu($conexao, $dados);
            $transacaoIugu->atualizaFaturamentoIugo();
            $conexao->commit();

            if ($precisaResposta) {
                return ['message' => 'ok'];
            }
        } catch (\Throwable $exception) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $exception;
        }
    }
};
