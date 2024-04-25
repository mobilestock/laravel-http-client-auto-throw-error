<?php
require_once "classes/conexao.php";


function buscaColaborador($id)
{
    $query = "SELECT id,cnpj,cpf,telefone,email,cidade,uf FROM colaboradores WHERE tipo='C' and id={$id};";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetch();
}

function buscaUsuarios()
{
    $query = "SELECT id, id_colaborador, email, cnpj, telefone FROM usuarios WHERE id_colaborador IS NOT NULL AND (email is null AND telefone Is NULL AND cnpj IS NULl);";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetchAll();
}


$usuarios = buscaUsuarios();

foreach ($usuarios as $key => $u) {

    if ($c = buscaColaborador($u['id_colaborador'])) {
        $cnpj = "";
        if (strlen($c['cpf']) >= strlen($c['cnpj'])) {
            $cnpj = $c['cpf'];
        } else {
            $cnpj = $c['cnpj'];
        }

        $query = "<br>" . "UPDATE usuarios SET email= NULLIF('{$c['email']}',''),cnpj =NULLIF('{$cnpj}',''),telefone = NULLIF('{$c['telefone']}','') WHERE id_colaborador={$c['id']} AND id={$u['id']};" . "<br>";
    }
}
