<?php
require_once 'conexao.php';

// function buscaCreditosAcerto($id_acerto){
//   $query = "SELECT lf.*,c.razao_social FROM lancamento_financeiro lf
//   INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
//   WHERE lf.acerto={$id_acerto} AND lf.tipo='P';";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaDefeitosPorAcerto($id_acerto){
//   $query = "SELECT p.descricao referencia, di.tamanho, di.id_faturamento, p.valor_custo_produto, di.sequencia FROM devolucao_item di
//   INNER JOIN produtos p ON (p.id=di.id_produto)
//   WHERE di.acerto={$id_acerto} AND di.defeito=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $lista = $resultado->fetchAll();
//   return $lista;
// }

// function buscaTotalDefeitosAcerto($id_acerto){
//   $query = "SELECT SUM(p.valor_custo_produto) valor FROM devolucao_item di
//   INNER JOIN produtos p ON (p.id=di.id_produto)
//   WHERE di.acerto={$id_acerto} AND di.defeito=1;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   $linha = $resultado->fetch();
//   return $linha['valor'];
// }

function listaLancamentosTesouraria($filtro)
{
    $query = "SELECT t.*, u.nome usuario FROM tesouraria t
  INNER JOIN usuarios u ON (u.id=t.usuario)
  {$filtro} ORDER BY t.data_emissao DESC;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaSaldoAnterior($filtro, $tipo)
{
    $query = "SELECT SUM(t.valor) saldo FROM tesouraria t
  {$filtro} AND t.tipo='{$tipo}';";
    $query = "SELECT SUM(t.valor) saldo FROM tesouraria t
    {$filtro} AND t.tipo='{$tipo}';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['saldo'];
}
