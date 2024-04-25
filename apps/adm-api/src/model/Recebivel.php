<?php

namespace MobileStock\model;


class Recebivel
{
    protected $id;
    protected $id_lancamento;
    protected $id_zoop_recebivel;
    protected $situacao;
    protected $id_zoop_split;
    protected $id_recebedor;
    protected $num_parcela;
    protected $valor_pago;
    protected $valor;
    protected $data_pagamento;
    protected $data_vencimento;
    protected $data_gerad;
    protected $id_transacao;
    protected $cod_transacao;

    public function __set($atrib, $value)
    {
        if ($value || $atrib === 'valor_pago') {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'situacao':
                    $this->converteSituacao();
                    break;
            }
        }else{
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    protected function converteSituacao()
    {
        $situacao = ['pending' => 'PE', 'paid' => 'PA', 'canceled' => 'CA', 'refunded' => 'RE', 'deleted' => 'DE'];
        if (array_key_exists($this->situacao, $situacao)) {
            $this->situacao = $situacao[$this->situacao];
        }
    }
}
