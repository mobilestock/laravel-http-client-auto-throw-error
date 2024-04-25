<?php
 if($erro = isset($_POST['erro'])?strip_tags($_POST['erro']):false){
    $arquivo = fopen("log/log_erros.txt","a+");
    fwrite($arquivo, date('d-m-Y H:i:s')." - ".$erro."\n");
    fclose($arquivo);
 } 
?>