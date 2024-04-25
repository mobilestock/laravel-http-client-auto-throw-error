<?php
require_once '../configuracoes.php';
require_once '../regras/alertas.php';

$metas_do_mobile = array_key_exists('metas_do_mobile', $_POST) ? 1 : 0;
$fornecedor_fisico = array_key_exists('fornecedor_fisico', $_POST) ? intVal($_POST['fornecedor_fisico']) : 0;
$fornecedor_juridico = array_key_exists('fornecedor_juridico', $_POST) ? intVal($_POST['fornecedor_juridico']) : 0;
$id_zoop = array_key_exists('id_zoop_mobile', $_POST) ? $_POST['id_zoop_mobile'] : "";

if (atualizaConfiguracoes($metas_do_mobile, $fornecedor_fisico, $fornecedor_juridico, $id_zoop)) {
    $_SESSION['success'] = "Configurações efetuadas com sucesso.";
}

header("location:../configuracoes-sistema.php");
die();
