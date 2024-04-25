<?php
require_once 'conexao.php';

function insereLancamentoTesouraria($idTesouraria,$tipoTesouraria,$documento,$valor,$data,$usuario,$responsavel,$motivo,$acerto){
    $query = "INSERT INTO tesouraria (id,tipo,documento,valor,data_emissao,usuario,responsavel,motivo,acerto)
    VALUES ({$idTesouraria},'{$tipoTesouraria}',{$documento},{$valor},'{$data}',{$usuario},{$responsavel},'{$motivo}',{$acerto});";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

function buscaUltimoRegistroTesouraria(){
    $query = "SELECT COALESCE(MAX(id),0)id FROM tesouraria";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    $linha = $resultado->fetch();
    return $linha['id'];
}

?>

