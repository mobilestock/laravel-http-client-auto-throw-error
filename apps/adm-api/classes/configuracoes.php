<?php
require_once 'conexao.php';

function atualizaConfiguracoes(int $nota_saida){
  $query="UPDATE configuracoes SET emissao_nota_fiscal = {$nota_saida};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaConfiguracoes(){
  $query = "SELECT * FROM configuracoes";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $linha = $resultado->fetch();
  return $linha;
}

// function removeSeparadoresDosPedidos(){
//   $query = "UPDATE pedido set separador_temp = 0 WHERE separador_temp > 0;";
//   $conexao = Conexao::criarConexao();
//   return $conexao->exec($query);
// }

function atualizaHorasBackup($horaAtual){
  $query = "UPDATE configuracoes set horas_backup = {$horaAtual};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaMesCorrespondente($mes){
  switch ($mes) {
      case '01':
          echo 'Janeiro';
          break;
      
      case '02':
          echo 'Fevereiro';
          break;

      case '03':
          echo 'Março';
          break;
      
      case '04':
          echo 'Abril';
          break;

      case '05':
          echo 'Maio';
          break;

      case '06':
          echo 'Junho';
          break;

      case '07':
          echo 'Julho';
          break;

      case '08':
          echo 'Agosto';
          break;

      case '09':
          echo 'Setembro';
          break;

      case '10':
          echo 'Outubro';
          break;

      case '11':
          echo 'Novembro';
          break;

      case '11':
          echo 'Dezembro';
          break;
  }
}
