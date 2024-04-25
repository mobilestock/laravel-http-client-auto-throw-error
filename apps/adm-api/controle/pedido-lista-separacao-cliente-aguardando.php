<?php
require_once '../regras/alertas.php';
require_once '../classes/separacao.php';
require_once '../classes/estoque.php';

$id = $_POST['id_sep'];

adicionaPrioridadeDeClienteAguardando($id);