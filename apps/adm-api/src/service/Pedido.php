<?php
namespace MobileStock\service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\helper\ConversorArray;
use MobileStock\model\PedidoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class Pedido
{
    public static function limparTransacaoEProdutosFreteDoCarrinhoSeNecessario(): void
    {
        DB::beginTransaction();
        $transacao = new TransacaoFinanceiraService();
        $transacao->pagador = Auth::user()->id_colaborador;
        $transacao->removeTransacoesEmAberto(DB::getPdo());

        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues(ProdutoModel::IDS_PRODUTOS_FRETE, 'id_produto');
        $binds[':id_cliente'] = Auth::user()->id_colaborador;
        $binds[':situacao'] = PedidoItem::SITUACAO_EM_ABERTO;

        $idsProdutosFrete = DB::selectColumns(
            "SELECT pedido_item.id
                FROM pedido_item
                WHERE pedido_item.id_cliente = :id_cliente
                    AND pedido_item.id_produto IN ($produtosFreteSql)
                    AND pedido_item.situacao = :situacao;",
            $binds
        );

        if (empty($idsProdutosFrete)) {
            return;
        }

        [$produtosFreteSql, $binds] = ConversorArray::criaBindValues($idsProdutosFrete);

        $rowCount = DB::delete(
            "DELETE FROM pedido_item
            WHERE pedido_item.id IN ($produtosFreteSql);",
            $binds
        );

        if ($rowCount !== count($idsProdutosFrete)) {
            throw new InvalidArgumentException('A quantidade de itens deletadas do pedido está inconsistente.');
        }

        DB::commit();
    }
}
