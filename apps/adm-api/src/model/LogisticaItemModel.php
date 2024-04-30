<?php

namespace MobileStock\model;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\NegociacoesProdutoTempService;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use RuntimeException;

/**
 * https://github.com/mobilestock/backend/issues/131
 * @property string $uuid_produto
 * @property int $id_usuario
 * @property string $situacao
 * @property int $id_cliente
 * @property int $id_transacao
 * @property int $id_colaborador_tipo_frete
 */
class LogisticaItemModel extends Model
{
    public const REGEX_ETIQUETA_PRODUTO = "/^[0-9]+_[0-9A-z]+\.[0-9]+$/";
    public const SITUACAO_FINAL_PROCESSO_LOGISTICA = 3;

    protected $table = 'logistica_item';
    protected $fillable = ['situacao', 'id_usuario'];

    public function liberarLogistica(string $origem): void
    {
        $condicaoProdutoPago = '';
        $colaboradorTipoFrete = $this->id_colaborador_tipo_frete;

        if ($origem === Origem::ML) {
            $condicaoProdutoPago = 'AND pedido_item.id_transacao = :idTransacao';
            $colaboradorTipoFrete = TransacaoFinanceiraItemProdutoService::buscaFreteTransacao(
                DB::getPdo(),
                $this->id_transacao
            );
        }

        $produtos = DB::select(
            "SELECT
                    (
                        SELECT
                            SUM(transacao_financeiras_produtos_itens.preco)
                        FROM transacao_financeiras_produtos_itens
                        WHERE
                            transacao_financeiras_produtos_itens.id_transacao = pedido_item.id_transacao
                            AND transacao_financeiras_produtos_itens.uuid_produto = pedido_item.uuid
                    ) preco,
                    :idUsuario id_usuario,
                    :idCliente id_cliente,
                    pedido_item.id_produto,
                    pedido_item.nome_tamanho,
                    pedido_item.uuid uuid_produto,
                    pedido_item.id_transacao,
                    pedido_item.id_responsavel_estoque,
                    :colaboradorTipoFrete id_colaborador_tipo_frete,
                    pedido_item.observacao
                FROM pedido_item
                WHERE
                    pedido_item.id_cliente = :idCliente
                    AND pedido_item.situacao = 'DI'
                    $condicaoProdutoPago
                    ;",
            [
                ':idCliente' => $this->id_cliente,
                ':idUsuario' => Auth::id(),
                ':colaboradorTipoFrete' => $colaboradorTipoFrete,
            ] + ($origem === Origem::ML ? [':idTransacao' => $this->id_transacao] : [])
        );

        if (empty($produtos)) {
            throw new RuntimeException('Falha ao identificar os produtos para inserir na logistica.');
        }

        $logisticaItem = DB::table('logistica_item');
        $pedidoItem = DB::table('pedido_item');

        $uuids = array_column($produtos, 'uuid_produto');

        /**
         * logistica_item.preco
         * logistica_item.id_cliente
         * logistica_item.id_usuario
         * logistica_item.id_produto
         * logistica_item.nome_tamanho
         * logistica_item.uuid_produto
         * logistica_item.id_transacao
         * logistica_item.id_responsavel_estoque
         * logistica_item.id_colaborador_tipo_frete
         * logistica_item.observacao
         */
        $stmt = DB::getPdo()->prepare(
            $logisticaItem->grammar->compileInsert($logisticaItem, $produtos) .
                ';' .
                $pedidoItem->grammar->compileDelete($pedidoItem->whereIn('pedido_item.uuid', $uuids))
        );
        $stmt->execute(
            array_merge($logisticaItem->cleanBindings(Arr::flatten($produtos, 1)), $pedidoItem->cleanBindings($uuids))
        );

        $linhasAtualizadas = 0;
        do {
            $linhasAtualizadas += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if (count($produtos) + count($uuids) !== $linhasAtualizadas) {
            throw new RuntimeException('Falha ao inserir os produtos na logistica.');
        }

        separacaoService::alertarSepararProdutoExterno($this->id_transacao);

        $job = new GerenciarAcompanhamento($uuids);
        dispatch($job->afterCommit());
    }

    public static function buscaProdutosCancelamento(): array
    {
        $diasParaOCancelamento = ConfiguracaoService::buscaDiasDeCancelamentoAutomatico(DB::getPdo());
        $uuids = DB::selectColumns(
            "SELECT
                logistica_item.uuid_produto
             FROM logistica_item
             WHERE logistica_item.situacao < :situacao
               AND DATEDIFF_DIAS_UTEIS(CURDATE(), logistica_item.data_criacao) > :dias;",
            [
                ':situacao' => self::SITUACAO_FINAL_PROCESSO_LOGISTICA,
                ':dias' => $diasParaOCancelamento,
            ]
        );

        return $uuids;
    }
    public static function buscaListaProdutosCancelados(): array
    {
        $produtos = DB::select(
            "SELECT
                transacao_financeiras_produtos_itens.id_produto,
                transacao_financeiras_produtos_itens.nome_tamanho AS `tamanho`,
                transacao_financeiras_produtos_itens.uuid_produto,
                transacao_financeiras_produtos_itens.id_transacao,
                fornecedor_colaboradores.razao_social AS `nome_fornecedor`,
                reputacao_fornecedores.reputacao,
                (
                    SELECT colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.id = transacao_financeiras.pagador
                ) AS `nome_cliente`,
                DATE_FORMAT(transacao_financeiras.data_criacao, '%d/%m/%Y %H:%i') AS `data_compra`,
                DATE_FORMAT(logistica_item_data_alteracao.data_criacao, '%d/%m/%Y %H:%i') AS `data_correcao`,
                CASE
                    WHEN logistica_item_data_alteracao.id_usuario = 2 THEN 'CANCELADO_PELO_SISTEMA'
                    WHEN negociacoes_produto_log.id IS NOT NULL THEN 'NEGOCIACAO_RECUSADA'
                    WHEN usuarios.id_colaborador = fornecedor_colaboradores.id THEN 'CANCELADO_PELO_FORNECEDOR'
                END AS `porque_afetou_reputacao`
            FROM logistica_item_data_alteracao
            INNER JOIN usuarios ON usuarios.id = logistica_item_data_alteracao.id_usuario
            INNER JOIN transacao_financeiras_produtos_itens ON transacao_financeiras_produtos_itens.tipo_item = 'PR'
                AND transacao_financeiras_produtos_itens.uuid_produto = logistica_item_data_alteracao.uuid_produto
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = transacao_financeiras_produtos_itens.id_transacao
            INNER JOIN colaboradores AS `fornecedor_colaboradores` ON fornecedor_colaboradores.id = transacao_financeiras_produtos_itens.id_fornecedor
            LEFT JOIN reputacao_fornecedores ON reputacao_fornecedores.id_colaborador = transacao_financeiras_produtos_itens.id_fornecedor
            LEFT JOIN negociacoes_produto_log ON negociacoes_produto_log.situacao = :negociacao_recusada
                AND negociacoes_produto_log.uuid_produto = logistica_item_data_alteracao.uuid_produto
            WHERE logistica_item_data_alteracao.situacao_nova = 'RE'
                AND DATE(logistica_item_data_alteracao.data_criacao) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            GROUP BY transacao_financeiras_produtos_itens.uuid_produto
            HAVING porque_afetou_reputacao IS NOT NULL
            ORDER BY logistica_item_data_alteracao.data_criacao DESC;",
            [':negociacao_recusada' => NegociacoesProdutoTempService::SITUACAO_RECUSADA]
        );

        return $produtos;
    }
    public static function buscaProdutosComConferenciaAtrasada(): array
    {
        $produtosAtrasados = DB::select(
            "SELECT
                colaboradores.telefone,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                (
                    SELECT produtos_foto.caminho
                    FROM produtos_foto
                    WHERE produtos_foto.id = logistica_item.id_produto
                        AND produtos_foto.tipo_foto <> 'SM'
                    ORDER BY produtos_foto.tipo_foto IN ('MD', 'LG') DESC
                    LIMIT 1
                ) foto_produto,
                DATEDIFF_DIAS_UTEIS(CURDATE(), DATE(logistica_item.data_criacao)) = 2 `esta_atrasado`
            FROM logistica_item
            INNER JOIN colaboradores ON colaboradores.id = logistica_item.id_responsavel_estoque
            WHERE logistica_item.id_responsavel_estoque <> 1
                AND logistica_item.situacao < :situacao_logistica
            HAVING esta_atrasado;",
            ['situacao_logistica' => self::SITUACAO_FINAL_PROCESSO_LOGISTICA]
        );

        return $produtosAtrasados;
    }
}
