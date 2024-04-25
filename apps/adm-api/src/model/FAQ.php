<?php

namespace MobileStock\model;
use Exception;


class FAQ implements \JsonSerializable
{
    protected $id;
    protected $id_cliente;
    protected $id_usuario_responde;
    protected $pergunta;
    protected $resposta;
    protected $data_pergunta;
    protected $data_resposta;
    protected $tipo;
    protected $frequencia;
    protected $id_produto;
    protected $id_fornecedor;

    public function __construct()
    {
        $this->tipo = 'MP';
        $this->frequencia = 1;
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
        } else {
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

   
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
