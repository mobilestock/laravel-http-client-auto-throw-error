<?php
require_once 'conexao.php';

function listaTarefas(){
  $query = "SELECT * FROM tarefas WHERE resolvido = 0
  ORDER BY prioridade, sistema, modulo, data DESC";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetchAll();
}

function insereTarefa($data,$data_solucao,$id_sistema,$id_modulo,$id_prioridade,$id_usuario,$resolvido,$descricao,$solucao){
  $query = "INSERT INTO tarefas (data,data_solucao,sistema,modulo,prioridade,usuario,resolvido,descricao,solucao) VALUES
  ('{$data}','{$data_solucao}',{$id_sistema},{$id_modulo},{$id_prioridade},{$id_usuario},{$resolvido},'{$descricao}','{$solucao}');";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function removeTarefa($id){
  $query = "DELETE FROM tarefas WHERE id={$id}";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}

function buscaTarefa($id){
  $query = "SELECT * FROM tarefas WHERE id={$id}";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  return $resultado->fetch();
}

function atualizaTarefa($id,$data_solucao,$id_sistema,$id_modulo,$id_prioridade,$resolvido,$descricao,$solucao){
  $query = "UPDATE tarefas set data_solucao='{$data_solucao}',sistema={$id_sistema}, modulo={$id_modulo},
  prioridade={$id_prioridade},resolvido={$resolvido},descricao='{$descricao}',solucao='{$solucao}' WHERE id={$id};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);
}
