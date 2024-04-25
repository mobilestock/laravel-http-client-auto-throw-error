<?php
/*
namespace api_cliente\Controller;

use Exception;
use api_cliente\Models\Conect;
use api_cliente\Models\Request_m;
use MobileStock\service\MessageService;

class Mensageria extends Request_m
{
    public function __construct($rota)
    {
        $this->nivelAcesso = '0';
        parent::__construct();
        $this->rota = $rota;
        $this->conexao = Conect::conexao();
    }

    public function callbackLambda()
    {
        try {
            $json = json_decode($this->json, true);
            if ($json['auth'] != "3LStcmTcjmbGU8dg4tSx") {
                throw new Exception("Não autorizado");
            }
            $messageService = new MessageService();
            $messageService->callbackUpdate($this->conexao, $json['state'], $json['id']);
        } catch (\Throwable $e) {
            $this->retorno['data'] = $e->getMessage();
        }
        finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode(200)->send();
            die;
        }
    }

}
*/
?>