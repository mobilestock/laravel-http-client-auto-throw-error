<?php

namespace MobileStock\model\TransacaoFinanceira;

class TransacaoFinanceiraTentativaPagamento
{
    public int $id_transacao;
    public string $emissor_transacao;
    public string $cod_transacao;
    public string $mensagem_erro;
    public string $transacao_json;

    /**
     * @inheritDoc
     */
    public function extrair(): array
    {
        return [
            'id_transacao' => $this->id_transacao,
            'emissor_transacao' => $this->emissor_transacao,
            'cod_transacao' => $this->cod_transacao ?? null,
            'mensagem_erro' => str_replace("'", '"', $this->mensagem_erro ?? ''),
            'transacao_json' => $this->transacao_json
        ];
    }
}