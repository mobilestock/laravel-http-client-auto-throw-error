<?php
require_once 'Database.php';
require_once '../regras/alertas.php';
require_once '../classes/conexao.php';

header('Content-Type: application/json');

$db = new Database();
$uri = $_GET['url'];
if ($_SERVER['REQUEST_METHOD'] == 'GET'){
  $query = "SELECT estante FROM pedido_estante;";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $lista =  $resultado->fetchAll();

  $estante = 1;
  $estanteLivre = 1;
  foreach ($lista as $key => $linha) {
    if($linha['estante']!=$estanteLivre){
      $estante = $estanteLivre;
      break;
    }else{
      $estanteLivre++;
      $estante = $estanteLivre;
    }
  }

  echo json_encode($estante);

  $query = "UPDATE pedido_estante SET cheio=1 WHERE id_cliente={$uri};";
  $conexao = Conexao::criarConexao();
  $conexao->exec($query);

  $query = "INSERT INTO pedido_estante SET estante={$estante},id_cliente={$uri};";
  $conexao = Conexao::criarConexao();
  return $conexao->exec($query);

}
