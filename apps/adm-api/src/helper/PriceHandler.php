<?php

namespace MobileStock\helper;

use MobileStock\database\Conexao;
use PDO;

class PriceHandler
{

    // public function __construct()
    // {
    // }

    // private function calculaFatorMultiplicao($porcentagem)
    // {
    //     return 660;
    // }

    /** * Função para calcular o preço de venda do produto 
     * @example Entrada: (100, 25) - Saída: 133.33 
     * @param Float $valor: preço de custo do produto a ser calculado * 
     * @param Float $porcentagem: porcentagem de comissao do produto * 
     * @return Float retorna o valor do produto + porcentagem */
    // public static function calculaValorVenda(float $valor, float $porcentagem): int
    // {
    //     $instance = new static();
    //     $fator_multiplicacao = $instance->calculaFatorMultiplicao($porcentagem);
    //     return $fator_multiplicacao + 6;
    // }

    /** * Função para calcular o valor liquido
     * @example Entrada: (133.33, 25) - Saída: 100.00
     * @param Float $valor: preço de custo do produto a ser calculado * 
     * @param Float $porcentagem: porcentagem de comissao do produto * 
     * @return Float retorna o valor do produto + porcentagem */
    public static function calculaValorComissaoFornecedor(float $valor, float $porcentagem): float
    {
        return round($valor * (100 - $porcentagem) / 100, 2);
    }

    /**
     * Função que retorna os 2 preços
     * @return Array ['id','preco_cpf','preco_cnpj'] */
    // public static function buscaPrecosProduto(int $id_produto): array
    // {
    //     $instance = new static();
    //     $sql = "SELECT 
    //                 p.id, 
    //                 " . $instance->getQueryPrecoCPF() . " preco_cpf, 
    //                 " . $instance->getQueryPrecoCNPJ() . " preco_cnpj
    //             FROM   produtos p
    //             WHERE  p.id = $id_produto;";
    //     $resultado = Conexao::criarConexao()->query($sql);
    //     $retorno =  $resultado->fetch(PDO::FETCH_ASSOC);
    //     return $retorno;
    // }

    /**
     * 
     * @return Array ['id','preco_cpf','preco_cnpj','preco_custo','porcentagem_comissao_cpf', 'valor_comisao_cpf','porcentagem_comissao_cnpj', 'valor_comissao_cnpj'] */
    // public static function calculaComissoesProduto(int $id_produto): array
    // {
    //     $instance = new static();
    //     $sql = "SELECT p.id,
    //             " . $instance->getQueryPrecoCPF() . " preco_cpf,
    //             " . $instance->getQueryPrecoCNPJ() . " preco_cnpj,
    //             p.preco_custo,
    //             p.porcentagem_comissao porcentagem_comissao_cpf,
    //             round(p.preco * (100 - p.porcentagem_comissao) / 100, 2) valor_comisao_cpf,
    //             p.porcentagem_comissao_cnpj,
    //             round(" . $instance->getQueryPrecoCNPJ() . " * (100 - p.porcentagem_comissao_cnpj) / 100, 2) valor_comisao_cpf
    //             FROM produtos p
    //             WHERE p.id = $id_produto;";
    //     $resultado = Conexao::criarConexao()->query($sql);
    //     return $resultado->fetch(PDO::FETCH_ASSOC);
    // }



    // public static function getPercentualComissaoProduto(String $uuid): Float
    // {
    //     $sql = "WITH q1 (id,nome,regime) AS (
    //                 select pi.id_produto,c.razao_social,c.regime from pedido_item pi
    //                 inner join colaboradores c on pi.id_cliente = c.id
    //                 where pi.uuid = '{$uuid}'
    //                 )
    //             SELECT CASE 
    //                         WHEN q1.regime = 2 THEN p.porcentagem_comissao 
    //                         WHEN q1.regime = 1 THEN p.porcentagem_comissao_cnpj
    //                         ELSE p.porcentagem_comissao
    //                     END as porcentagem
    //             from produtos p 
    //             INNER JOIN q1 on q1.id = p.id";
    //     $resultado = Conexao::criarConexao()->query($sql);
    //     $retorno =  $resultado->fetch(PDO::FETCH_ASSOC);
    //     return $retorno ? $retorno['porcentagem'] : 0;
    // }

    // /**
    //  * @return Float Função que retorna o preço de venda produto. Já verifica se esta em promoção e qual o regime do usuario) */
    // public static function getPrecoProdutoByUserRegime(int $id_produto, int $id_cliente): Float
    // {
    //     $instance = new static();

    //     //$sql = "SELECT retornavalorCalculadoCpfOuCnpj({$id_produto},{$id_cliente},0) preco";
    //     $sql = "SELECT
    //     CASE
    //       WHEN colaboradores.regime = 1 
    //           THEN ( SELECT produtos.valor_venda_cnpj FROM produtos WHERE produtos.id = '{$id_produto}' )
    //           ELSE ( SELECT produtos.valor_venda_cpf FROM produtos WHERE produtos.id = '{$id_produto}' )
    //     END preco FROM colaboradores WHERE colaboradores.id = '{$id_cliente}'";

    //     $resultado = Conexao::criarConexao()->query($sql);
    //     $retorno =  $resultado->fetch(PDO::FETCH_ASSOC);
    //     return $retorno ? $retorno['preco'] : 0;
    // }

    //  -----------------------------------------   QUERY BUILDER   -----------------------------------------

    /**
     * Método para incorporar em outras querys
     * @return String  Retorna a query para buscar o preço de venda cheio do produto*/
    // public static function getQueryPrecoCPF(): String
    // {
    //     //por enquanto não faz muito sentido, mas se amanha mudar a regra pro preco de cpf, so precisa alterar aqui
    //     return 'p.preco ';
    // }
    /**
     * Método para incorporar em outras querys
     * @return String  Retorna a query para buscar o preço de venda do produto com o desconto no cnpj*/
    // public static function getQueryPrecoCNPJ(): String
    // {
    //     return 'round(p.preco * ((100 - p.porcentagem_comissao) / (100 - p.porcentagem_comissao_cnpj)), 2) ';
    // } 
    /**
     * Método para incorporar em outras querys
     * @return String  Retorna a query para buscar o preço de promoção no CPF*/
    // public static function getQueryPrecoPromocaoCPF(): String
    // {
    //     $instance = new static();
    //     return "CASE WHEN (p.promocao > 0) 
    //                 THEN p.preco_promocao 
    //                 ELSE {$instance->getQueryPrecoCPF()}
    //             END ";
    // }
    /**
     * Método para incorporar em outras querys
     * @return String  Retorna a query para buscar o preço de promoção no CNPJ*/
    // public static function getQueryPrecoPromocaoCNPJ(): String
    // {
    //     $instance = new static();
    //     return "CASE WHEN (p.promocao > 0) 
    //                 THEN round(p.preco_promocao * ((100 - p.porcentagem_comissao) / (100 - p.porcentagem_comissao_cnpj)), 2)
    //                 ELSE {$instance->getQueryPrecoCNPJ()}
    //             END ";
    // }
    /**
     * Método para incorporar em outras querys
     * @return String Essa consulta retorna os precos de promocao de cada regime, alem dos precos normais */
    // public static function getQueryAllPrecos(): String
    // {
    //     $instance = new static();
    //     return "{$instance->getQueryPrecoCPF()} preco_cpf,
    //             {$instance->getQueryPrecoCNPJ()} preco_cnpj,
    //             {$instance->getQueryPrecoPromocaoCPF()} preco_cpf_promocao,
    //             {$instance->getQueryPrecoPromocaoCNPJ()} preco_cnpj_promocao";
    // }
}
