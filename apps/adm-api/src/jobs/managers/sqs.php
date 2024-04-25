<?php

use Aws\Sqs\SqsClient;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Arr;
use MobileStock\helper\Filas;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../../vendor/autoload.php';

$config = Filas::lista();
$sqsClient = app(SqsClient::class);

$result = [];
$logger = app(LoggerInterface::class);

echo 'Aguardando recebimento de mensagens das filas: ' . PHP_EOL . implode(PHP_EOL, array_column($config, 'QueueUrl')) . PHP_EOL;
while (true) {
    foreach ($config as &$queue) {
        $promise = $sqsClient->receiveMessageAsync(
            array_merge($queue, [
                'AttributeNames' => ['ApproximateReceiveCount'],
                'WaitTimeSeconds' => 0
            ])
        );

        $promise->then(function (Aws\ResultInterface $result) use (&$queue) {
            $mensagens = $result->get('Messages') ?: [];

            if (!count($mensagens) > 0) {
                return;
            }

            $jobName = last(explode('/', $queue['Job']));
            echo "#$jobName Mensagem recebida." . PHP_EOL;
            echo implode(PHP_EOL, array_column($mensagens, 'MessageId')) . PHP_EOL;

            foreach ($mensagens as $mensagem) {
                $mensagemTratada = json_decode($mensagem['Body'], true);
                unset($mensagem['Body']);
                $queue['MaxReceiveCountPerMessage'] ??= 3;
                $mensagemTratada = array_merge($mensagemTratada, $mensagem, Arr::except($queue, ['Job', 'Processing']));
                $id = $mensagemTratada['MessageId'];

                if (array_key_exists($id, $queue['Processing'] ?? [])) {
                    if ($queue['Processing'][$id]->isRunning()) {
                        continue;
                    }
                    unset($queue['Processing'][$id]);
                }

                $process = Filas::executaProcesso($queue, $mensagemTratada);
                $queue['Processing'][$id] = $process;
            }
        }, function (Throwable $error) use ($logger) {
            $logger->emergency(
                $error->getMessage(),
                [
                    'title' => "SQS_WORKER"
                ]
            );
        });

        $result[$queue['QueueUrl']] = $promise;
    }

    Utils::all(array_values($result))->wait();

    usleep(0.5 * 1e6); // 0.5 segundo
}
