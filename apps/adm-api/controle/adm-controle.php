<?php
function statusLimiteSeparacao(){
    $query="SELECT limite_de_compra FROM configuracoes";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $dado = $resultado->fetch();
    return $dado[0];
}