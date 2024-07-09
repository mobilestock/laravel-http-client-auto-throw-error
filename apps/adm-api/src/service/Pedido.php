<?php
namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\PedidoItem;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class Pedido
{
    public static function limparTransacaoEProdutosFreteDoCarrinhoSeNecessario(): void
    {
        DB::beginTransaction();
        $transacao = new TransacaoFinanceiraService();
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());
        PedidoItem::limparProdutosFreteEmAbertoCarrinhoCliente();
        DB::commit();
    }
}
