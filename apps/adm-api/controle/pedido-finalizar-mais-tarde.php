<?php
require_once '../regras/alertas.php';
require_once '../classes/pedidos.php';
$cliente = clienteSessao();
finalizarPedidoMaisTarde($cliente,1);
header("location:../pedido-painel.php");
die();
