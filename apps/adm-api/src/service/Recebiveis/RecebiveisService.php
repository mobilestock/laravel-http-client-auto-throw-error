<?php
// namespace MobileStock\service\Recebiveis;

// use PDO;

// class RecebiveisService
// {
    // public static function buscaRecebiveisFuturos(PDO $conexao, int $idColaborador)
    // {
    //     $query = "SELECT lfr.id_lancamento, lfr.valor, DATE_FORMAT(lfr.data_vencimento,'%d/%m/%Y') data_vencimento, 
    //     lfr.num_parcela, (SELECT COUNT(lfr2.id) FROM lancamentos_financeiros_recebiveis lfr2 
    //     WHERE lfr2.id_lancamento = lfr.id_lancamento AND lfr2.id_recebedor = :idFornecedor) parcelas
    //     FROM lancamentos_financeiros_recebiveis lfr 
    //     INNER JOIN lancamento_financeiro lf ON lf.id = lfr.id_lancamento
    //     WHERE lfr.id_recebedor = :idFornecedor AND lfr.situacao = 'PE'
    //     ORDER BY lfr.data_vencimento ASC LIMIT 1000;";
    //     $stm = $conexao->prepare($query);
    //     $stm->bindParam(':idFornecedor', $idColaborador, PDO::PARAM_INT);   
    //     $stm->execute();
    //     return $stm->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public static function buscaRecebiveisPagos(PDO $conexao, int $idColaborador)
    // {
    //     $query = "SELECT lfr.valor_pago valor, lfr.id_lancamento, DATE_FORMAT(lfr.data_vencimento,'%d/%m/%Y') data_vencimento, 
    //     lfr.num_parcela, (SELECT COUNT(lfr2.id) FROM lancamentos_financeiros_recebiveis lfr2 
    //     WHERE lfr2.id_lancamento = lfr.id_lancamento AND lfr2.id_recebedor = :idFornecedor) parcelas
    //     FROM lancamentos_financeiros_recebiveis lfr 
    //     INNER JOIN lancamento_financeiro lf ON lf.id = lfr.id_lancamento
    //     WHERE lfr.id_recebedor = :idFornecedor AND lfr.situacao = 'PA'
    //     ORDER BY lfr.data_vencimento DESC LIMIT 1000;";
    //     $stm = $conexao->prepare($query);
    //     $stm->bindParam(':idFornecedor', $idColaborador, PDO::PARAM_INT);   
    //     $stm->execute();
    //     return $stm->fetchAll(PDO::FETCH_ASSOC);
    // }

    // public static function buscaLancamentosFinanceirosRecebiveis(PDO $conexao, int $lancamento, int $idColaborador)
    // {
    //     $query = "SELECT lfr.valor, COALESCE(DATE_FORMAT(lfr.data_vencimento,'%d/%m/%Y'),' ') data_pagamento, lfr.situacao, lfr.num_parcela,
    //     (SELECT COUNT(lfr2.id_lancamento) FROM lancamentos_financeiros_recebiveis lfr2 WHERE lfr2.id_lancamento = {$lancamento} 
    //     AND lfr2.id_recebedor={$idColaborador}) qParcelas FROM lancamentos_financeiros_recebiveis lfr
    //     INNER JOIN lancamento_financeiro lf ON lf.id = lfr.id_lancamento
    //     WHERE lfr.id_lancamento = {$lancamento} ORDER BY lfr.num_parcela ASC;";
    //     $stm = $conexao->prepare($query);
    //     $stm->execute();
    //     return $stm->fetchAll(PDO::FETCH_ASSOC);
    // }
// }