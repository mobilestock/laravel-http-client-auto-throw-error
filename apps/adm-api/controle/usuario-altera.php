<?php
require_once '../classes/conexao.php';
require_once '../classes/usuarios.php';

$id = $_POST['id'];
$nome = $_POST['nome'];
$acesso = $_POST['acesso'];
$colaborador = $_POST['id_colaborador'];
$tipos = $_POST['tipos'];

if (array_key_exists('bloqueado', $_POST)) {
  $bloqueado = 1;
} else {
  $bloqueado = 0;
}

if ($_POST['senha'] != "") {
  $senha = $_POST['senha'];
    if (alteraUsuario($id, $nome, $senha, $acesso, $bloqueado, $colaborador, $tipos)) {
    $_SESSION["success"] = "Usuário inserido com sucesso.";
  } else {
    $_SESSION["danger"] = "Erro ao inserir usuário.";
  }
} else {
  if (alteraUsuarioSemSenha($id, $nome, $acesso, $bloqueado, $colaborador, $tipos)) {
    $_SESSION["success"] = "Usuário inserido com sucesso.";
  } else {
    $_SESSION["danger"] = "Erro ao inserir usuário.";
  }
}

header('location:../usuarios-lista.php');
die();
