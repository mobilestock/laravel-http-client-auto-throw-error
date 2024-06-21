<?php

function getAllLancamentosFornecedor($filtros)
{
    $offSet = $filtros['pagina'] ? $filtros['pagina'] * 5 - 5 : 0;
    $sql = "SELECT
  cf.*,
  (SELECT
    count(id_fornecedor)
  FROM
    controle_financeiro_fornecedores where id_fornecedor = cf.id_fornecedor)
    qtd_paginas,
  (SELECT sum(valor_lancamento) FROM controle_financeiro_fornecedores where id_fornecedor = cf.id_fornecedor) total
FROM
  controle_financeiro_fornecedores cf where id_fornecedor = {$filtros['fornecedor']} ORDER BY id DESC LIMIT 5 OFFSET {$offSet}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($sql);
    return $resultado->fetchAll(PDO::FETCH_ASSOC);
}

function novoLancamentoFinanceiro($lancamento)
{
    $sql = "INSERT INTO controle_financeiro_fornecedores(
            id_fornecedor,
            data,
            valor_lancamento,
            valor_total_produtos,
            saldo,
            novo_saldo,
            saldo_defeitos
          )VALUES(
              {$lancamento['id_fornecedor']}, -- id_fornecedor - IN int(11)
              '{$lancamento['data']}', -- data - IN datetime
              {$lancamento['valor_lancamento']}, -- lancamento - IN double
              {$lancamento['valor_total_produtos']}, -- valor_total_produtos - IN double
              {$lancamento['saldo']}, -- saldo - IN double
              {$lancamento['novo_saldo']}, -- saldo - IN double
              {$lancamento['saldo_defeitos']} -- saldo - IN double
            );";

    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($sql);
    return $stmt->execute();
}

function editarLancamentoFinanceiro($lancamento)
{
    $sql = "UPDATE controle_financeiro_fornecedores
          SET
            id_fornecedor = {$lancamento['id_fornecedor']}
            ,data = '{$lancamento['data']}'
            ,valor_lancamento = {$lancamento['valor_lancamento']}
            ,valor_total_produtos = {$lancamento['valor_total_produtos']}
            ,saldo = {$lancamento['saldo']}
            ,novo_saldo = {$lancamento['novo_saldo']}
            ,saldo_defeitos = {$lancamento['saldo_defeitos']}
          WHERE id = {$lancamento['id']};";

    $sql .= " UPDATE controle_financeiro_fornecedores set saldo = saldo + {$lancamento['valor_lancamento']}, novo_saldo = novo_saldo + {$lancamento['valor_lancamento']} where id_fornecedor = 3045 and id > {$lancamento['id']};";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($sql);
    return $stmt->execute();
}
