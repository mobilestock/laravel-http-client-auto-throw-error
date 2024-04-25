<?php

use MobileStock\model\AnaliseDeDefeito;

require_once '../../vendor/autoload.php';
 $teste = new AnaliseDeDefeito(intval($_POST['idCliente']));
echo $teste->retornaBuscaItens($_POST['busca']);