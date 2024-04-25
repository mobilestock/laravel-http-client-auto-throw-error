<?php
/*
function verificaLimparLocalizacao($id_produto){
    $estoque = buscaTotalEstoqueProduto($id_produto);
    if($estoque<=0){
        $sql = "UPDATE produtos SET localizacao=null WHERE id={$id_produto};";
        $conexao = Conexao::criarConexao();
        $conexao->exec($sql);
    }
}
*/