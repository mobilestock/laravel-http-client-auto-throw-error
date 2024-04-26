<?php

namespace MobileStock\model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 *  @property int $id
 *  @property int $id_usuario
 *  @property int $id_entrega
 *  @property int $id_transacao
 *  @property int $id_cliente
 *  @property string $situacao
 *  @property string $origem
 *  @property string $uuid_produto
 *  @property int $id_produto
 *  @property string $nome_tamanho
 *  @property ?string $nome_recebedor
 *  @property string $data_criacao
 *  @property string $data_atualizacao
 *  @property ?string $data_entrega
 *  @property ?string $data_base_troca
 *  @property int $id_responsavel_estoque
 */
class EntregasFaturamentoItem extends Model
{
    protected $table = 'entregas_faturamento_item';
    protected $primaryKey = 'uuid_produto';
    protected $keyType = 'string';
    protected $fillable = ['id_usuario', 'situacao', 'nome_recebedor', 'data_base_troca'];
    public static function confirmaConferencia(array $produtos): void
    {
        foreach ($produtos as $uuidProduto) {
            $entregaItem = new self();
            $entregaItem->exists = true;

            $entregaItem->situacao = 'AR';
            $entregaItem->uuid_produto = $uuidProduto;

            $entregaItem->update();
        }
    }
    public function confirmaEntregaDeProdutos(array $produtos, string $nomeRecebedor = null): void
    {
        foreach ($produtos as $uuidProduto) {
            $entregaItem = new self();
            $entregaItem->exists = true;

            $entregaItem->situacao = 'EN';
            $entregaItem->nome_recebedor = $nomeRecebedor;
            $entregaItem->uuid_produto = $uuidProduto;

            $entregaItem->update();
        }
    }
    public static function buscaTransacoesDaEntrega(int $idEntrega): array
    {
        $transacoes = DB::selectColumns(
            "SELECT entregas_faturamento_item.id_transacao
            FROM entregas_faturamento_item
            WHERE entregas_faturamento_item.id_entrega = :id_entrega
            GROUP BY entregas_faturamento_item.id_transacao;",
            ['id_entrega' => $idEntrega]
        );

        return $transacoes;
    }
    public static function clientePossuiCompraEntregue(): bool
    {
        $consulta = DB::selectOneColumn(
            "SELECT EXISTS(
                SELECT 1
                FROM entregas_faturamento_item
                WHERE entregas_faturamento_item.id_cliente = :idCliente
                    AND entregas_faturamento_item.situacao = 'EN'
            );",
            [':idCliente' => Auth::user()->id_colaborador]
        );
        return $consulta;
    }
}
