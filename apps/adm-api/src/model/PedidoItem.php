<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property string $situacao
 * @property string $uuid
 * @property int $id_responsavel_estoque
 * @property ?int $id_transacao
 */
class PedidoItem extends Model
{
    public const SITUACAO_EM_ABERTO = '1';
    public const PRODUTO_RESERVADO = '2';
    protected $table = 'pedido_item';
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    protected $fillable = ['situacao', 'uuid', 'id_responsavel_estoque'];
    public static function verificaProdutosEstaoCarrinho(array $produtos): void
    {
        [$binds, $valores] = ConversorArray::criaBindValues($produtos, 'uuid_produto');
        $valores[':id_cliente'] = Auth::user()->id_colaborador;
        $valores[':situacao_em_aberto'] = self::SITUACAO_EM_ABERTO;
        $qtdProdutosCarrinho = DB::selectOneColumn(
            "SELECT COUNT(pedido_item.uuid) AS `qtd_itens_carrinho`
            FROM pedido_item
            WHERE pedido_item.situacao = :situacao_em_aberto
                AND pedido_item.id_cliente = :id_cliente
                AND pedido_item.uuid IN ($binds);",
            $valores
        );

        if ($qtdProdutosCarrinho !== count($produtos)) {
            throw new NotFoundHttpException(
                'Produtos não encontrados no carrinho, por favor, atualize a página e tente novamente.'
            );
        }
    }
}
