<?php
require_once '../regras/alertas.php';
require_once '../classes/reposicao.php';

$id = $_POST['id'];

alteraPrioridadeReposicao($id);