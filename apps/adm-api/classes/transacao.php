<?php
require_once 'conexao.php';

// function buscaUltimoRegistro($registro,$tabela){
//     $query = "SELECT MAX({$registro}) reg FROM {$tabela}";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $linha = $resultado->fetch();
//     return $linha['reg'];
// }

function remover($tabela,$id,$filtro){
      $query = "DELETE FROM {$tabela} WHERE {$filtro}={$id}";
      $conexao = Conexao::criarConexao();
      $stmt = $conexao->prepare($query);
      return $stmt->execute();
}

function listar($tabela){
      $query = "SELECT * FROM {$tabela}";
      $conexao = Conexao::criarConexao();
      $stmt = $conexao->prepare($query);
      return $stmt->execute();
}

function removerSequencia($tabela,$filtro){
      $query = "DELETE FROM {$tabela} WHERE {$filtro}";
      $conexao = Conexao::criarConexao();
      $stmt = $conexao->prepare($query);
      return $stmt->execute();
}

function executaSql($sql){
      $conexao = Conexao::criarConexao();
      $stmt = $conexao->prepare($sql);
      return $stmt->execute();
}
function executaProcedure($sql){
      $conexao = Conexao::criarConexao();  
      $conexao->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); 
      try{    
           $conexao->exec($sql); 
           return 1;
      }catch(PDOException $e){
            return $e->getMessage(); 
      }
}

// function buscaProdutoTrocaDefeito($uuid){
//       $query = "SELECT * FROM defeitos WHERE uuid='{$uuid}';";
//       $conexao = Conexao::criarConexao();
//       $resultado = $conexao->query($query);
//       $lista = $resultado->fetchAll();
//       return $lista;
//     }