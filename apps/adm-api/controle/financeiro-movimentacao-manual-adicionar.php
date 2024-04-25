<?php
//require_once '../regras/alertas.php';
//require_once '../classes/lancamento.php';
//require_once '../classes/colaboradores.php';
//require_once '../classes/cheques.php';
//require_once '../classes/tesouraria.php';

//$motivo = $_POST['motivo'];
//$id_lancamento = buscaUltimoLancamento();
//$id_lancamento++;
//$sequencia = 1;
//$tipo = $_POST['tipo'];

//if ($tipo == 'R') {
//  $tipoTesouraria = 'S';
//} else if ($tipo == 'P') {
//  $tipoTesouraria = 'E';
//}

//$documento = 1;
//$situacao = 2;
//$origem = "MA";
//$recebido = buscaColaboradorCaixa();

//date_default_timezone_set('America/Sao_Paulo');
//$data = Date('Y-m-d H:i:s');
//$valor = $_POST['valor'];
//$id_faturamento = 0;
//$usuario = idUsuarioLogado();
//$tabela = 1;

//if (array_key_exists('tesouraria', $_POST)) {
//  $tesouraria = 1;
//} else {
//  $tesouraria = 0;
//}

//if (isset($_POST['responsavel']) && $_POST['responsavel'] != '') {
//  $responsavel = $_POST['responsavel'];
//} else {
//  $responsavel = 0;
//}


//if ($tipo != '' && $valor != '' && $motivo != '') {
//  $num_acerto = buscaUltimoAcerto();
//  $num_acerto++;
//  $origem = 'MA';
//  $num_cheque = buscaUltimoCheque();
//  $block = 0;
//  insereLancamentoManual($id_lancamento, $sequencia, $tipo, 1, $situacao, $origem, $recebido, $data, $data, $valor, $id_faturamento, $usuario, $tabela, $motivo, $num_acerto, $block);

//  insereAcerto($num_acerto, $tipo, $origem, $recebido, $usuario, 0, 0, "");

//  insereAcertoDocumentos($num_acerto, $sequencia, $tipo, $documento, $valor, $data, $usuario, $motivo, 0, 0, $tesouraria, $responsavel, 1);

//  if ($tesouraria == 1) {
//    $idTesouraria = buscaUltimoRegistroTesouraria();
//    $idTesouraria++;
//    insereLancamentoTesouraria($idTesouraria, $tipoTesouraria, $documento, $valor, $data, $usuario, $responsavel, $motivo, $num_acerto);
//  }
//}
header('location:../relatorio-caixa.php');
die();
