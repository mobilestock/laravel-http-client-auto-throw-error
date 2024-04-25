<?php

namespace MobileStock\model\Entrega;

use Exception;

class EntregasDevolucaoItemProblemas implements \JsonSerializable
{
    protected $id;
	protected $id_entrega;
	protected $id_entrega_devolucao_item;
    protected $id_usuario;
	protected $motivo = "defeito na etiqueta";
	protected $tipo;
	protected $data_atualizacao;
	protected $data_criacao;

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;     
            switch ($atrib) {
                case 'id':
                    $this->validaId();
                    break;
                case 'tipo':
                    $this->converteTipo();
                    break;
                case 'data_atualizacao':
                    $this->atualizaData();
                    break;
            }       
        }else{
            $this->$atrib = null;
        }
    } 
    protected function converteTipo()
    {
        $tipo = [
                        'Defeito'=>'DE',
                        'Ausente'=>'AU',
                        'DE'=>'DE',
                        'AU'=>'AU'];
        if (array_key_exists($this->tipo, $tipo)) {
            $this->tipo = $tipo[$this->tipo];
        }else{
            throw new Exception("Tipo invalido", 1);
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
    protected function atualizaData()
    {
        $this->data_atualizacao = 'NOW()';
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
    