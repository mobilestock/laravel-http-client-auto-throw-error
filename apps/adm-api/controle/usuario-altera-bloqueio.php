<?php
require_once '../classes/conexao.php';
require_once '../classes/usuarios.php';
require_once '../regras/alertas.php';

$id = $_POST['id'];
$bloqueado = $_POST['bloqueado'];

if(alterarBloqueio($id,$bloqueado)){
  $_SESSION["success"]="Alteração de bloqueio feita com sucesso.";
}else{
  $_SESSION["danger"]="Erro ao alterar bloqueio de usuário.";
}

header('location:../usuarios-lista.php');
die();
