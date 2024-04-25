<?php

namespace MobileStock\helper;

use Symfony\Component\Process\Process;

abstract class Filas
{
    public static function lista(): array
    {
        return [
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['GERAR_PAGAMENTO'],
                'Job' => __DIR__ . '/../jobs/jobGerarPagamento.php',
            ],
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['MS_PAGAMENTO_RAPIDO'],
                'Job' => __DIR__ . '/../jobs/jobPagamentoRapido.php',
            ],
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['ATUALIZAR_PAGAMENTO_WEBHOOK'],
                'Job' => __DIR__ . '/../jobs/jobAtualizaSituacaoTransacao.php',
                'MaxReceiveCountPerMessage' => PHP_INT_MAX,
            ],
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['MS_FECHAR_PEDIDO'],
                'Job' => __DIR__ . '/../jobs/jobFecharPedidoMobileStock.php',
            ],
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['APPENTREGAS_GERAR_PAGAMENTO_PIX'],
                'Job' => __DIR__ . '/../jobs/jobGerarPagamentoPix.php',
            ],
            [
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['PROCESSO_CRIAR_TRANSACAO_CREDITO'],
                'Job' => __DIR__ . '/../jobs/jobProcessoCriarTransacaoCredito.php',
            ],
        ];
    }

    public static function executaProcesso(array $itemFila, array $conteudoRequisicao): Process
    {
        $process = new Process(['php', $itemFila['Job'], json_encode($conteudoRequisicao)]);
        $process->setTimeout(null);

        if (in_array($_ENV['AMBIENTE'], ['producao', 'homologado'])) {
            $process->start();
        } else {
            $envTemporario = $_ENV;
            $_ENV = [];
            $process->start();
            $_ENV = $envTemporario;
        }
        $process->wait();

        return $process;
    }
}
