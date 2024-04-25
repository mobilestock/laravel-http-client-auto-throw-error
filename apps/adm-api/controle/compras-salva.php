<?php
require_once '../regras/alertas.php';
require_once '../classes/compras.php';

if(isset($_POST['id_compra'])){
    $_SESSION["compraEditar"]=$_POST['id_compra'];

    $compra = array(
    "id" => $_POST['id_compra'],
    "id_fornecedor" => $_POST['id_fornecedor'],
    "data_previsao" => $_POST['data_previsao'],
    );

    $id = $compra['id'];

    //verifica a situação da compra antes de ser alterada
    $situacaoCompra = buscaSituacaoCompra($compra['id']);

    //busca a lista de produtos da compra
    $produtosCompra = buscaCompraProdutos($compra['id']);
    //percorre a lista de produtos da compra
    foreach ($produtosCompra as $produto):

      //se produto estiver bloqueado. ele é desbloqueaddo
      if($produto['bloqueado']==1){
        desbloqueiaProdutoNaCompra($produto);
      }

    endforeach;

    if(alteraCompra($id,$compra)){
        $_SESSION["success"]="Compra alterada com sucesso";
    }
}

header('Location:../compras-lista.php');
die();
