<?php

namespace MobileStock\model\Publicacao;

class Publicacao implements \JsonSerializable
{
    public $nome_tabela = 'publicacoes';
    protected int $id;
    protected int $id_colaborador;
    protected string $tipo_publicacao;
    protected string $descricao;
    protected string $situacao;
    protected string $foto;

    public function __construct() 
    {
        $this->id = 0;
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;     
        }else{
            if($atrib == 'descricao'){ return; }
            $this->$atrib = null;
        }
    } 

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    public function extrair()
    {
        return [
            'descricao' => $this->descricao ?? '',
            'id_colaborador' => $this->id_colaborador ?? '',
            'tipo_publicacao' => $this->tipo_publicacao ?? '',
            'foto' => $this->foto ?? '',
            'situacao' => $this->situacao ?? '',
            'id' => $this->id
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->extrair();   
    }
}