<?php require_once 'conexao.php';

function buscaListaRepresentantes($filtro){
  $query = "SELECT SUM(lf.valor) valor, SUM(lf.pares) pares, r.nome representante, lf.id_representante from lancamento_financeiro lf
  INNER JOIN representantes r ON (r.id=lf.id_representante)
  {$filtro} AND lf.documento = 2 AND lf.situacao=1
  GROUP BY lf.id_representante ORDER BY r.nome;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}

function buscaRepresentante($id){
  $query = "SELECT r.*,SUM(lf.valor)valor, SUM(lf.pares)pares, SUM(lf.comissao_vale)comissao
  FROM representantes r
  INNER JOIN lancamento_financeiro lf ON (lf.id_representante = r.id)
  WHERE r.id={$id} AND lf.situacao=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

function buscaValesRepresentante($id){
  $query = "SELECT lf.id, lf.valor, lf.pares, lf.numero_documento, c.razao_social cliente, lf.data_emissao, lf.data_vencimento, lf.baixar, lf.comissao_vale
  from lancamento_financeiro lf
  INNER JOIN colaboradores c ON (c.id=lf.id_colaborador)
  WHERE lf.documento = 2 AND lf.id_representante={$id} AND lf.situacao=1;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista = $resultado->fetchAll();
  return $lista;
}