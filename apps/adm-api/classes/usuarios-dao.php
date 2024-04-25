<?php
require_once 'conexao.php';

function buscaUsuario($nome,$senha) {
    $senhaMd5 = md5($senha);
    $query = "SELECT id, nome, senha, nivel_acesso, token, (SELECT colaboradores.regime FROM colaboradores WHERE colaboradores.id = usuarios.id_colaborador) regime, id_colaborador FROM usuarios WHERE nome=:nome and senha=:senha and bloqueado=0";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->bindValue(':nome',$nome);
    $stmt->bindValue(':senha',$senhaMd5);
    $stmt->execute();
    return $stmt->fetch();
}

function buscaUsuarioEmail($email,$senha) {
    $senhaMd5 = md5($senha);
    $query = "SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE email=:email and senha=:senha and bloqueado=0";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->bindValue(':email',$email);
    $stmt->bindValue(':senha',$senhaMd5);
    $stmt->execute();
    return $stmt->fetch();
}

// function buscaUsuarioCnpj($cnpj,$senha) {
//     $senhaMd5 = md5($senha);
//     $query = "SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE cnpj=:cnpj and senha=:senha and bloqueado=0";
//     $conexao = Conexao::criarConexao();
//     $stmt = $conexao->prepare($query);
//     $stmt->bindValue(':cnpj',$cnpj);
//     $stmt->bindValue(':senha',$senhaMd5);
//     $stmt->execute();
//     return $stmt->fetch();
// }

function usuarioOnline($id){
    $query = "UPDATE usuarios SET online=1 WHERE id={$id};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}


function usuarioOffline($id){
    $query = "UPDATE usuarios SET online=0 WHERE id={$id};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}
