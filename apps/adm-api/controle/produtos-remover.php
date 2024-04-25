<?php
require_once '../classes/produtos.php';
require_once '../regras/alertas.php';

$id = $_POST['id_produto'];

if(removeProduto($id)){
    $_SESSION["success"]= "Produto removido com sucesso.";
}else{
    $_SESSION["danger"]= "Erro ao remover produto.";
}

if(removerProdutoFotos($id)){
    $_SESSION["success"]= "Fotos removidas com sucesso.";
}else{
    $_SESSION["danger"]= "Erro ao remover fotos.";
}

if(removeGradeProduto($id)){
    $_SESSION["success"]= "Grade removida com sucesso.";
}else{
    $_SESSION["danger"]= "Erro ao remover grade.";
}

header('Location: ../produtos-lista.php');
die();
