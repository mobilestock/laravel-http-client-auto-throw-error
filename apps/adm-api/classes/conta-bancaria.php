<?php
require_once 'conexao.php';

function listaContasBancarias(){
    $query = "SELECT * FROM contas_bancarias";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}
function cadastraContaBancaria($nomeConta){
    $query = "INSERT INTO contas_bancarias (nome) VALUES('$nomeConta')";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado;
}