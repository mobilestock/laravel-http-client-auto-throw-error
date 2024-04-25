<?php
require_once '../regras/alertas.php';
require_once '../classes/conexao.php';

$uri = $_GET['url'];

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($uri=='logar'){
      $json = file_get_contents('php://input');
      $login = json_decode($json,true);
      $nome = $login['nome'];
      $senha = $login['senha'];
      $id_usuario = buscaUsuario($nome,$senha);
      if($id_usuario>0){
        echo json_encode($id_usuario);
          http_response_code(200);
      }else{
          http_response_code(405);
      }
    }
}else{
  http_response_code(404);
}

function buscaUsuario($nome,$senha) {
    $senhaMd5 = md5($senha);
    $query = "SELECT id, nome, senha, nivel_acesso FROM usuarios WHERE nome=:nome and senha=:senha and bloqueado=0";
    $conexao = Conexao::criarConexao();
    $stmt = $conexao->prepare($query);
    $stmt->bindValue(':nome',$nome);
    $stmt->bindValue(':senha',$senhaMd5);
    $stmt->execute();
    $linha = $stmt->fetch();
    return $linha['id'];
}
