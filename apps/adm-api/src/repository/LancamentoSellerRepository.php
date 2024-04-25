<?php
/*
namespace MobileStock\repository;

use Exception;
use MobileStock\database\Conexao;
use PDO;

class LancamentoSellerRepository
{
    public function criar(array $lancamento, PDO $conexao = null)
    {
        $conexao = $conexao ?? Conexao::criarConexao();
        $query = '';

        $lancamento = array_filter($lancamento);
        $size = sizeof($lancamento);

        $count = 0;
        $query = "INSERT INTO lancamento_financeiro_seller (";
        foreach ($lancamento as $key => $l) {
            $count++;
            $query .= $size > $count ? $key . ", " : $key;
        }

        $count = 0;
        $query .= ")VALUES(";
        foreach ($lancamento as $key => $l) {
            $count++;
            $query .= $size > $count ? ":" . $key . ", " : ":" . $key;
        }

        $query .= ")";
        $sth = $conexao->prepare($query);
        foreach ($lancamento as $key => $l) {
            $sth->bindValue($key, $l, $this->typeof($l));
        }

        if (!$sth->execute()) {
            throw new Exception('Erro ao gerar lancamento seller', 1);
        }

        return true;
    }
    public function listar($filtro, $pagina, $items)
    {
        $conexao = Conexao::criarConexao();
        $sql = "SELECT *,(SELECT razao_social FROM colaboradores WHERE colaboradores.id = lancamento_financeiro_seller.id_recebedor)as razao_social FROM lancamento_financeiro_seller WHERE 1=1 {$filtro} LIMIT {$pagina},{$items};";
        $result = $conexao->prepare($sql);
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
    public function atualizar(array $lancamento)
    {
        $conexao = Conexao::criarConexao();
        $query = '';

        $lancamento = array_filter($lancamento);
        if (isset($lancamento['action'])) {
            $size = sizeof($lancamento) - 2;
        } else {
            $size = sizeof($lancamento) - 1;
        }

        $count = 0;
        $query = "UPDATE lancamento_financeiro_seller SET ";
        foreach ($lancamento as $key => $l) {

            if ($key != 'id' && $key != 'action') {
                $count++;
                $query .= $size > $count ? $key . "= :" . $key . ", " : $key . "= :" . $key;
            }
        }

        $count = 0;
        $query .= " WHERE ";

        $query .= "id =" . $lancamento['id'];


        $query .= ";";
        $sth = $conexao->prepare($query);
        foreach ($lancamento as $key => $l) {
            if ($key != 'id' && $key != 'action') {

                $sth->bindValue($key, $l, $this->typeof($l));
            }
        }

        if (!$sth->execute()) {
            throw new Exception('Erro ao gerar lancamento seller', 1);
        }

        return true;
    }
    public function deletar($id)
    {
        $conexao = Conexao::criarConexao();
        $sql = "DELETE FROM lancamento_financeiro_seller WHERE lancamento_financeiro_seller.id = {$id};";
        $result = $conexao->prepare($sql);
        if ($result->execute()) {
            return true;
        } else {
            return false;
        }
    }
    public function typeof($value)
    {
        switch (gettype($value)) {
            case 'float':
                return PDO::PARAM_STR;
                break;

            case 'double':
                return PDO::PARAM_STR;
                break;

            case 'string':
                return PDO::PARAM_STR;
                break;

            case 'integer':
                return PDO::PARAM_INT;
                break;
            default:
                return PDO::PARAM_STR;
                break;
        }
    }
    public function listaLancamentosEmAbertoFornecedor($filtro)
    {
        $query = "SELECT lf.id_colaborador, c.razao_social fornecedor, COUNT(lf.id) entradas, 
        SUM(lf.valor)valor, u.nome usuario, MAX(data_emissao) data_emissao
        FROM lancamento_financeiro_seller lf 
        INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
        INNER JOIN usuarios u ON (u.id = lf.id_usuario)
        WHERE lf.tipo='P' AND lf.situacao=1 AND c.tipo='F' {$filtro}
        GROUP BY lf.id_colaborador ORDER BY lf.data_emissao DESC LIMIT 20;";

        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $lista = $resultado->fetchAll();
        return $lista;
    }
    public function buscaValorTotalLancamentosAbertoFornecedor()
    {
        $query = "SELECT SUM(lf.valor) valor, SUM(lf.pares) pares FROM lancamento_financeiro_seller lf 
        INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
        LEFT OUTER JOIN movimentacao_estoque m ON (m.id = lf.numero_documento)
        WHERE lf.tipo='P' AND lf.situacao=1 AND c.tipo='F';";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch();
        return $linha;
    }
    // public function buscaLancamentosFornecedorEmAberto($id_fornecedor)
    // {
    //     $query = "SELECT lf.id, lf.compras, lf.valor, lf.id_colaborador, c.razao_social fornecedor, lf.pares,
    //     u.nome usuario, lf.data_emissao, lf.data_vencimento, lf.numero_movimento nMov FROM lancamento_financeiro_seller lf
    //     INNER JOIN colaboradores c ON (c.id = lf.id_colaborador)
    //     INNER JOIN usuarios u ON (u.id = lf.id_usuario)
    //     WHERE lf.tipo='P' AND lf.situacao=1
    //     AND lf.id_colaborador={$id_fornecedor} GROUP BY nMov;";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $lista = $resultado->fetchAll();
    //     return $lista;
    // }
    public function buscaLancamento($id)
    {
        $query = "SELECT lc.*, c.razao_social, s.nome nome_situacao, u.nome usuario FROM lancamento_financeiro_seller lc
      INNER JOIN colaboradores c ON (c.id=lc.id_colaborador)
      INNER JOIN usuarios u ON (u.id=lc.id_usuario)
      INNER JOIN situacao_lancamento s ON (s.id=lc.situacao)
      WHERE lc.id = {$id}";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linha = $resultado->fetch();
        return $linha;
    }
    public function atualizaLancamentoPagamentoFornecedor($numero, $id_acerto, $usuario)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $data_atual = Date('Y-m-d H:i:s');
        $query = "UPDATE lancamento_financeiro_seller SET situacao = 2, id_usuario_pag = {$usuario}, id_usuario_edicao = {$usuario}, acerto={$id_acerto}, data_pagamento='{$data_atual}' 
        WHERE id={$numero};";
        $conexao = Conexao::criarConexao();
        return $conexao->exec($query);
    }
    public function buscaListaAcertos($filtro)
    {
        $query = "SELECT 
        acertos.*,
        colaboradores.razao_social, 
        (SELECT SUM(acertos_documentos.valor) FROM acertos_documentos WHERE acertos_documentos.id_acerto = acertos.id) valor_total
        FROM acertos 
            INNER JOIN colaboradores ON (colaboradores.id=acertos.id_colaborador)
            INNER JOIN acertos_documentos ON (acertos_documentos.id_acerto=acertos.id)
            INNER JOIN lancamento_financeiro_seller ON (lancamento_financeiro_seller.acerto=acertos.id) 
        {$filtro} GROUP BY acertos.id 
        ORDER BY acertos.id DESC
        LIMIT 25";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $lista = $resultado->fetchAll();
        return $lista;
    }
}
*/