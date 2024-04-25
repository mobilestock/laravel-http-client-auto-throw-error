<?php
require_once '../classes/transacao.php';
require_once '../regras/alertas.php';

$id_compra = $_GET["id"];
$sequencia = $_GET["sequencia"];

if(removerSequencia("compras_itens","id_compra={$id_compra} and sequencia={$sequencia}")){
    $_SESSION["success"]="Produto removido com sucesso.";
}else{
    $_SESSION["danger"]="Erro ao remover produto.";
}

if(removerSequencia("compras_itens_grade","id_compra={$id_compra} and id_sequencia={$sequencia}")){
    $_SESSION["success"]="Produto grade removido com sucesso.";
  }else{
    $_SESSION["danger"]="Erro ao remover produto grade.";
}

if(removerSequencia("compras_itens_caixas","id_compra={$id_compra} and id_sequencia={$sequencia}")){
    $_SESSION["success"]="Produto codigo removido com sucesso.";
  }else{
    $_SESSION["danger"]="Erro ao remover produto codigo.";
}

header("location:../compras-cadastrar.php");
die();
