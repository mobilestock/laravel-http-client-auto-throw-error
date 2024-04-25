<?php

namespace MobileStock\model\Pedido;

class PedidoItemMeuLook
{
    protected int $id_produto;
    protected int $id_cliente;
    protected string $nome_tamanho;
    protected int $id_responsavel_estoque;
    protected int $id_publicacao_produto_origem;
    protected string $uuid;
    protected float $preco;
    protected string $situacao;

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
        }else{
            $this->$atrib = null;
        }
    }

    public function __get($atrib) 
    {
        return $this->$atrib;
    }
}
