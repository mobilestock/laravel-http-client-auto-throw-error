<?php

namespace MobileStock\model;



class Notificacao implements ModelInterface, \JsonSerializable
{
    public $nome_tabela = "notificacoes";
    private $id;
    private $id_cliente;
    private $data_evento;
    private $mensagem;
    private $recebida;
    private $tipo_frete;
    private $tipo_mensagem;

    public static function hidratar(array $dados): ModelInterface
    {
        if(empty($dados)){
            throw new \InvalidArgumentException('Dados inválidos');
        }    

        $notificacao = new self();

        foreach ($dados as $key => $dado){
            $notificacao->$key = $dado;
        }

        return $notificacao;
    }

    public function extrair():array
    {
     return get_object_vars($this);  
    }

    /**
     * Get the value of tipo_mensagem
     */ 
    public function getTipoMensagem()
    {
        return $this->tipo_mensagem;
    }

    /**
     * Set the value of tipo_mensagem
     *
     * @return  self
     */ 
    public function setTipoMensagem($tipo_mensagem)
    {
        $this->tipo_mensagem = $tipo_mensagem;

        return $this;
    }

    /**
     * Get the value of tipo_frete
     */ 
    public function getTipoFrete()
    {
        return $this->tipo_frete;
    }

    /**
     * Set the value of tipo_frete
     *
     * @return  self
     */ 
    public function setTipoFrete($tipo_frete)
    {
        $this->tipo_frete = $tipo_frete;

        return $this;
    }

    /**
     * Get the value of recebido
     */ 
    public function getRecebida()
    {
        return $this->recebida;
    }

    /**
     * Set the value of recebido
     *
     * @return  self
     */ 
    public function setRecebida($recebida)
    {
        $this->recebida = $recebida;

        return $this;
    }

    /**
     * Get the value of mensagem
     */ 
    public function getMensagem()
    {
        return $this->mensagem;
    }

    /**
     * Set the value of mensagem
     *
     * @return  self
     */ 
    public function setMensagem($mensagem)
    {
        $this->mensagem = $mensagem;

        return $this;
    }

    /**
     * Get the value of data_evento
     */ 
    public function getDataEvento()
    {
        return $this->data_evento;
    }

    /**
     * Set the value of data_evento
     *
     * @return  self
     */ 
    public function setDataEvento($data_evento)
    {
        $this->data_evento = $data_evento;

        return $this;
    }

    /**
     * Get the value of id_cliente
     */ 
    public function getIdCliente()
    {
        return $this->id_cliente;
    }

    /**
     * Set the value of id_cliente
     *
     * @return  self
     */ 
    public function setIdCliente($id_cliente)
    {
        $this->id_cliente = $id_cliente;

        return $this;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }    
    
    public function jsonSerialize()
    {
     return get_object_vars($this); 
    }
}
