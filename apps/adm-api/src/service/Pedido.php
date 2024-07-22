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

        [$idsProdutosSql, $binds] = ConversorArray::criaBindValues(
            [ProdutoModel::ID_PRODUTO_FRETE_PADRAO, ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO],
            'id_produto'
        );
        $binds[':id_cliente'] = Auth::user()->id_colaborador;
        $binds[':situacao'] = PedidoItem::SITUACAO_EM_ABERTO;

        $idsPedidoItem = DB::selectColumns(
            "SELECT pedido_item.id
                FROM pedido_item
                WHERE pedido_item.id_cliente = :id_cliente
                    AND pedido_item.id_produto IN ($idsProdutosSql)
                    AND pedido_item.situacao = :situacao;",
            $binds
        );

        if (empty($idsPedidoItem)) {
            return;
        }

        [$idsPedidoItemSql, $binds] = ConversorArray::criaBindValues($idsPedidoItem);

        $rowCount = DB::delete(
            "DELETE FROM pedido_item
            WHERE pedido_item.id IN ($idsPedidoItemSql);",
            $binds
        );

        if ($rowCount !== count($idsPedidoItem)) {
            throw new InvalidArgumentException('A quantidade de itens deletadas do pedido está inconsistente.');
        }

        DB::commit();
    }
}
