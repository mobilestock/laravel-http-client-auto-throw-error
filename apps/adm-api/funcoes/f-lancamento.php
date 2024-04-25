<?php

function atualizaHistoricoLancamento($id,$usuario,$acao){
    date_default_timezone_set('America/Sao_Paulo');
    $data_atual = Date('Y-m-d H:i:s');
    $seq = buscaUltimoHistoricoLancamento($id);
    $seq++;
    insereHistoricoLancamento($id,$seq,$usuario,$acao,$data_atual);
}