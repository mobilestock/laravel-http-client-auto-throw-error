<?php

namespace MobileStock\model\RegraPagamentoSeller;

use Exception;

class Emprestimo implements \JsonSerializable
{
    protected $id;
	protected $id_favorecido;
	protected $id_conta_bancaria_favorecida;
	protected $id_lancamento;
	protected $valor_capital;
	protected $valor_atual;
	protected $taxa;
	protected $situacao;
	protected $data_criacao;
	protected $data_atualizacao;


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