<?php
require_once '../classes/conexao.php';
require_once '../classes/usuarios.php';

$id = buscaUltimoUsuario();
$id++;

$nome = $_POST['nome'];
$senha = $_POST['senha'];
$acesso = $_POST['acesso'];
$colaborador = $_POST['id_colaborador'];
$tipos = $_POST['tipos'];

if(array_key_exists('bloqueado', $_POST)){
    $bloqueado = 1;
}else{
    $bloqueado = 0;
}
if($colaborador>0){

if(insereUsuario($id,$nome,$senha,$acesso,$bloqueado,$colaborador, $tipos)){
  $_SESSION["success"]="Usuário inserido com sucesso.";
}else{
  $_SESSION["danger"]="Erro ao inserir usuário.";
}}

header('location:../usuarios-lista.php');
die();
