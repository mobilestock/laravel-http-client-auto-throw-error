<?php
/*
require_once 'gradesTamanhos.php';
require_once 'conexao.php';

function listarGrades(){
    $grades = array();
    $query = "SELECT * FROM grades order by nome";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaUltimaGrade(){
    $query="SELECT MAX(id) id from grades";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $id = $resultado->fetch();
    return $id['id'];
}

function buscaGrade($id){
    $query="SELECT * FROM grades where id={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    $grade = array(
    "id" => $linha['id'],
    "nome" => $linha['nome'],
    "min" => $linha['min'],
    "max" => $linha["max"]);
    return $grade;
}

function buscaGradeVazia($id) {
    $grade = array(
    "nome" => "",
    "min" => 13,
    "max" => 50);
    return $grade;
}

// function buscaGradeTamanho($id){
//     $grades = array();
//     $query = "SELECT * FROM produtos_grade where id={$id} ORDER BY tamanho";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     foreach ($lista as $linha):
//         $grade = new gradesTamanhos();
//         $grade->tamanho = $linha['tamanho'];
//         $grade->quantidade = 0;
//         array_push($grades, $grade);
//     endforeach;
//     return $grades;
// }

// function buscaGradeTamanhoCompra($id){
//     $query = "SELECT * FROM produtos_grade where id={$id} ORDER BY tamanho";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll();
// }

// function getProdutoGrade($id){

//     $query = "SELECT *,
//     (SELECT p.tipo_grade FROM produtos p WHERE p.id = estoque_grade.id_produto) tipo_grade 
//     FROM estoque_grade 
//     where id_produto= {$id} and COALESCE(id_responsavel, 1) = 1
//     ORDER BY tamanho;";

//     //$query = " SELECT * FROM estoque_grade where id_produto= {$id} ORDER BY tamanho;";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     return $resultado->fetchAll();
// }

function existeGradeCadastrada($id){
    $query = "SELECT * from grades where id={$id}";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return sizeof($lista);
}

// function existeGradePadraoCadastrada($id){
//     $query = "SELECT * from grade_padrao where id_grade={$id}";
//     $conexao = Conexao::criarConexao();
//     $resultado = $conexao->query($query);
//     $lista = $resultado->fetchAll();
//     return sizeof($lista);
// }

function cadastraGrade($id, $nome, $min, $max){
    $query = "INSERT INTO grades (id,nome,min,max) VALUES ({$id},'{$nome}',{$min},{$max})";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

// function cadastraGradePadrao($id, $min, $max) {
//     $query = "";
//     for($i=$min;$i<=$max;$i++){
//         $query.="INSERT INTO grade_padrao (id_grade,tamanho,quantidade) VALUES ({$id},{$i},0);";
//     }
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->prepare($query);
//     return $stmt->execute();
// }

function atualizaGrade($id, $nome, $min, $max) {
    $query = "UPDATE grades set nome='{$nome}', min={$min}, max={$max} WHERE id={$id}";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    return $stmt->execute();
}

// function insereGradePadrao($id_grade,$quantidades) {
//     $query = "";
//     foreach ($quantidades as $grade):
//       $query.="UPDATE grade_padrao set quantidade={$grade->quantidade}
//       WHERE tamanho={$grade->tamanho} and id_grade=$id_grade;";
//     endforeach;
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->prepare($query);
//     return $stmt->execute();
// }

function excluirGrade($id){
  $query = "DELETE FROM grades WHERE id={$id}";
  $conexao = Conexao::criarConexao();
  $stmt = $conexao->prepare($query);
  return $stmt->execute();
}

// function excluirGradePadrao($id){
//   $query = "DELETE FROM grade_padrao WHERE id_grade={$id}";
//   $conexao = Conexao::criarConexao();
//   $stmt = $conexao->prepare($query);
//   return $stmt->execute();
// }
*/
