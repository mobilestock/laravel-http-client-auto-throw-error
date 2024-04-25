<?php

require_once __DIR__ . '/../../vendor/autoload.php';


use Dompdf\Dompdf;

$dompdf = new Dompdf(array('enable_remote' => true));
$dompdf->loadHtml($_POST['data']);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('lista_faturamento.pdf', array('Attachment' => 0));
$output = $dompdf->output();
return file_put_contents("lista_faturamento.pdf", $output);
