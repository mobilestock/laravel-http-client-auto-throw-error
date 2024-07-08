<?php
namespace MobileStock\model;

use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\PriceHandler;
use PDO;

/**
 * @deprecated
 * @see Usar: MobileStock\model\TaxasModel
 */
class Taxas
{

    private $numero_parcelas;
    private $bandeira;
    private $taxa;
    private $cartoes;
    private $quantidade_items;
    private $juros;
    private $juros_fornecedor;
    private $boleto;
    /**
     * 1 - a prazo
     * 2- a vista
     * 3- boleto
     */
    private $tipo_pagameto;

    public function __construct()
    {
        $this->cartoes =  [
            "american_express" => "/^3[47][0-9]{13}/",
            "diners" => "/^3(?:0[0-5]|[68][0-9])[0-9]{11}/",
            "elo" => "/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/",
            "elo" => "/^6(?:011|5[0-9]{2})[0-9]{12}$/",
            "hiper" => "/^(606282\d{10}(\d{3})?)|(3841\d{15})/",
            "JCB" => "/^(?:2131|1800|35\d{3})\d{11}/",
            "mastercard" => "/^5[1-5][0-9]{14}/",
            "visa" => "/^4[0-9]{12}(?:[0-9]{3})/"
        ];
    }

    public static function  __aPrazo(string $numero_cartao, int $numero_parcelas)
    {
        $instancia = new self();
        $instancia->numero_parcelas = $numero_parcelas;
        $instancia->tipo_pagameto = 1;
        //$instancia->getJuros();
        /*if ($instancia->testaCartao($numero_cartao)) {
            $instancia->getValorTaxa();
        } else {
            throw new Exception("Erro ao processar o cartão", 1);
        }*/
        return $instancia;
    }

    public static function  __aVista(int $quantidade_items)
    {
        $instancia = new self();
        $instancia->quantidade_items = $quantidade_items;
        $instancia->numero_parcelas = 0;
        $instancia->bandeira = 'dinheiro';
        $instancia->tipo_pagameto = 2;
        return $instancia;
    }

    public static function  __aVistaBoleto(int $quantidade_items)
    {
        $instancia = new self();
        $instancia->quantidade_items = $quantidade_items;
        $instancia->numero_parcelas = 1;
        $instancia->bandeira = 'boleto';
        $instancia->tipo_pagameto = 3;
        $instancia->getValorTaxa();
        return $instancia;
    }

    // ---------------------------------------- GETTERS AND SETTERS  ----------------------------------------
    public function getNumeroParcelas()
    {
        return $this->numero_parcelas;
    }

    public function SetTipo_pagameto()
    {
       $this->numero_parcelas;
    }

    public function getBandeira()
    {
        return $this->bandeira;
    }

    public function getTaxa()
    {
        return $this->taxa;
    }


    // ----------------------------------------   METHODS    --------------------------------------------------

    private function getValorTaxa()
    {
        $conexao = Conexao::criarConexao();
        $sql = "SELECT {$this->bandeira} as taxa from taxas where numero_de_parcelas = {$this->numero_parcelas}";
        $resultado = $conexao->query($sql);
        if ($result = $resultado->fetch(PDO::FETCH_ASSOC)) {
            return $this->taxa = $result['taxa'];
        } else {
            return false;
        }
    }
    public function SetJuros(PDO $conexao,$numero_parcelas)
    {
        if( !$numero_parcelas )
        {
            $numero_parcelas = 1;
        }
        $sql = "SELECT ROUND(if(taxas.juros > taxas.Juros_fixo_mobile,taxas.juros - taxas.Juros_fixo_mobile,0) * (taxas.Juros_para_fornecedor / 100),2)  juros_fornecedor,
                        taxas.juros,
                        taxas.boleto
                FROM taxas WHERE taxas.numero_de_parcelas = {$numero_parcelas}";
        $resultado = $conexao->query($sql);
        if ($result = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $this->juros = $result['juros'];
            $this->juros_fornecedor = $result['juros_fornecedor'];
            $this->boleto = $result['boleto'];
        } else {
            throw new Exception("Erro para buscar juros", 1);

        }
    }

    /**
     * @deprecated
     * Utilizar a função buscaPorcentagemJuros
     */
    public static function buscaListaJuros()
    {
        $sql = "SELECT * FROM taxas ORDER BY numero_de_parcelas;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($sql);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    public function testaCartao(string $numero_cartao)
    {
        foreach ($this->cartoes as $cartao => $regex) {
            if (preg_match($regex, $numero_cartao)) {
                return $this->bandeira = $cartao;
            }
        }
        return false;
    }

    public function adicionaListaDeTaxas(array $lista)
    {
        if (!sizeof($lista) > 0) return false;
        $valid = true;
        foreach ($lista as $key => $item) {
            if (
                !isset($item['numero_de_parcelas']) ||
                !is_int((int)$item['numero_de_parcelas']) ||
                !isset($item['juros']) ||
                !is_float((float)$item['juros']) ||
                !isset($item['mastercard']) ||
                !is_float((float)$item['mastercard']) ||
                !isset($item['visa']) ||
                !is_float((float)$item['visa']) ||
                !isset($item['elo']) ||
                !is_float((float)$item['elo']) ||
                !isset($item['american_express']) ||
                !is_float((float)$item['american_express']) ||
                !isset($item['hiper']) ||
                !is_float((float)$item['hiper']) ||
                !isset($item['boleto']) ||
                !is_float((float)$item['boleto'])
            ) {
                $valid = false;
                exit;
            };
        }
        if (!$valid) return false;
        $sql = "TRUNCATE table taxas;";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($sql);
        if ($stmt->execute()) {
            foreach ($lista as $key => $item) {
                $this->adicionaTaxa((int)$item['numero_de_parcelas'], (float)$item['juros'], (float)$item['mastercard'], (float)$item['visa'], (float)$item['elo'], (float)$item['american_express'], (float)$item['hiper'], (float)$item['boleto']);
            }
        } else {
            return false;
        }

        return true;
    }

    public function adicionaTaxa(int $numero_parcelas, float $juros, float $mastercard, float $visa, float $elo, float $american_express, float $hiper, float $boleto)
    {
        $sql = "INSERT INTO taxas( numero_de_parcelas ,juros ,mastercard ,visa ,elo ,american_express ,hiper ,boleto)
                VALUES ( $numero_parcelas,$juros ,$mastercard ,$visa,$elo,$american_express,$hiper,$boleto)";

        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($sql);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeTaxa(int $id)
    {
        $sql = "DELETE from taxas where id = $id";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($sql);
        return $stmt->execute();
    }


    public function getBoleto()
    {
        return $this->boleto;
    }
    public function getJuros()
    {
        return $this->juros;
    }
    public function getJurosFornecedor()
    {
        return $this->juros_fornecedor;
    }

    public static function buscaPorcentagemJuros(\PDO $conexao): array
    {
        $sql = $conexao->prepare(
            "SELECT
                taxas.numero_de_parcelas,
                taxas.juros
            FROM taxas
            ORDER BY numero_de_parcelas;"
        );
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
}
