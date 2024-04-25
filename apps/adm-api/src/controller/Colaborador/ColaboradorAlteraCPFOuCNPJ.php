<?php

use MobileStock\database\Conexao;
use MobileStock\repository\ColaboradoresRepository;

require_once '../../../vendor/autoload.php';

$retorno  = ColaboradoresRepository::busca([
    "id"=>$_POST['id']
]);
$dados = $retorno->extrair();
if($dados['regime'] == 2){
    if(filter_var($_POST['valor'],FILTER_VALIDATE_INT)){
        $Conexao = new Conexao;
        $DB = $Conexao->criarConexao();
        $query = "UPDATE colaboradores set cpf = '{$_POST['valor']}' WHERE id='{$dados['id']}'";
        if($DB->exec($query)){
            echo "Concluido";
        }else{
            echo "ERRO";
        }
    }
} else if($dados['regime'] == 1){
    echo $dados['cnpj'];
} else if($dados['regime'] == 3) {
    throw new Exception("Não foi possível identificar o regime Mobile(3)");
}