<?php
require_once 'Database.php';
require_once '../regras/alertas.php';
require_once '../classes/conexao.php';

header('Content-Type: application/json');

$db = new Database();
$uri = $_GET['url'];

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    if($uri!=null){
        $sql = "SELECT p.id, p.descricao FROM produtos p
        WHERE lower(p.descricao) LIKE lower('%{$uri}%')
        ORDER BY p.descricao;";
        if($db->query($sql)==null){
            return null;
            http_response_code(405);
        }else{
            echo json_encode($db->query($sql));
            http_response_code(200);
        }
    }
}