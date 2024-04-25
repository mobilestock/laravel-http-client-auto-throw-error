<?php

namespace MobileStock\jobs\config;

use Aws\Sqs\SqsClient;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Log\Logger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use MobileStock\helper\ClienteException;
use MobileStock\helper\Retentador;
use MobileStock\helper\RetryableException;
use MobileStock\helper\Validador;
use MobileStock\service\Fila\FilaRespostasService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * No metodo run não pode utilizar validador.
 */
class ReceiveFromQueue
{
    public SqsClient $sqsClient;
    public FilaRespostasService $filaService;

    public function __construct(SqsClient $sqsClient, FilaRespostasService $filaService)
    {
        $this->sqsClient = $sqsClient;
        $this->filaService = $filaService;
    }

    /**
     * @throws Throwable
     * @throws RetryableException
     * @throws ClienteException
     */
    public function handle($ignorado, Closure $next): void
    {
        set_time_limit(0);

        Validador::validar(
            ['STDIN' => $_SERVER['argv'][1]],
            [
                'STDIN' => [Validador::JSON],
            ]
        );
        $dados = json_decode($_SERVER['argv'][1], true);

        Validador::validar($dados, [
            'QueueUrl' => [Validador::OBRIGATORIO],
            'MessageId' => [Validador::OBRIGATORIO],
            'ReceiptHandle' => [Validador::OBRIGATORIO],
        ]);

        $retorno = [];
        $chavesFila = [
            'MessageId',
            'ReceiptHandle',
            'MD5OfBody',
            'QueueUrl',
            'Attributes',
            'MaxReceiveCountPerMessage',
        ];
        app(Logger::class)->withContext([
            'queue' => array_merge(Arr::only($dados, $chavesFila), [
                'body' => Arr::except($dados, $chavesFila),
            ]),
        ]);

        if (!empty($dados['user'])) {
            Auth::setUser(new GenericUser($dados['user']));
        }

        try {
            $retorno = $next($dados);
            $this->deleteMessage($dados);
        } catch (Throwable $exception) {
            if ($exception instanceof ClienteException) {
                $retorno['message'] ??= $exception->getMessage();
                $retorno['error']['code'] = Response::HTTP_UNPROCESSABLE_ENTITY;
                $this->deleteMessage($dados);
            } elseif ($dados['Attributes']['ApproximateReceiveCount'] > $dados['MaxReceiveCountPerMessage']) {
                $retorno['message'] ??=
                    'Mensagem não processada após 3 tentativas.' .
                    PHP_EOL .
                    'Nossos desenvolvedores já estão trabalhando para encontrarem a solução.' .
                    PHP_EOL .
                    "Erro: {$exception->getMessage()}";
                $retorno['error']['code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
                $this->deleteMessage($dados);
            }

            throw $exception;
        } finally {
            if (!empty($retorno)) {
                Retentador::retentar(10, fn() => $this->filaService->responde($dados['MessageId'], $retorno));
            }
        }
    }

    public function deleteMessage(array $dados): void
    {
        $this->sqsClient->deleteMessage([
            'QueueUrl' => $dados['QueueUrl'],
            'ReceiptHandle' => $dados['ReceiptHandle'],
        ]);
    }
}
