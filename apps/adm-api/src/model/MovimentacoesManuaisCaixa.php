<?php

namespace MobileStock\model;

class MovimentacoesManuaisCaixa implements ModelInterface
{

    public $nome_tabela = "movimentacoes_manuais_caixa";
    private $id;
    private $tipo;
    private $valor;
    private $motivo;
    private $responsavel;
    private $criado_em;
    private $conferido_por;
    private $conferido_em;


    public function setId ($id)
    {
        $this->id = $id;
    }
    public function setTipo ($tipo)
    {
        $this->tipo = $tipo;
    }
    public function setValor ($valor)
    {
        $this->valor = $valor;
    }
    public function setMotivo ($motivo)
    {
        $this->motivo = $motivo;
    }
    public function setResponsavel ($responsavel)
    {
        $this->responsavel = $responsavel;
    }
    public function setCriadoEm ($criado_em)
    {
        $this->criado_em = $criado_em;
    }
    public function setConferidoPor ($conferido_por)
    {
        $this->conferido_por = $conferido_por;
    }
    public function setConferidoEm ($conferido_em)
    {
        $this->conferido_em = $conferido_em;
    }

    public function getId ()
    {
        return $this->id;
    }
    public function getTipo ()
    {
        return $this->tipo;
    }
    public function getValor ()
    {
        return $this->valor;
    }
    public function getMotivo ()
    {
        return $this->motivo;
    }
    public function getResponsavel ()
    {
        return $this->responsavel;
    }
    public function getCriadoEm ()
    {
        return $this->criado_em;
    }
    public function getConferidoPor ()
    {
        return $this->conferido_por;
    }
    public function getConferidoEm ()
    {
        return $this->conferido_em;
    }
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

}