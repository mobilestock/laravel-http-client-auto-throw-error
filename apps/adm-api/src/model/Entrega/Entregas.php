<?php

namespace MobileStock\model\Entrega;

use Exception;

/**
 * @deprecated utilizar o model de entrega
 */
class Entregas implements \JsonSerializable
{
    protected $id;
    protected $id_usuario;
    protected $id_cliente;
    protected $id_tipo_frete;
    protected $id_transporte;
    protected $id_localizacao;
    protected $id_cidade;
    protected $id_lancamento;
    protected $situacao;
    protected $qtd_itens;
    protected $volumes = 1;
    protected $uuid_entrega;
    protected $observacao;
    protected $data_entrega;
    protected $data_criacao;
    protected $data_atualizacao;
    public const SITUACAO_EXPEDICAO = 2;

    public function __set($campo, $valor)
    {
        if ($valor || $valor === '0') {
            $this->$campo = $valor;
            switch ($campo) {
                case 'situacao':
                    $this->converteSituacao();
                    break;
                case in_array($campo, [
                    'id',
                    'id_usuario',
                    'id_cliente',
                    'id_tipo_frete',
                    'id_transporte',
                    'id_localizacao',
                    'id_lancamento',
                    'qtd_itens',
                    'volumes',
                ]):
                    $this->validaInt($campo, $valor);
                    break;
            }
        } else {
            $this->$campo = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    protected function converteSituacao()
    {
        $situacao = [
            'AB' => 'AB',
            'Aberto' => 'AB',
            'Expedicao' => 'EX',
            'Ponto Transporte' => 'PT',
            'Entregue' => 'EN',
            'EX' => 'EX',
            'PT' => 'PT',
            'EN' => 'EN',
        ];
        if (array_key_exists($this->situacao, $situacao)) {
            $this->situacao = $situacao[$this->situacao];
        } else {
            throw new Exception('Situacao invalido', 1);
        }
    }

    protected function validaInt($campo, $valor)
    {
        $this->$campo = (int) $valor;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
