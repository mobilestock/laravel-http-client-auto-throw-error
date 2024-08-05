<?php

namespace MobileStock\model\Pedido;

use Exception;

/**
 * @property int    $id_produto
 * @property int    $id_cliente
 * @property array  $grade
 * @property float  $preco
 * @property string $situacao
 * @property string $cliente
 * @property string $uuid
 * @property string $observacao
 * @property array  $array_uuid
 * @property int    $id_cliente_final
 * @property ?int   $id_transacao
 * @property ?string $observacao
 * @property string $data_atualizacao
 */
class PedidoItem
{
    // protected $id_produto;
    // protected $id_cliente;
    // protected $grade;
    // protected $preco;
    // protected $cliente;
    // protected $uuid;
    // protected $premio;
    // protected $observacao;
    // protected $array_uuid;
    // protected $id_cliente_final;
    // protected ?int $id_transacao;

    public function __set($atrib, $value)
    {
        if ($value || $value === '0') {
            $this->$atrib = $value;
            switch ($atrib) {
                case 'situacao':
                    $this->validaSituacao();
                    break;
                case 'preco':
                    $this->validaPreco();
                    break;
            }
        } else {
            $this->$atrib = null;
        }
    }

    //public function __get($atrib)
    //{
    //    return $this->$atrib;
    //}

    protected function validaSituacao()
    {
        if (!in_array($this->situacao, ['1', '2', 'DI', 'FR'])) {
            throw new Exception('Situação não é permitida', 1);
        }
    }
    // protected function validaIdProduto()
    // {
    //     $this->id = (int) $this->id;
    // }

    protected function validaPreco()
    {
        $this->preco = (float) $this->preco;
    }
}
