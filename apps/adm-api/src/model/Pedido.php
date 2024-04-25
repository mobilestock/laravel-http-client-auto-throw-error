<?php
namespace MobileStock\model;

class Pedido
{
    /**
     * Class Pedido
     * @package MobileStock\model
     * @property int $idCliente
     * @property int $idUsuario
     * @property float $valorProdutos
     * @property string $observacoes
     * @property int $tipoPagamento
     * @property int $pares
     * @property int $tipoFrete
     * @property float $valorFrete
     * @property string $dataEmissao
     * @property string $contaDeposito
     * @property int $transportadora
     * @property int $expedido
     * @property int $prioridade
     * @property int $freteiro
     */

    private $idCliente;
    private $idUsuario;
    private $valorProdutos;
    private $observacoes;
    private $tipoPagamento;
    private $pares;
    private $tipoFrete;
    private $valorFrete;
    private $dataEmissao;
    private $contaDeposito;
    private $transportadora;
    private $expedido;
    private $prioridade;
    private $freteiro;
    private $acrescimo;

    public function __construct(int $idCliente, int $idUsuario, float $valorProdutos, string $observacoes, int $tipoPagamento, int $pares, int $tipoFrete, 
    float $valorFrete, string $dataEmissao, string $contaDeposito, int $transportadora, int $expedido, int $prioridade, string $contaBancaria, int $freteiro, float $acrescimo) 
    {
        $this->idCliente = $idCliente;
        $this->idUsuario = $idUsuario;
        $this->valorProdutos = $valorProdutos;
        $this->observacoes = $observacoes;
        $this->tipoPagamento = $tipoPagamento;
        $this->pares = $pares;
        $this->tipoFrete = $tipoFrete;
        $this->valorFrete = $valorFrete;
        $this->dataEmissao = $dataEmissao;
        $this->contaDeposito = $contaDeposito;
        $this->transportadora = $transportadora;
        $this->expedido = $expedido;
        $this->prioridade = $prioridade;
        $this->freteiro = $freteiro;
        $this->acrescimo = $acrescimo;
    }

    public function recuperaAcrescimo():float
    {
        return $this->acrescimo;
    }
    public function recuperaFreteiro():int
    {
        return $this->freteiro;
    }
    public function recuperaPrioridade():int
    {
        return $this->prioridade;
    }
    public function recuperaExpedido():int
    {
        return $this->expedido;
    }
    public function recuperaTransportadora():int
    {
        return $this->transportadora;
    }
    public function recuperaContaDeposito():string
    {
        return $this->contaDeposito;
    }
    public function recuperaDataEmissao():string
    {
        return $this->dataEmissao;
    }
    public function recuperaValorFrete():float
    {
        return $this->valorFrete;
    }
    public function recuperaTipoFrete():int
    {
        return $this->tipoFrete;
    }
    public function recuperaPares():int
    {
        return $this->pares;
    }
    public function recuperaTipoPagamento():int
    {
        return $this->tipoPagamento;
    }
    public function recuperaObservacoes():string
    {
        return $this->observacoes;
    }
    public function recuperaValorProdutos():float
    {
        return $this->valorProdutos;
    }
    public function recuperaIdUsuario():int
    {
        return $this->idUsuario;
    }
    public function recuperaIdCliente():int
    {
        return $this->idCliente;
    }

    public function __get($atributo)
    {
            $metodo = 'recupera'.ucfirst($atributo);
            return $this->$metodo();
    }

    public function __set($atributo, $valor):void
    {
        $metodo = 'insere'.ucfirst($atributo);
        $this->$metodo($valor);
    }
}