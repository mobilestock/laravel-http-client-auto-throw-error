<?php

namespace MobileStock\model;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public static function buscaSituacaoItem(string $uuidProduto): string
    {
        $situacaoItem = DB::selectOneColumn(
            "SELECT logistica_item.situacao
            FROM logistica_item
            WHERE logistica_item.uuid_produto = :uuid_produto;",
            ['uuid_produto' => $uuidProduto]
        );
        if (empty($situacaoItem)) {
            throw new NotFoundHttpException(
                'Este produto não foi encontrado. Verifique se o mesmo foi cancelado ou chame a T.I.'
            );
        }

        return $situacaoItem;
    }
    public static function buscaInformacoesProdutoPraAtualizarPrevisao(string $uuidProduto): array
    {
        $informacao = DB::selectOne(
            "SELECT
                logistica_item.id_transacao,
                logistica_item.situacao,
                logistica_item.id_produto,
                logistica_item.nome_tamanho,
                logistica_item.id_responsavel_estoque,
                tipo_frete.id_colaborador_ponto_coleta,
                transportadores_raios.dias_entregar_cliente,
                transportadores_raios.dias_margem_erro
            FROM logistica_item
            INNER JOIN tipo_frete ON tipo_frete.id_colaborador = logistica_item.id_colaborador_tipo_frete
            INNER JOIN transacao_financeiras_metadados ON transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
                AND transacao_financeiras_metadados.id_transacao = logistica_item.id_transacao
            INNER JOIN transportadores_raios ON transportadores_raios.id = JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_raio')
            WHERE logistica_item.uuid_produto = :uuid_produto;",
            ['uuid_produto' => $uuidProduto]
        );
        if (empty($informacao)) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        return $informacao;
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
    public static function confereItens(array $produtos): void
    {
        foreach ($produtos as $uuidProduto) {
            $logisticaItem = new self();
            $logisticaItem->exists = true;

            $logisticaItem->situacao = 'CO';
            $logisticaItem->uuid_produto = $uuidProduto;

            $logisticaItem->update();
        }
    }
}
