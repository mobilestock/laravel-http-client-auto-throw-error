<?php

use MobileStock\repository\ColaboradoresRepository;

require_once '../../../vendor/autoload.php';

$retorno  = ColaboradoresRepository::busca([
    "id"=>$_POST['id']
]);
$dados = $retorno->extrair();
if($dados['regime'] == 2){
    echo $dados['cpf'];
} else if($dados['regime'] == 1){
    echo $dados['cnpj'];
} else if($dados['regime'] == 3) {
    throw new Exception("Não foi possível identificar o regime Mobile(3)");
}