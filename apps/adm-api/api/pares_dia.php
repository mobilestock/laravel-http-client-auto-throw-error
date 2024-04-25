<?php
require_once '../regras/alertas.php';
require_once '../classes/conexao.php';

$uri = $_GET['url'];
header('Content-Type: application/json');
$uri = $_GET['url'];
if ($_SERVER['REQUEST_METHOD'] == 'GET'){
  date_default_timezone_set('America/Sao_Paulo');
  $data = DATE('Y-m-d');
  $query = "SELECT COALESCE(SUM(pares),0) paresSeparadosDia FROM pedidos_separados WHERE id_separador = {$uri} and DATE(data) = '{$data}';";
  $conexao = Conexao::criarConexao();
  $resultado = $conexao->query($query);
  $usuario = $resultado->fetch();
  echo json_encode($usuario);
}
