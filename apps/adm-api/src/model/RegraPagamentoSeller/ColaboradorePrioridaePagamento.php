<?php

namespace MobileStock\model\RegraPagamentoSeller;

use Exception;

/**
 * @deprecated
 */
class ColaboradorePrioridaePagamento implements \JsonSerializable
{
    protected $id;
	protected $id_colaborador;
	protected $id_conta_bancaria;
	protected $valor_pagamento;
	protected $valor_pago;
	protected $data_criacao;
	protected $data_atualizacao;
	protected $usuario;
	protected $situacao;
	protected $id_transferencia;


    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'id':
                    $this->validaId();
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

    protected function validaId()
    {
        $this->id = intval($this->id);
    }
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}