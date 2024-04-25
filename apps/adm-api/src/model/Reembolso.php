<?php

namespace MobileStock\model;

class Reembolso
{
    protected $id;
    protected $id_recebedor;
    protected $id_pagador;
    protected $conta;
    protected $id_lancamento_origem;
    protected $valor;
    protected $situacao;
    protected $log_conta;
    protected $data_emissao;
    protected $data_pagamento;
    protected $id_atendimento;

    public function __set($atrib, $value)
    {
        if ($value) {
            $this->$atrib = $value;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }
}
