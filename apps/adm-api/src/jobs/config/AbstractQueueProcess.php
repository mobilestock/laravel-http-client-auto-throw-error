<?php /*

namespace MobileStock\jobs\config;

use MobileStock\database\Conexao;
use MobileStock\helper\LoggerFactory;
use MobileStock\helper\SqsClientFactory;
use MobileStock\helper\Validador;
use MobileStock\service\Pagamento\Filas\FilaPagamentosService;

/**
 * @deprecated
 * @see \MobileStock\jobs\config\ReceiveFromQueue
 * No metodo run não pode utilizar validador.
 */
/*
abstract class AbstractQueueProcess extends AbstractJob
{
    public array $dados;
    protected FilaPagamentosService $queueServiceInstance;

    public function prepareJob(): callable
    {
        return function () {
            try {
                set_time_limit(0);
                $conexao = Conexao::criarConexao();

                Validador::validar(['STDIN' => $_SERVER['argv'][1]], [
                    'STDIN' => [Validador::JSON],
                ]);
                $this->dados = json_decode($_SERVER['argv'][1], true);

                Validador::validar($this->dados, [
                    'QueueUrl' => [Validador::OBRIGATORIO],
                    'id' => [Validador::OBRIGATORIO, Validador::NUMERO]
                ]);

                if (in_array($_ENV['AMBIENTE'], ['producao', 'homologado'])) {
                    Validador::validar($this->dados, [
                        'ReceiptHandle' => [Validador::OBRIGATORIO]
                    ]);
                    $receiptHandle = $this->dados['ReceiptHandle'];

                    $sqsClient = SqsClientFactory::default();

                    $sqsClient->deleteMessage([
                        'QueueUrl' => $this->dados['QueueUrl'],
                        'ReceiptHandle' => $receiptHandle,
                    ]);
                }
                $this->queueServiceInstance = new FilaPagamentosService();
                $this->queueServiceInstance->url_fila = $this->dados['QueueUrl'];
                $this->queueServiceInstance->respostaArray = [];
                $this->queueServiceInstance->id = $this->dados['id'];
                $this->queueServiceInstance->situacao = 'PR';
                $this->queueServiceInstance->salva($conexao);
            } catch (\Throwable $exception) {
                $logger = LoggerFactory::arquivo('log_execucao_fila.log');
                $logger->error($exception->getMessage(), $_SERVER['argv']);

                if (isset($sqsClient) && isset($receiptHandle)) {
                    $sqsClient->deleteMessage([
                        'QueueUrl' => $this->dados['QueueUrl'],
                        'ReceiptHandle' => $receiptHandle,
                    ]);
                }

                throw $exception;
            }

            try {
                call_user_func(parent::prepareJob());
                $this->queueServiceInstance->situacao = 'OK';
            } catch (\Throwable $exception) {
                $this->queueServiceInstance->situacao = 'ER';
                $this->queueServiceInstance->respostaArray['message'] = $exception->getMessage();

                throw $exception;
            } finally {
                $this->queueServiceInstance->salva($conexao);
            }
        };
    }

}*/