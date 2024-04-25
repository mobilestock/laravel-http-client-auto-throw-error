<?php

use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\Lancamento\LancamentoService;

require_once '../classes/vales.php';
require_once __DIR__ . '/../vendor/autoload.php';

$lancamento = $_POST['lancamento'];
$marcado = $_POST['marcado'];

$lancamento = LancamentoCrud::busca(['id' => $lancamento])[0];
$lancamento->baixar = $marcado;
LancamentoCrud::atualiza($lancamento);