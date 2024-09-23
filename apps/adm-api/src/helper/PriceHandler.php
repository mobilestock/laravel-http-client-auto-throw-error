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
        return round(($valor * (100 - $porcentagem)) / 100, 2);
    }

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
}
