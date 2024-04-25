<?php

use MobileStock\helper\Acesso;
use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

extract($_REQUEST);

$controle = new Acesso(ProdutosRepository::class);
$controle->get('index');
// $controle->delete('delete');
