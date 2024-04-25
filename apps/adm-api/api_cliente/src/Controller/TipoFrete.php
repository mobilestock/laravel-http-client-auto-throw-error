<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class TipoFrete
{
    public function listaLocaisEntrega()
    {
        $idColaborador = Auth::user()->id_colaborador;
        $transacao = new TransacaoFinanceiraService();
        $transacao->pagador = $idColaborador;
        $transacao->buscaTransacaoCR(DB::getPdo());
        $produtos = TransacaoFinanceiraItemProdutoService::buscaDadosProdutosTransacao(
            DB::getPdo(),
            $transacao->id ?: 0,
            $idColaborador
        );

        $resultado = TipoFreteService::buscaTipoFrete($produtos);

        return $resultado;
    }
}
