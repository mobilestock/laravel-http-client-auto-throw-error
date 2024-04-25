<?php


namespace api_cliente\Controller;


use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraLinksConsultas;

class LinksPagamentoPublico
{
    public function buscaInfoLink(string $idMd5, TransacaoFinanceiraLinksConsultas $consultas)
    {
        $consulta = $consultas->buscaInformacoesLink($idMd5);

        return $consulta;
    }
}