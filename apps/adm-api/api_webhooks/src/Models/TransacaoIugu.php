<?php

namespace api_webhooks\Models;

use MobileStock\helper\ClienteException;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\model\Lancamento;
use MobileStock\repository\ContaBancariaRepository;
use MobileStock\service\IuguService\IuguServiceConta;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\PrioridadePagamento\PrioridadePagamentoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraTentativaPagamentoService;
use PDO;

/**
 * @deprecated
 * https://github.com/mobilestock/backend/issues/175
 */
class TransacaoIugu
{
    protected $evento = [];

    public function __construct(PDO $conexao, array $dadosEvento)
    {
        $this->evento = $dadosEvento;
        $this->conexao = $conexao;
    }

    public function atualizaFaturamentoIugo()
    {
        $invoice = [
            'invoice.created',
            'invoice.status_changed',
            'invoice.refund',
            'invoice.payment_failed',
            'invoice.dunning_action',
            'invoice.due',
            'invoice.installment_released',
            'invoice.released',
            'invoice.bank_slip_status',
            'invoice.partially_refunded',
        ];

        if (in_array($this->evento['event'], $invoice)) {
            $this->transacaoIugu();
        } elseif ($this->evento['event'] === 'withdraw_request.status_changed') {
            /**
             * TODO: Transformar essa função no controller
             */
            $this->recebeConfirmacaoSaque();
        } else {
            $this->comunicacaoDefault();
        }
    }

    //    public function atualizaTransacaoIugo()
    //    {
    //        $this->evento['data']['status'] = $this->evento['resposta']->status;
    //        $this->evento['data']['id'] = $this->evento['resposta']->id;
    //        $this->transacaoIugu();
    //    }

    /**
     * @throws PagamentoTransacaoNaoExisteException
     * @throws ClienteException
     */
    public function transacaoIugu()
    {
        $consulta = $this->conexao->prepare("SELECT transacao_financeiras.id,
                                                transacao_financeiras.valor_liquido,
                                                transacao_financeiras.status,
                                                transacao_financeiras.pagador,
                                                transacao_financeiras.cod_transacao,
                                                COALESCE(transacao_financeiras.valor_acrescimo,0) valor_acrescimo,
                                                transacao_financeiras.metodo_pagamento,
                                                transacao_financeiras.id_usuario
                                            FROM transacao_financeiras
                                            WHERE transacao_financeiras.cod_transacao = :cod_iugu");
        $consulta->bindParam(':cod_iugu', $this->evento['data']['id'], PDO::PARAM_STR, 100);
        $consulta->execute();
        $info_transacao = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($info_transacao && $info_transacao['status'] !== 'CA' && $this->evento['data']['status'] == 'expired') {
            $transacao = new TransacaoFinanceiraService();
            $transacao->id = $info_transacao['id'];
            $transacao->BloqueiaLinhaTransacao($this->conexao);
            $transacao->retornaTransacao($this->conexao);
            $transacao->removeTransacaoPaga($this->conexao, 1);
        } elseif ($info_transacao) {
            $pagamento = new TransacaoFinanceiraService();
            $pagamento->id = $info_transacao['id'];
            $pagamento->valor_liquido = $info_transacao['valor_liquido'];
            $pagamento->status = $this->evento['data']['status'];
            $pagamento->atualizaSituacaoTransacao($this->conexao);

            if ($info_transacao['status'] === 'CA' && $this->evento['data']['status'] == 'paid') {
                /** crédito par cliente */
                $lancamento = new Lancamento(
                    'P',
                    1,
                    'CM',
                    $info_transacao['pagador'],
                    '',
                    $info_transacao['valor_liquido'] - $info_transacao['valor_acrescimo'],
                    1,
                    15
                );
                $lancamento->transacao_origem = $info_transacao['id'];
                $lancamento->observacao = 'Gerado a partir de pagamento de uma transacao cancelada';

                LancamentoCrud::salva($this->conexao, $lancamento);

                $lancamento_pag = new Lancamento(
                    'R',
                    1,
                    'FA',
                    $info_transacao['pagador'],
                    '',
                    $info_transacao['valor_liquido'],
                    1,
                    15
                );
                $lancamento_pag->situacao = 2;
                $lancamento_pag->valor = $info_transacao['valor_liquido'];
                $lancamento_pag->valor_pago = $info_transacao['valor_liquido'];
                $lancamento_pag->transacao_origem = $info_transacao['id'];
                $lancamento_pag->cod_transacao = $this->evento['data']['id'];
                $lancamento_pag->observacao = 'Gerado a partir de pagamento de uma transacao cancelada';
                $lancamento_pag->documento_pagamento = '15';

                LancamentoCrud::salva($this->conexao, $lancamento_pag);
            }
        } elseif (
            !TransacaoFinanceiraTentativaPagamentoService::existeTentativa($this->conexao, $this->evento['data']['id'])
        ) {
            throw new ClienteException("Não existe transação \"{$this->evento['data']['id']}\" cadastrada no sistema");
        }
    }
    public function comunicacaoDefault()
    {
        //Notificacoes::criaNotificacoes($this->conexao, "SUCESSO!! Recebemos uma comunicacao da IUGU Evento ".$this->evento['event']." " . implode(' - ',$this->evento['data']));
    }

    public function recebeConfirmacaoSaque()
    {
        $transferencia = new PrioridadePagamentoService();
        $transferencia->id_transferencia = $this->evento['data']['withdraw_request_id'];

        switch ($this->evento['data']['status']) {
            case 'rejected':
                [
                    'valor_pago' => $valor,
                    'iugu_token_live' => $iuguTokenLive,
                    'id_colaborador' => $idColaborador,
                    'situacao' => $situacao,
                    'nome_titular' => $nomeTitularConta,
                    'conta' => $numeroConta,
                ] = PrioridadePagamentoService::consultaContaPrioridadeTravaLinha(
                    $this->conexao,
                    $this->evento['data']['withdraw_request_id']
                );

                if ($situacao != 'EP') {
                    Notificacoes::criaNotificacoes(
                        $this->conexao,
                        "Webhook esta tentando rejeitar transferencia {$this->evento['data']['withdraw_request_id']} com situacao: $situacao."
                    );
                    // return;
                }

                $lancamento = new Lancamento('P', 1, 'EP', $idColaborador, null, $valor, 1, 7);
                $lancamento->observacao =
                    'Uma tentativa de transferencia na conta de "' .
                    $nomeTitularConta .
                    '" Nº da conta: ' .
                    $numeroConta .
                    ' falhou: ' .
                    $this->evento['data']['feedback'];

                LancamentoCrud::salva($this->conexao, $lancamento);

                Notificacoes::criaNotificacoes(
                    $this->conexao,
                    "Transferencia {$this->evento['data']['withdraw_request_id']} rejeitada: {$this->evento['data']['feedback']}",
                    'UR'
                );

                $transferencia->situacao = 'RE';
                $transferencia->atualizaPrioridadePagamentoPorIdTransferencia($this->conexao);

                ContaBancariaRepository::bloqueiaContaIugu($this->conexao, $iuguTokenLive);

                $iuguService = new IuguServiceConta();
                $iuguService->apiToken = $iuguTokenLive;
                $iuguService->transfereDinheiroMobile($valor * 100);
                break;

            case 'processing':
                $transferencia->situacao = 'EP';
                $transferencia->atualizaPrioridadePagamentoPorIdTransferencia($this->conexao);
                break;

            case 'accepted':
                $transferencia->situacao = 'PA';
                $transferencia->atualizaPrioridadePagamentoPorIdTransferencia($this->conexao);
                break;
        }
    }
}
