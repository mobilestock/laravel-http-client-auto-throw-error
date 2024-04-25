<?php

$ano=DATE('Y');
$mes=DATE('m');
$id_produto = $_GET['id'];

if(verificaAcesso($ano, $mes, $id_produto)){
    $sql = "UPDATE paginas_acessadas SET acessos = acessos+1 WHERE ano={$ano} AND mes={$mes} AND id_produto={$id_produto}; ";
}else{
    $sql = "INSERT INTO paginas_acessadas (acessos, ano, mes, id_produto) VALUES (1,{$ano},{$mes},{$id_produto}); ";
}

executaSql($sql);