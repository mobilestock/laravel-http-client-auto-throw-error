<?php

namespace MobileStock\service;

use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\NotificaDireitoItemCancelado;
use MobileStock\model\PedidoItem;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;

class CancelamentoProdutos
{
    protected array $bindValues;
    protected array $produtos;
    protected string $motivoCancelamento;

    public function __construct(array $produtos, string $motivoCancelamento = 'CLIENTE_DESISTIU')
    {
        $this->produtos = $produtos;
        [$whereUuids, $bind] = ConversorArray::criaBindValues($produtos);
        $this->bindValues = ['whereUuids' => $whereUuids, 'bind' => $bind];
        $this->motivoCancelamento = $motivoCancelamento;
    }

    public function direitosItem(): void
    {
        $this->gerarLancamentos();

        $stmt = DB::getPdo()->prepare(
            "UPDATE pedido_item
                SET pedido_item.situacao = :situacao
             WHERE pedido_item.uuid IN ({$this->bindValues['whereUuids']});
             DELETE FROM pedido_item
             WHERE pedido_item.uuid IN ({$this->bindValues['whereUuids']});

             UPDATE logistica_item
                SET logistica_item.situacao = 'RE',
                    logistica_item.id_usuario = :idUsuario
             WHERE logistica_item.uuid_produto IN ({$this->bindValues['whereUuids']});
             DELETE FROM logistica_item
             WHERE logistica_item.uuid_produto IN ({$this->bindValues['whereUuids']})"
        );
        $stmt->execute(
            $this->bindValues['bind'] + [':idUsuario' => Auth::id(), ':situacao' => PedidoItem::SITUACAO_EM_ABERTO]
        );

        $rowCount = 0;
        do {
            $rowCount += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if ($rowCount !== count($this->produtos) * 2) {
            throw new DomainException('Não foi possivel realizar o cancelamento.');
        }
    }

    public function liberadosLogistica(): void
    {
        $this->gerarLancamentos();

        $stmt = DB::getPdo()->prepare(
            "UPDATE logistica_item
                SET logistica_item.situacao = 'RE',
                    logistica_item.id_usuario = :idUsuario
             WHERE logistica_item.uuid_produto IN ({$this->bindValues['whereUuids']});
             DELETE FROM logistica_item
             WHERE logistica_item.uuid_produto IN ({$this->bindValues['whereUuids']})"
        );
        $stmt->execute($this->bindValues['bind'] + [':idUsuario' => Auth::id()]);

        $rowCount = 0;
        do {
            $rowCount += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if ($rowCount !== count($this->produtos) * 2) {
            throw new DomainException('Não foi possivel realizar o cancelamento.');
        }
    }

    protected function gerarLancamentos(): void
    {
        $lancamentosPendentesApagar = DB::selectColumns(
            "SELECT lancamento_financeiro_pendente.id
            FROM lancamento_financeiro_pendente
            WHERE lancamento_financeiro_pendente.numero_documento IN ({$this->bindValues['whereUuids']})
            GROUP BY lancamento_financeiro_pendente.id",
            $this->bindValues['bind']
        );

        $consultaGerenciaItens = DB::selectOne(
            "SELECT
                CONCAT('[', COALESCE(GROUP_CONCAT(IF(transacao_financeiras_produtos_itens.momento_pagamento = 'PAGAMENTO', JSON_OBJECT(
                    'sequencia', 1,
                    'tipo', 'R',
                    'documento', 15,
                    'situacao', 1,
                    'origem', 'ES',
                    'id_colaborador', transacao_financeiras_produtos_itens.id_fornecedor,
                    'valor', transacao_financeiras_produtos_itens.comissao_fornecedor,
                    'valor_total', transacao_financeiras_produtos_itens.comissao_fornecedor,
                    'id_usuario', :id_usuario,
                    'observacao', 'Estorno de comissão paga no momento do pagamento',
                    'numero_documento', transacao_financeiras_produtos_itens.uuid_produto,
                    'transacao_origem', transacao_financeiras_produtos_itens.id_transacao
                ), NULL)), ''), ']') json_lancamentos_normais_gerar,
                CONCAT('[', GROUP_CONCAT(IF(transacao_financeiras_produtos_itens.id_responsavel_estoque <> 1,
                    CONCAT('\"', transacao_financeiras_produtos_itens.uuid_produto, '\"'),
                    NULL
                )), ']') produtos_externos_json
            FROM transacao_financeiras_produtos_itens
            WHERE transacao_financeiras_produtos_itens.uuid_produto IN ({$this->bindValues['whereUuids']})
              AND transacao_financeiras_produtos_itens.tipo_item = 'PR'",
            [':id_usuario' => Auth::id()] + $this->bindValues['bind']
        );
        $lancamentosGerar['normal'] = $consultaGerenciaItens['lancamentos_normais_gerar'] ?? [];
        $lancamentosGerar['pendente'] = [];
        $produtosExternos = $consultaGerenciaItens['produtos_externos'];

        if (!empty($produtosExternos)) {
            $job = new NotificaDireitoItemCancelado($produtosExternos);
            dispatch($job->afterCommit());
        }

        $sqlValorEstornado = TransacaoConsultasService::sqlValorEstornado();
        $pedidosCorrigidos = DB::select(
            "SELECT
                COALESCE((
                    transacao_financeiras.valor_liquido +
                    transacao_financeiras.valor_credito -
                    transacao_financeiras.valor_credito_bloqueado
                ), 0) valor_pode_pagar_credito,
                transacao_financeiras.valor_credito_bloqueado -
                    COALESCE((SELECT SUM(lancamento_financeiro_pendente.valor)
                              FROM lancamento_financeiro_pendente
                              WHERE lancamento_financeiro_pendente.transacao_origem = transacao_financeiras.id
                                AND lancamento_financeiro_pendente.id_colaborador = transacao_financeiras.pagador
                                AND lancamento_financeiro_pendente.origem = 'ES'), 0)
                valor_pode_pagar_bloqueado,
                SUM(itens.preco) valor_corrigido,
                transacao_financeiras.pagador AS id_cliente,
                transacao_financeiras.id,
                $sqlValorEstornado valor_estornado,
                transacao_financeiras.valor_total
            FROM (
                SELECT
                    SUM(transacao_financeiras_produtos_itens.preco) preco,
                    transacao_financeiras_produtos_itens.id_transacao,
                    transacao_financeiras_produtos_itens.uuid_produto
                FROM transacao_financeiras_produtos_itens
                GROUP BY
                    transacao_financeiras_produtos_itens.id_transacao,
                    transacao_financeiras_produtos_itens.uuid_produto
            ) itens
            INNER JOIN transacao_financeiras ON transacao_financeiras.id = itens.id_transacao
            WHERE itens.uuid_produto IN ({$this->bindValues['whereUuids']})
            GROUP BY transacao_financeiras.id;",
            $this->bindValues['bind']
        );

        $lancamentos = $this->estornaCliente($pedidosCorrigidos);
        array_push($lancamentosGerar['normal'], ...$lancamentos['normal'] ?? []);
        array_push($lancamentosGerar['pendente'], ...$lancamentos['pendente'] ?? []);

        $normalBuilder = DB::table('lancamento_financeiro');
        $pendenteBuilder = DB::table('lancamento_financeiro_pendente');
        $grammar = $pendenteBuilder->grammar;

        $sql = '';
        $bind = [];
        if (!empty($lancamentosPendentesApagar)) {
            $bind = array_merge($bind, $grammar->prepareBindingsForDelete($lancamentosPendentesApagar));
            $sql .=
                $grammar->compileDelete(
                    $pendenteBuilder->whereIn('lancamento_financeiro_pendente.id', $lancamentosPendentesApagar)
                ) . ';';
        }

        if (!empty($lancamentosGerar['normal'])) {
            $sql .= $grammar->compileInsert($normalBuilder, $lancamentosGerar['normal']) . ';';
            $bind = array_merge($bind, Arr::flatten($lancamentosGerar['normal'], 1));
        }

        if (!empty($lancamentosGerar['pendente'])) {
            $sql .= $grammar->compileInsert($pendenteBuilder, $lancamentosGerar['pendente']) . ';';
            $bind = array_merge($bind, Arr::flatten($lancamentosGerar['pendente'], 1));
        }

        $stmt = DB::getPdo()->prepare($sql);

        $stmt->execute($pendenteBuilder->cleanBindings($bind));

        $rowCount = 0;

        do {
            $rowCount += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if (
            $rowCount !==
            count($lancamentosGerar['normal']) +
                count($lancamentosGerar['pendente']) +
                count($lancamentosPendentesApagar)
        ) {
            throw new DomainException('Não foi possivel realizar o cancelamento.');
        }
    }

    public function estornaCliente(array $pedidosCorrigidos): array
    {
        if ($this->motivoCancelamento === 'FRAUDE') {
            return [];
        }

        if (empty($pedidosCorrigidos)) {
            throw new DomainException('Não foi possivel realizar o cancelamento.');
        }

        foreach ($pedidosCorrigidos as $pedidoCorrigido) {
            $valorPagarLancamentoBloqueado = 0;
            $valorPagarLancamento = 0;

            $pedidoCorrigido['valor_corrigido_pendente'] = $pedidoCorrigido['valor_corrigido'];
            if ($pedidoCorrigido['valor_pode_pagar_bloqueado'] > 0) {
                if ($pedidoCorrigido['valor_pode_pagar_bloqueado'] >= $pedidoCorrigido['valor_corrigido_pendente']) {
                    $valorPagarLancamentoBloqueado += $pedidoCorrigido['valor_corrigido_pendente'];
                    $pedidoCorrigido['valor_corrigido_pendente'] -= $pedidoCorrigido['valor_corrigido_pendente'];
                } else {
                    $valorPagarLancamentoBloqueado += $pedidoCorrigido['valor_pode_pagar_bloqueado'];
                    $pedidoCorrigido['valor_corrigido_pendente'] -= $pedidoCorrigido['valor_pode_pagar_bloqueado'];
                }
            }

            $pedidoCorrigido['valor_corrigido_pendente'] = round($pedidoCorrigido['valor_corrigido_pendente'], 2);
            $valorPagarLancamentoBloqueado = round($valorPagarLancamentoBloqueado, 2);

            if ($pedidoCorrigido['valor_corrigido_pendente'] > 0) {
                if ($pedidoCorrigido['valor_pode_pagar_credito'] >= $pedidoCorrigido['valor_corrigido_pendente']) {
                    $valorPagarLancamento += $pedidoCorrigido['valor_corrigido_pendente'];
                    $pedidoCorrigido['valor_corrigido_pendente'] -= $pedidoCorrigido['valor_corrigido_pendente'];
                } else {
                    $pedidoCorrigido['valor_corrigido_pendente'] -= $pedidoCorrigido['valor_pode_pagar_credito'];
                    $valorPagarLancamento += $pedidoCorrigido['valor_pode_pagar_credito'];
                }
            }

            $pedidoCorrigido['valor_corrigido_pendente'] = round($pedidoCorrigido['valor_corrigido_pendente'], 2);
            $valorPagarLancamento = round($valorPagarLancamento, 2);

            if (
                $pedidoCorrigido['valor_corrigido_pendente'] > 0 ||
                $pedidoCorrigido['valor_corrigido'] !== round($valorPagarLancamento + $valorPagarLancamentoBloqueado, 2)
            ) {
                throw new InvalidArgumentException("Não foi possivel estornar transacao {$pedidoCorrigido['id']}");
            }

            if ($valorPagarLancamentoBloqueado > 0) {
                $lancamentosGerar['pendente'][] = [
                    'tipo' => 'P',
                    'sequencia' => 1,
                    'documento' => 15,
                    'situacao' => 1,
                    'origem' => 'ES',
                    'id_colaborador' => $pedidoCorrigido['id_cliente'],
                    'valor' => $valorPagarLancamentoBloqueado,
                    'valor_total' => $valorPagarLancamentoBloqueado,
                    'id_usuario' => Auth::id(),
                    'observacao' => 'Estorno de comissão paga no momento do pagamento',
                    'transacao_origem' => $pedidoCorrigido['id'],
                ];
            }

            if ($valorPagarLancamento > 0) {
                $lancamentosGerar['normal'][] = [
                    'sequencia' => 1,
                    'tipo' => 'P',
                    'documento' => 15,
                    'situacao' => 1,
                    'origem' => 'ES',
                    'id_colaborador' => $pedidoCorrigido['id_cliente'],
                    'valor' => $valorPagarLancamento,
                    'valor_total' => $valorPagarLancamento,
                    'id_usuario' => Auth::id(),
                    'observacao' => 'Estorno de comissão paga no momento do pagamento',
                    'numero_documento' => DB::raw('DEFAULT(numero_documento)'),
                    'transacao_origem' => $pedidoCorrigido['id'],
                ];
            }

            if (
                round($pedidoCorrigido['valor_corrigido'] + $pedidoCorrigido['valor_estornado'], 2) ===
                $pedidoCorrigido['valor_total']
            ) {
                $transacao = new TransacaoFinanceiraService();
                $transacao->id = $pedidoCorrigido['id'];
                $transacao->status = 'ES';
                $transacao->atualizaTransacao(DB::getPdo());
            }
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $lancamentosGerar;
    }
}
