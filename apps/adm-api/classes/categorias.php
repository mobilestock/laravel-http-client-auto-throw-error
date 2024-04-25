<?php
require_once 'conexao.php';

function buscaCategorias(){
    $query = "SELECT * FROM categorias ORDER BY nome";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}

function buscaLinhas(){
    $query = "SELECT * FROM linha ORDER BY nome";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $lista = $resultado->fetchAll();
    return $lista;
}