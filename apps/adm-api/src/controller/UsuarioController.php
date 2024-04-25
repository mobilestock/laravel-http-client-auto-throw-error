<?php

use MobileStock\database\Conexao;
use MobileStock\service\UsuarioService;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../regras/alertas.php';

extract($_POST);

if(array_key_exists('bloqueado', $_POST)){
    $bloqueado = 1;
}else{
    $bloqueado = 0;
}

$usuario = [
    "nome"=>$nome,
    "senha"=>$senha,
    "acesso"=>intVal($acesso),
    "bloqueado"=>intVal($bloqueado),
    "id_colaborador"=>intVal($id_colaborador),
    "email"=>$email,
    "cnpj"=>$cnpj,
    "telefone"=>$telefone
];

$conexao = Conexao::criarConexao();
$conexao->beginTransaction();
try {
    $usuarioService = new UsuarioService();
    if ($id>0) {
        $usuarioService->alteraUsuario($conexao,$usuario,$id);
    }else{
        $id = $usuarioService->insereUsuario($conexao,$usuario);
    }
    $conexao->commit(); 
    $_SESSION["success"]="Usuário salvo com sucesso.";
    header('location:../../usuario-cadastrar.php?id='.$id);
    die();
} catch(Throwable | PDOException $e){
    $conexao->rollBack();
    $_SESSION['danger']=$e->getMessage();
    header('location:../../usuarios-lista.php');
    die();
}
