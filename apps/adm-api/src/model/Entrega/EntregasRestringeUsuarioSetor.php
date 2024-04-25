<?php

namespace MobileStock\model\Entrega;


class EntregasRestringeUsuarioSetor implements \JsonSerializable
{
    protected $id;
	protected $id_usuario;
	protected $tipo_frete;
	protected $transporte;
	protected $id_cliente;
	protected $id_cliente_consumidor; // caso entrega seja meu look, o id cliente consumidor sera o id do cliente do responsavel pela compra da entrega
	protected $situacao_entrega_anterior;
	protected $situacao_entrega_atual;
    

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
    