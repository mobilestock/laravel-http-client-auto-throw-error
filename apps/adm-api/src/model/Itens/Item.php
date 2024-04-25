<?php

namespace MobileStock\model\Itens;

class Item
{
    /**
     * Class Item
     * @package MobileStock\Model
     * @property-read int $idproduto
     * @property-read string $descricao
     * @property-read int $tamanho
     * @property-read string $categorias
     * @property-read string $nometamanho
     * @property-read string $nomecomercial
     * @property-read float $preco
     * @property-read string $datahora
     * @property-read string $cliente
     * @property-read string $foto
     * @property-read string $uuid
     * @property-write string $observacao
     */

    protected $idproduto;
    protected $descricao;
    // protected $tamanho;
    protected $categorias;
    protected $nometamanho;
    protected $nomecomercial;
    protected $preco;
    protected $datahora;
    protected $cliente;
    protected $foto;
    protected $uuid;
    protected $observacao;


    function __construct(int $idproduto, string $descricao, string $categorias, string $nometamanho, string $nomecomercial, float $preco, string $datahora, string $cliente, string $foto, string $uuid, string $observacao)
    {
        $this->idproduto = $idproduto;
        $this->descricao = $descricao;
        $this->categorias = $categorias;
        $this->nometamanho = $nometamanho;
        $this->nomecomercial = $nomecomercial;
        $this->preco = $preco;
        $this->datahora = $datahora;
        $this->cliente = $cliente;
        $this->foto = $foto;
        $this->uuid = $uuid;
        $this->datahora = $datahora;
        $this->observacao = $observacao;
    }

    public function __get($attr)
    {
        $method = 'get' . $attr;
        return $this . $method();
    }

    public function __set($attr, $value)
    {
        $method = 'set' . $attr;
        return $this . $method($value);
    }

    public function getidproduto()
    {
        return $this->idproduto;
    }
    public function getdescricao()
    {
        return $this->descricao;
    }
    // public function gettamanho()
    // {
    //     return $this->tamanho;
    // }

    public function getnometamanho()
    {
        return $this->nometamanho;
    }
    public function getnomecomercial()
    {
        return $this->nomecomercial;
    }
    public function getcategorias()
    {
        return $this->categorias;
    }
    public function getpreco()
    {
        return $this->preco;
    }
    public function getdatahora()
    {
        return $this->datahora;
    }
    public function getcliente()
    {
        return $this->cliente;
    }
    public function getfoto()
    {
        return $this->foto;
    }
    public function getuuid()
    {
        return $this->uuid;
    }
    public function getobservacao()
    {
        return $this->observacao;
    }
}
