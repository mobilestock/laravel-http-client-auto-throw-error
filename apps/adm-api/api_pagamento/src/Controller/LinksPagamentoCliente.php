<?php

namespace api_pagamento\Controller;

use api_pagamento\Models\Request_m;
use Illuminate\Http\Request;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PDO;

class LinksPagamentoCliente extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function listaLinks(Request $request, PDO $conexao)
    {
        $pesquisa = $request->query->get('pesquisa', '');
        $pagina = $request->query->getInt('pagina', 1);

        $listaTransacoesApi = TransacaoFinanceiraService::listaTransacoesApi(
            $conexao,
            $this->idCliente,
            $pesquisa,
            $pagina,
            'GERAL'
        );

        return $listaTransacoesApi;
    }
}