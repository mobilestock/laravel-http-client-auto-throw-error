<?php
namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\PedidoItem;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class Pedido
{
    /**
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public static function limparTransacaoEProdutosFreteDoCarrinhoSeNecessario(): void
    {
        $transacao = new TransacaoFinanceiraService();
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());
        PedidoItem::limparProdutosFreteEmAbertoCarrinhoCliente();
    }
}
