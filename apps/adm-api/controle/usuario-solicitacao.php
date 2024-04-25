<?php
require_once '../classes/conexao.php';
require_once '../classes/usuarios.php';
require_once '../regras/alertas.php';

$id = buscaUltimoUsuario();
$id++;

$nome = $_POST['nome'];
$senha = $_POST['senha'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];

if(insereUsuarioSolicitacao($id,$nome,$senha,$email,$telefone)){
  $_SESSION['success'] = "Agora falta pouco para você utilizar o MOBILE STOCK.
    Aguarde um contato através do e-mail ou telefone, aprovando a solicitação. =)";
}else{
  $_SESSION["danger"]="Erro ao inserir usuário.";
}

header('location:../index.php');
die();
