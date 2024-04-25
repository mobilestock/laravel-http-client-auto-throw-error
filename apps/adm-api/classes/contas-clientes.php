<?php

require_once 'conexao.php';
require_once 'bancos.php';

function InsereNovaContaCliente($id_cliente, $conta, $agencia, $id_banco, $cpf, $titular, $prioridade)
{
    //$id_banco = IdNomeBanco($nome_banco);
    //$id_banco = $id_banco['id']; 

    $query = "INSERT INTO conta_bancaria_colaboradores(id_cliente,conta,agencia, id_banco, cpf_titular, nome_titular, prioridade) 
                VALUES ({$id_cliente}, '{$conta}', '{$agencia}', {$id_banco},'{$cpf}','{$titular}','{$prioridade}');";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}

//function SelecionaConta($id)
//{
//    $query = "SELECT *,(SELECT bancos.nome FROM bancos WHERE bancos.id = conta_bancaria_colaboradores.id_banco) as nome_banco
//                FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id = {$id};";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetch(PDO::FETCH_ASSOC);
//    return $lista;
//}

//function SelecionarContaClientePrincipal($id_cliente)
//{
//    $query = "SELECT *,(SELECT bancos.nome FROM bancos WHERE bancos.id = conta_bancaria_colaboradores.id_banco) as nome_banco
//                FROM conta_bancaria_colaboradores
//                    WHERE conta_bancaria_colaboradores.id_cliente={$id_cliente} and conta_bancaria_colaboradores.prioridade='P';";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    $lista = $resultado->fetch(PDO::FETCH_ASSOC);
//    return $lista;
//}
function ExisteCadastro($id_cliente)
{
    $query = "SELECT EXISTS(SELECT 1 FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id_cliente = {$id_cliente}) existe";
    $conexao = Conexao::criarConexao();
    $resultado = $conexao->query($query);
    return $resultado->fetch(PDO::FETCH_ASSOC);
}
//function AtualizaCadastro($id_cliente, $banco, $agencia, $conta)
//{
//    $query = "UPDATE conta_bancaria_colaboradores SET conta= '{$conta}',agencia= '{$agencia}', id_banco= {$banco} WHERE id_cliente = {$id_cliente};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
function AtualizaSituacoesSecundarias($id_cliente)
{
    $query = "UPDATE conta_bancaria_colaboradores SET prioridade = 'S' WHERE id_cliente = {$id_cliente};";
    $conexao = Conexao::criarConexao();
    return $conexao->exec($query);
}
//function AtualizaSituacoesPrimaria($id_cliente, $id)
//{
//    $query = "UPDATE conta_bancaria_colaboradores SET prioridade = 'P' WHERE id_cliente = {$id_cliente} AND id = {$id};";
//    $conexao = Conexao::criarConexao();
//    return $conexao->exec($query);
//}
//function ListaContasUsuario($id_cliente)
//{
//    $query = "SELECT *,(SELECT bancos.nome FROM bancos WHERE bancos.id = conta_bancaria_colaboradores.id_banco) as nome_banco
//    FROM conta_bancaria_colaboradores
//        WHERE conta_bancaria_colaboradores.id_cliente={$id_cliente} AND prioridade = 'S' ;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}

//function ListaTodasContasUsuario($id_cliente)
//{
//    $query = "SELECT *,(SELECT bancos.nome FROM bancos WHERE bancos.id = conta_bancaria_colaboradores.id_banco) as nome_banco
//    FROM conta_bancaria_colaboradores
//        WHERE conta_bancaria_colaboradores.id_cliente={$id_cliente} ;";
//    $conexao = Conexao::criarConexao();
//    $resultado = $conexao->query($query);
//    return $resultado->fetchAll();
//}
//function DeleteContaBancariaCliente($id)
//{
//    $query = "DELETE FROM conta_bancaria_colaboradores WHERE conta_bancaria_colaboradores.id={$id};";
//    $conexao = Conexao::criarConexao();
//    $conexao->exec($query);
//}
