<?php

namespace api_webhooks\Controller;

use api_webhooks\Models\Conect;
use api_webhooks\Models\Notificacoes;
use api_webhooks\Models\Request_m;
use api_webhooks\Models\TransacaoIugu;
use Throwable;

class TransacoesIugu extends Request_m
{

    private $conexao;
    public function __construct($rota)
    {
        parent::__construct();
        $this->rota = $rota;
        $this->conexao = Conect::conexao();
    }

    public function transacoesIugo()
    {
        $this->executaIugu(/*3*/);
    }

    private function executaIugu(/*int $tentativas = 4*/){
        $this->conexao->beginTransaction();
        $arrayTransacao  =  $this->request->request->all();
        // $arquivo = fopen(uniqid(""). "_" . date("YmdHis") . ".txt", "a");
        // fwrite($arquivo, $this->json);
        // fclose($arquivo);
        try {
            $faturamento = new TransacaoIugu(
                $this->conexao,
                $arrayTransacao
            );
            $faturamento->atualizaFaturamentoIugo();
            $this->conexao->commit();
        } catch (Throwable $e) {
            $this->conexao->rollBack(); 
            // if ($tentativas > 0) {
            //     sleep(1);
            //     $this->executaIugu($tentativas-1);
            // }else{
                Notificacoes::criaNotificacoes($this->conexao, "Erro ao receber transacao da IUGO Evento.".$arrayTransacao['event']." (" . implode(" - ",$arrayTransacao['data']). "). Mesagem de erro " . $e->getMessage());
            // }
            
            $this->resposta->setStatusCode(400);
        } finally {
            $this->resposta->send();
            die;
        }
    }
}
