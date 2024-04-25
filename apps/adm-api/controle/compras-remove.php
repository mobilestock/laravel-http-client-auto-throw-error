<?php
require_once '../classes/transacao.php';
require_once '../classes/compras.php';
require_once '../regras/alertas.php';

$id = $_POST['id_compra'];
$situacaoCompra = buscaSituacaoCompra($id);

if($situacaoCompra!=2){
  if(remover("compras",$_POST['id_compra'],"id")){
      $_SESSION["success"]="Compra removida com sucesso.";
  }else{
      $_SESSION["danger"]="Erro ao remover compra.";
  }

  if(remover("compras_itens",$_POST['id_compra'],"id_compra")){
      $_SESSION["success"]="Compra removida com sucesso.";
  }else{
      $_SESSION["danger"]="Erro ao remover compra.";
  }

  if(remover("compras_itens_grade",$_POST['id_compra'],"id_compra")){
      $_SESSION["success"]="Compra removida com sucesso.";
  }else{
      $_SESSION["danger"]="Erro ao remover compra.";
  }

  if(remover("compras_itens_caixas",$_POST['id_compra'],"id_compra")){
      $_SESSION["success"]="Compra removida com sucesso.";
  }else{
      $_SESSION["danger"]="Erro ao remover compra";
  }
}else{
  $_SESSION["danger"]="Não é possível remover a compra com situação entregue.";
}

header("Location:../compras-lista.php");
die();
