<?php

namespace MobileStock\model\TransacaoFinanceira;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\LogisticaItem;
use MobileStock\model\Model;

/**
 * @property float $valor_credito_bloqueado
 * @property float $valor_estornado
 * @property int $id
 * @property string $status
 * @property string $origem_transacao
 * @property int $pagador
 *
 * https://github.com/mobilestock/backend/issues/131
 */
class TransacaoFinanceiraModel extends Model
{
    protected $casts = [
        'valor_credito_bloqueado' => 'float',
    ];
    protected $fillable = ['valor_credito_bloqueado'];

    /**
     * @return array<self[]|float|null>
     */
    public function buscaTransacoesPendentesTroca(string $uuidProduto): array
    {
        $transacoes = self::fromQuery(
            "SELECT
                 transacao_financeiras.id,
                 transacao_financeiras.valor_credito_bloqueado,
                 logistica_item.preco
             FROM transacao_financeiras
             INNER JOIN logistica_item ON logistica_item.id_cliente = transacao_financeiras.pagador
             WHERE transacao_financeiras.valor_credito_bloqueado > 0
               AND logistica_item.situacao > :situacao
               AND logistica_item.uuid_produto = :uuid_produto
               AND logistica_item.data_atualizacao > transacao_financeiras.data_criacao
             GROUP BY transacao_financeiras.id",
            [
                ':situacao' => LogisticaItem::SITUACAO_FINAL_PROCESSO_LOGISTICA,
                ':uuid_produto' => $uuidProduto,
            ]
        )->all();

        if (empty($transacoes)) {
            return [null, null];
        }

        return [$transacoes, (float) $transacoes[0]->preco];
    }

    /**
     * @return Collection<self>
     */
    public function buscaTransacoesEmFraude(int $pagador): Collection
    {
        $transacoes = self::fromQuery(
            "SELECT
                transacao_financeiras.id,
                transacao_financeiras.cod_transacao,
                transacao_financeiras.emissor_transacao
             FROM transacao_financeiras
             JOIN pedido_item ON pedido_item.id_transacao = transacao_financeiras.id
                AND pedido_item.situacao = 'FR'
             WHERE transacao_financeiras.pagador = :id_colaborador
             GROUP BY transacao_financeiras.id",
            [':id_colaborador' => $pagador]
        );

        return $transacoes;
    }

    public static function buscaPagador(int $idTransacao): int
    {
        $pagador = DB::selectOneColumn(
            "SELECT
                transacao_financeiras.pagador
             FROM transacao_financeiras
             WHERE transacao_financeiras.id = ?",
            [$idTransacao]
        );

        return $pagador;
    }

    public static function buscaTransacoesEsqueciTroca(): array
    {
        $transacoes = DB::select(
            "SELECT
                transacao_financeiras.id AS `id_transacao`,
                transacao_financeiras.cod_transacao AS `transacao`,
                transacao_financeiras.pagador,
                transacao_financeiras.emissor_transacao AS `tipo`,
                transacao_financeiras.qrcode_pix,
                transacao_financeiras.qrcode_text_pix,
                transacao_financeiras.origem_transacao,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y às %H:%i') AS `data_criacao`,
                transacao_financeiras.data_criacao AS `data_nao_formatada`,
                transacao_financeiras.valor_liquido
            FROM transacao_financeiras
            WHERE transacao_financeiras.status = 'PE'
                AND transacao_financeiras.origem_transacao = 'ET'
                AND transacao_financeiras.pagador = :id_cliente;",
            ['id_cliente' => Auth::user()->id_colaborador]
        );

        return $transacoes;
    }
}
