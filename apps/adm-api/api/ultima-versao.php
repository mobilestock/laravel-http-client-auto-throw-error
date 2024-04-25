<?php
require_once '../regras/alertas.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    $versao = "2.0.0";
    echo json_encode($versao);
}