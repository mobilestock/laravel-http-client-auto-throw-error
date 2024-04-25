<?php

namespace api_webhooks\Controller;

use api_webhooks\Models\Conect;
use api_webhooks\Models\Notificacoes;
use api_webhooks\Models\Request_m;
use api_webhooks\Models\Transacao;
use Throwable;

class Transacoes extends Request_m
{

    private $conexao;
    public function __construct($rota)
    {
        parent::__construct();
        $this->rota = $rota;
        $this->conexao = Conect::conexao();
    }

    public function transacoes()
    {
        $this->conexao->beginTransaction();
        $json_transacao  =  json_decode($this->json, false);
        // $arquivo = fopen($json_transacao->type . "_" . date("YmdHis") . ".json", "a");
        // fwrite($arquivo, $this->json);
        // fclose($arquivo);
        try {
            $faturamento = new Transacao(
                $this->conexao,
                $json_transacao->type,
                $json_transacao->payload->object->id,
                $json_transacao->payload->object->status,
                $json_transacao->payload->object->amount
            );
            $faturamento->split_rules = isset($json_transacao->payload->object->split_rules)?$json_transacao->payload->object->split_rules:false;
            $faturamento->atualiza_faturamento($this->conexao);
            $this->conexao->commit();
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            Notificacoes::criaNotificacoes($this->conexao, "Erro ao receber transacao " . $json_transacao->payload->object->id . " mesagem " . $e->getMessage());
            $arquivo = fopen($json_transacao->type . "_" . date("YmdHis") . ".json", "a");
            fwrite($arquivo, $this->json);
            fclose($arquivo);
        } finally {
            $this->resposta->send();
        }
    }
}
