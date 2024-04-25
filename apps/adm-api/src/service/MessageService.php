<?php
namespace MobileStock\service;
require_once __DIR__ . '../../../.env.php';

use api_webhooks\Models\Notificacoes;
use Aws\Sqs\SqsClient;
use Exception;
use DateTime;

class MessageService
{
    private $messageID;
    private $conexao;
    protected SqsClient $sqsClient;

    public function __construct()
    {
        $date = new DateTime();
        $this->messageID = $date->getTimestamp();
        $this->conexao = app(\PDO::class);
        $this->sqsClient = app(SqsClient::class);
    }

    private function logError(string $phone)
    {
        $mensagem = 'Não foi possível enviar mensagem: ' . $phone;
        Notificacoes::criaNotificacoes($this->conexao, $mensagem);
    }
    // private function saveMessage(string $content)
    // {
    //     $req = $this->conexao->prepare(
    //         "INSERT INTO fila_servico_mensageria (
    //             fila_servico_mensageria.conteudo
    //          ) VALUES (
    //             :content
    //         );"
    //     );
    //     $req->bindParam(":content", $content, PDO::PARAM_STR);
    //     $req->execute();
    //     return $this->conexao->lastInsertId();
    // }
    public function sqsController(array $postData, string $target)
    {
        try {
            if ($_ENV['AMBIENTE'] !== 'producao') {
                $postData['target'] = $_ENV['TELEFONE_WHATSAPP_TESTE'];
            }
            $this->sqsClient->sendMessage([
                'QueueUrl' => $_ENV['SQS_ENDPOINTS']['MENSAGERIA'],
                'MessageBody' => json_encode($postData),
                'MessageGroupId' => $this->messageID,
                'MessageDeduplicationId' => uniqid(rand(), true),
            ]);
            return true;
        } catch (Exception $e) {
            $this->logError($target);
            return false;
        }
    }
    // public function callbackUpdate(PDO $conn, $state, $id)
    // {
    //     $stmt = $conn->prepare("UPDATE fila_servico_mensageria SET situacao = :msg_state WHERE id = :msg_id");
    // 	$stmt->execute([':msg_state' => $state, ':msg_id' => $id,]);
    // }
    public function sendMessageWhatsApp(string $target, string $message)
    {
        if (!$target || !$message) {
            return false;
        }
        $postData = [
            'endpoint' => 'sendMessage',
            'target' => $target,
            'text' => $message,
        ];
        return $this->sqsController($postData, $target);
    }
    public function sendImageBase64WhatsApp(string $target, string $data, string $message = '')
    {
        if (!$target || !$message || !$data) {
            return false;
        }
        $postData = [
            'endpoint' => 'sendImageFromBase64',
            'target' => $target,
            'text' => $message,
            'data' => $data,
        ];
        return $this->sqsController($postData, $target);
    }
    public function sendImageWhatsApp(string $target, string $image, string $message = '')
    {
        if (!$target || !$image) {
            return false;
        }
        $postData = [
            'endpoint' => 'sendImageFromURL',
            'target' => $target,
            'text' => $message,
            'url' => $image,
        ];
        return $this->sqsController($postData, $target);
    }
    public function sendBroadcastTelegram(string $text)
    {
        $postData = [
            'endpoint' => 'sendBroadcast',
            'text' => $text,
        ];
        return $this->sqsController($postData, 0);
    }
}
