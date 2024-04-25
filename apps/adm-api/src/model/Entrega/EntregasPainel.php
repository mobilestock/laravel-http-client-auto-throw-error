<?php

namespace MobileStock\model\Entrega;

class EntregasPainel implements \JsonSerializable
{
    protected $id;
    protected $id_localizacao;
    protected $id_usuario;
	protected $data_criacao;

    public function __set($campo, $valor)
    {
        if ($valor || $valor === "0") {
            $this->$campo = $valor;     
            if (in_array($campo,['id','id_localizacao','id_usuario'])){
                $this->validaInt($campo,$valor);
            }       
        }else{
            $this->$campo = null;
        }
    } 

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    protected function validaInt($campo,$valor)
    {
        $this->$campo = intval($valor);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
    