<?php

namespace MobileStock\jobs;

use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\helper\TokenCartaoValidador;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\jobs\config\ReceiveFromQueue;
use MobileStock\service\CartoesSenhasService;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use Monolog\Logger;
use PDO;
use Psr\Log\LogLevel;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    protected array $middlewares = [SetLogLevel::class . ':' . LogLevel::EMERGENCY, ReceiveFromQueue::class];

    public function run(array $dados, PDO $conexao): array
    {
        if (isset($dados['holderName'])) {
            app(Logger::class)->pushProcessor(function ($record) {
                $record['context']['queue']['body']['holderName'] = $record['context']['queue']['body'][
                    'cardNumber'
                ] = $record['context']['queue']['body']['expirationYear'] = $record['context']['queue']['body'][
                    'expirationMonth'
                ] = $record['context']['queue']['body']['tokenCartao'] = $record['context']['queue']['body'][
                    'secureCode'
                ] = '********';

                return $record;
            });
        }
        $transacao = new TransacaoFinanceiraService();
        $transacao->id = $dados['id_transacao'];
        $transacao->retornaTransacao($conexao);
        $transacao->pagador = (int) $transacao->pagador;

        if (empty($transacao->metodo_pagamento)) {
            throw new PagamentoTransacaoNaoExisteException();
        }
        $transacao->dados_cartao = $dados;

        try {
            $conexao->beginTransaction();
            $pagamento = ProcessadorPagamentos::criarPorInterfacesPadroes($conexao, $transacao);
            $pagamento->executa();

            if ($conexao->inTransaction()) {
                $conexao->commit();
            }
        } catch (\Throwable $exception) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }
            throw $exception;
        }

        // Salva token de cartão
        if ($transacao->metodo_pagamento === 'CA' && $dados['armazenar_cartao']) {
            $chave = CartoesSenhasService::consultaChaveAtual($conexao);
            $tokenCartao = TokenCartaoValidador::geraToken(
                [
                    'proprietario' => $dados['holderName'],
                    'numero' => $dados['cardNumber'],
                    'ano' => $dados['expirationYear'],
                    'mes' => $dados['expirationMonth'],
                    'data_criacao' => date('Y-m-d H:i:s'),
                    'cartao_hash' => sha1($dados['cardNumber'] . $dados['expirationYear'] . $dados['expirationMonth']),
                    'id_cliente' => (int) $transacao->pagador,
                ],
                $chave['chave_privada'],
                $chave['chave_publica']
            );

            $retorno['token_cartao'] = $tokenCartao;
        }

        $retorno['metodo_pagamento'] = $transacao->metodo_pagamento;

        return $retorno;
    }
};
