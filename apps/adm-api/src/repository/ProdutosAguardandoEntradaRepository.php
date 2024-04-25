<?php
/*

namespace MobileStock\repository;

use MobileStock\database\TraitConexao;
use PDO;

class ProdutosAguardandoEntradaRepository
{
    use TraitConexao;

    public function buscaEntradasAguardandoEntrada()
    {
        $query = "SELECT  produtos_aguarda_entrada_estoque.id_produto,
        GROUP_CONCAT(DISTINCT COALESCE((SELECT estoque_grade.nome_tamanho FROM estoque_grade WHERE estoque_grade.tamanho = produtos_aguarda_entrada_estoque.tamanho AND estoque_grade.id_produto = produtos_aguarda_entrada_estoque.id_produto LIMIT 1), produtos_aguarda_entrada_estoque.tamanho)) tamanho,
        produtos_aguarda_entrada_estoque.localizacao,
        produtos_aguarda_entrada_estoque.identificao,
        GROUP_CONCAT(DISTINCT CASE 
            WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'CO' THEN 'Compra'
            WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'FT' THEN 'Foto'
            WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'TR' THEN 'Troca'
            WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'PC' THEN 'Pedido Cancelado'
            ELSE 'NAO IDENTIFICADO'
        END) tipo_entrada,
        MAX(produtos_aguarda_entrada_estoque.data_hora) data_hora,
        GROUP_CONCAT(DISTINCT produtos_aguarda_entrada_estoque.usuario) usuario,
        SUM(produtos_aguarda_entrada_estoque.qtd) qtd,
        SUM(CASE WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'TR' THEN produtos_aguarda_entrada_estoque.qtd ELSE 0 END) qtd_troca,
        SUM(CASE WHEN produtos_aguarda_entrada_estoque.tipo_entrada = 'CO' THEN produtos_aguarda_entrada_estoque.qtd ELSE 0 END) qtd_compra,
       (SELECT usuarios.nome from usuarios where usuarios.id = produtos_aguarda_entrada_estoque.usuario_resp) usuario_resp,
       (SELECT produtos_grade_cod_barras.cod_barras 
            from produtos_grade_cod_barras 
            where produtos_grade_cod_barras.id_produto = MAX(produtos_aguarda_entrada_estoque.id_produto) LIMIT 1) cod_barras,
       CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) produto
    FROM produtos_aguarda_entrada_estoque
        INNER JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
    WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
    		 AND produtos_aguarda_entrada_estoque.tipo_entrada <> 'SP'
    GROUP BY produtos_aguarda_entrada_estoque.id_produto
    UNION ALL
    SELECT  produtos_aguarda_entrada_estoque.id_produto,
            produtos_aguarda_entrada_estoque.tamanho,
            produtos_aguarda_entrada_estoque.localizacao,
            produtos_aguarda_entrada_estoque.identificao,
            'Separar para foto' tipo_entrada,
            produtos_aguarda_entrada_estoque.data_hora,
            produtos_aguarda_entrada_estoque.usuario,
            produtos_aguarda_entrada_estoque.qtd,
            0 qtd_troca,
            0 qtd_compra,
        (SELECT usuarios.nome from usuarios where usuarios.id = produtos_aguarda_entrada_estoque.usuario_resp) usuario_resp,
        (SELECT produtos_grade_cod_barras.cod_barras 
                from produtos_grade_cod_barras 
                where produtos_grade_cod_barras.id_produto = produtos_aguarda_entrada_estoque.id_produto LIMIT 1) cod_barras,
        CONCAT(produtos.descricao, ' ', COALESCE(produtos.cores, '')) produto
        FROM produtos_aguarda_entrada_estoque
            INNER JOIN produtos ON produtos.id = produtos_aguarda_entrada_estoque.id_produto
        WHERE produtos_aguarda_entrada_estoque.em_estoque = 'F'
                AND produtos_aguarda_entrada_estoque.tipo_entrada = 'SP'
    ";

        $stmt = Conexao::criarConexao()->query($query);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }


    public function buscaEntradasParametro(string $pesquisa): array
    {
        $query = "SELECT   coalesce(produtos.descricao,0) produto 
                    FROM produtos
                    WHERE produtos.id = (SELECT produtos_grade_cod_barras.id_produto FROM produtos_grade_cod_barras where produtos_grade_cod_barras.cod_barras = '{$pesquisa}') 
                        OR produtos.id = (SELECT compras_itens_caixas.id_produto FROM compras_itens_caixas WHERE compras_itens_caixas.codigo_barras = '{$pesquisa}')";

        $stmt = $this->criarConexao()->query($query);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultado;
    }
}
*/