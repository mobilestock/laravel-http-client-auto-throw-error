<?php
/*
require_once 'conexao.php';
require_once 'pares-expirados.php';
require_once 'painel.php';

function buscaDataVencimentoCliente($cliente,$data){
    $dias = 7;
    limpaParesExpirados60Dias($cliente);
    return date('Y-m-d H:i:s',strtotime("+".$dias." days",strtotime($data)));
}

function buscaDataVencimentoPremioCliente($cliente,$data){
    $dias = 60;
    limpaParesExpirados60Dias($cliente);
    return date('Y-m-d H:i:s',strtotime("+".$dias." days",strtotime($data)));
}
*/