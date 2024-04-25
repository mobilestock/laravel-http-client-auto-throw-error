<?php
require_once 'conexao.php';

function listaTabela($tabela)
{
  if ($tabela == "categorias") {
    $query = "SELECT * FROM {$tabela} ORDER BY nome;";
  } else {
    $query = "SELECT * FROM {$tabela} ORDER BY id;";
  }
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado;
}

// function listaCondicaoPagamento()
// {
//   $query = "SELECT * FROM condicao_pagamento ORDER BY nome;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado;
// }

// function listaEstados()
// {
//   $query = "SELECT * FROM estados ORDER BY uf;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado;
// }

// function buscaLocalizacao($tipo)
// {
//   $query = "SELECT * FROM localizacao_estoque WHERE tipo='{$tipo}' ORDER BY local;";
//   $conexao = Conexao::criarConexao();
//   $resultado = $conexao->query($query);
//   return $resultado;
// }
