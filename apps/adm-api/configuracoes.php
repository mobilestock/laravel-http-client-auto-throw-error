<?php
require_once 'classes/conexao.php';

function atualizaConfiguracoes(int $metas_do_mobile, int $fornecedor_fisico, int $fornecedor_juridico, string $zoop)
{
  $query = "UPDATE configuracoes 
  SET fornecedor_mobile_fisico = {$fornecedor_fisico}, 
  metas_do_mobile = {$metas_do_mobile},
  fornecedor_mobile_juridico = {$fornecedor_juridico},
  id_zoop_mobile = '{$zoop}';";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaConfiguracoes()
{
  $query = "SELECT * FROM configuracoes";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

// function removeSeparadoresDosPedidos()
// {
//   $query = "UPDATE pedido set separador_temp = 0 WHERE separador_temp > 0;";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function atualizaHorasBackup($horaAtual)
{
  $query = "UPDATE configuracoes set horas_backup = {$horaAtual};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function verificaParesExpirados()
{
  date_default_timezone_set('America/Sao_Paulo');
  $data_atual = DATE('Y-m-d');
  $query = "SELECT verificacao_expirar_pares FROM configuracoes
  WHERE verificacao_expirar_pares = '{$data_atual}';";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}
