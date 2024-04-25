<?php
require_once '../classes/conexao.php';
$conexao = Conexao::criarConexao();

if($_POST['data']==="Ativa"){
    $query="UPDATE configuracoes SET limite_de_compra = 1";
    $conexao->exec($query);
}else if($_POST['data']==="Desativa"){
    $query="UPDATE configuracoes SET limite_de_compra = 0";
    $conexao->exec($query);
}