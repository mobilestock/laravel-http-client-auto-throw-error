<?php 
require_once "conexao.php";

function IdNomeBanco($nome_banco){
    $query = "SELECT bancos.id FROM bancos WHERE bancos.nome = '{$nome_banco}';";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $id = $resultado->fetch();
    return $id;
}
function ListaTodosBancos(){
    $query = "SELECT * FROM bancos;";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}
?>