<?php 
require_once '../classes/bancos.php';
require_once '../classes/contas-clientes.php';

/* Controler adiciona nova conta do cliente e/ou coloca como Primaria se for a primeira inserção ou se ele cadastrou */

$conta = $_POST['conta'];
$agencia = $_POST['agencia'];
$banco = $_POST['banco'];
$id_cliente = $_POST['id_cliente'];
$cpf = $_POST['cpf']; 
$nome= $_POST['nome_titular'];

$existe = ExisteCadastro($id_cliente);
if($existe['existe']){
    AtualizaSituacoesSecundarias($id_cliente);
    InsereNovaContaCliente($id_cliente, $conta,$agencia,$banco,$cpf,$nome,'P');
}else{
    InsereNovaContaCliente($id_cliente, $conta,$agencia,$banco,$cpf,$nome,'P');
}
?>