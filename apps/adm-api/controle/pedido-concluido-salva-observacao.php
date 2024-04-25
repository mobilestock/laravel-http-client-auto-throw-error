<?php
require_once '../regras/alertas.php';
require_once '../classes/pedidos.php';

$id_cliente = clienteSessao();

$observacao = $_POST['conteudo'];

insereObservacaoPedido($id_cliente,$observacao);
