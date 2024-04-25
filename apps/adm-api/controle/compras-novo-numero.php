<?php
require_once '../regras/alertas.php';

//limpa numero da compra em edição
unset($_SESSION["id_compra"]);
header("location: ../compras-cadastrar.php");
die();
