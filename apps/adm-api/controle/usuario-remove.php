<?php
require_once '../classes/conexao.php';
require_once '../classes/usuarios.php';

$id = $_POST['id'];

if(removeUsuario($id)){
  $_SESSION["success"]="Usuário inserido com sucesso.";
}else{
  $_SESSION["danger"]="Erro ao inserir usuário.";
}

header('location:../usuarios-lista.php');
die();
