<?php
require_once __DIR__.'/../regras/alertas.php';
require_once '../classes/colaboradores.php';
require_once '../classes/usuarios-dao.php';

//verificaUsuario();

$id = idUsuarioLogado();

usuarioOffline($id);

logout();
header("Location: ../index.php");
die();
