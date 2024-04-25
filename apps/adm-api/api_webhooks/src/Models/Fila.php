<?php

namespace api_webhooks\Models;

use Aws\AwsClientInterface;
use Exception;
use PDO;

class Fila{
    protected $requisicao;
    protected $situacao;
    protected $cod_transacao;
    protected $conexao;
    public AwsClientInterface $sqsClient;
    public string $sqsEndpoint;
   
    public function __construct(pdo $conexao, array $transferencia, string $type)
    {  
        $this->requisicao = json_encode($transferencia);
        $this->cod_transacao = $transferencia['data']['id'];
        $this->situacao = $type;
        $this->conexao = $conexao;
    }
    
    public function adiciona_fila(){
        $sql="INSERT INTO fila_processo_webhook(cod_transacao, situacao, requisicao)VALUES('{$this->cod_transacao}','{$this->situacao}','{$this->requisicao}');";
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute();

        $this->sqsClient->sendMessage([
            'QueueUrl' => $this->sqsEndpoint,
            'MessageBody' => $this->requisicao,
            'MessageGroupId' => uniqid(rand(), true),
            'MessageDeduplicationId' => uniqid(rand(), true)
        ]);
    }  
}
?>