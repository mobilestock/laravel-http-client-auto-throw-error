<?php

namespace MobileStock\model\TransacaoFinanceira;

use Exception;

/**
 * @property int $id_cliente
 * @property int $id_nova_transacao
 */
class TransacaoFinanceirasProdutosTroca implements \JsonSerializable
{
    protected $id;
    protected $id_transacao;
    protected $uuid;
    protected $situacao;

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'situacao':
                    $this->converteSituacao();
                    break;
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

    protected function converteSituacao()
    {
        $situacao = [
                        'Pendente'=>'PE',
                        'Pago'=>'PA',
                        'PE'=>'PE',
                        'PA'=>'PA'
                    ];
        if (array_key_exists($this->status, $situacao)) {
            $this->status = $situacao[$this->status];
        }else{
            throw new Exception("Situacao invalido", 1);
        }
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}