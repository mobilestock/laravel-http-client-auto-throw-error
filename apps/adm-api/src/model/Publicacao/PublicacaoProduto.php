<?php

namespace MobileStock\model\Publicacao;

class PublicacaoProduto
{
    public $nome_tabela = 'publicacoes_produtos';
    protected int $id;
    protected int $id_publicacao;
    protected int $id_produto;
    protected string $uuid;
    protected string $foto_publicacao;

    public function __construct()
    {
        $this->id = 0;
    }

    public function __set($atrib, $value)
    {
        $this->$atrib = $value;
    } 

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    public function extrair()
    {
        return [
            'id_publicacao' => $this->id_publicacao,
            'id_produto' => $this->id_produto,
            'foto_publicacao' => $this->foto_publicacao,
            'uuid' => $this->uuid,
        ];
    }
}