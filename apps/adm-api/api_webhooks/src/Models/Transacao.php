<?php

namespace api_webhooks\Models;

use Exception;
use MobileStock\model\Lancamento;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\ZoopSellerService;
use PDO;

class Transacao
{
    protected $evento;
    protected $id_zoop_transacao;
    protected $situcao_transacao;
    protected $valor_transacao;
    protected $conexao;
    public $split_rules;

    public function __construct(pdo $conexao, string $evento, string $id_zoop_transacao, string $situcao_transacao, float $valor_transacao)
    {
        $this->evento = $evento;
        $this->id_zoop_transacao = $id_zoop_transacao;
        $this->situcao_transacao = $situcao_transacao;
        $this->valor_transacao = $valor_transacao;
        $this->conexao = $conexao;
    }
    public function atualiza_faturamento()
    {
        switch ($this->evento) {
            case 'transaction.created':
                $this->transacao_sucesso();
                break;    /*Criação de uma nova transação*/
            case 'transaction.authorization.failed':
                $this->transacao_sucesso();
                break;
            case 'transaction.authorization.succeeded':
                $this->transacao_sucesso();
                break;
            case 'transaction.canceled':
                $this->transacao_cancelada();
                //$this->transacao_sucesso();
                break;    /*Estorno ou cancelamento, tanto parciais quanto totais, de uma transação*/
            case 'transaction.capture.failed':
                $this->transacao_sucesso();
                break;
            case 'transaction.capture.succeeded':
                $this->transacao_sucesso();
                break;
            case 'transaction.charged_back':
                $this->transacao_disputa();
                break;    /*Vitória da disputa pela transação por parte do portador do cartão*/
            case 'transaction.commission.succeeded':
                $this->transacao_sucesso();
                break;    /*Geração com sucesso do comissionamento de uma transação*/
            case 'transaction.disputed':
                $this->transacao_disputa();
                break;    /*Recebimento de notificação do adquirente de disputa aberta para uma transação*/
            case 'transaction.dispute.succeeded':
                $this->transacao_disputa();
                break;    /*Vitória da disputa pela transação por parte do estabelecimento comercial*/
            case 'transaction.pre_authorization.failed':
                $this->transacao_sucesso();
                break;
            case 'transaction.pre_authorization.succeeded':
                $this->transacao_sucesso();
                break;
            case 'transaction.pre_authorized':
                $this->transacao_sucesso();
                break;
            case 'transaction.reversed':
                $this->transacao_sucesso();
                break;    /*Reversão de uma transação, ou seja, um cancelamento por falha de comunicação entre a Zoop e a adquirente aprovadora da transação*/
            case 'transaction.succeeded':
                $this->transacao_sucesso();
                break;    /*Autorização (ou captura) de uma transação com sucesso*/
            case 'transaction.updated':
                $this->transacao_sucesso();
                break;
            case 'transaction.void.failed':
                $this->transacao_sucesso();
                break;    /*Estorno da transação falhou?*/
            case 'transaction.void.succeeded':
                $this->transacao_sucesso();
                break;    /*Estorno da transação realizado com sucesso?*/

            default:
                # code...
                break;
        }
    }

    public function transacao_sucesso()
    {

        $consulta = $this->conexao->prepare("SELECT transacao_financeiras.id,
                                                transacao_financeiras.status,
                                                transacao_financeiras.pagador,
                                                COALESCE(transacao_financeiras.valor_acrescimo,0) valor_acrescimo,
                                                transacao_financeiras.metodo_pagamento
                                            FROM transacao_financeiras
                                            WHERE transacao_financeiras.cod_transacao = :cod_zoop LOCK IN SHARE MODE");
        $consulta->bindParam(":cod_zoop", $this->id_zoop_transacao,  PDO::PARAM_STR, 100);
        $consulta->execute();
        $info_transacao = $consulta->fetch(PDO::FETCH_ASSOC);
        
        if ($info_transacao) {
            $pagamento = new TransacaoFinanceiraService;
            $pagamento->id = $info_transacao['id'];
            $pagamento->valor_liquido = $this->valor_transacao;
            $pagamento->status = $this->situcao_transacao;
            $pagamento->atualizaSituacaoTransacao($this->conexao);
            if($info_transacao['status'] === 'CA' && $info_transacao['metodo_pagamento'] === 'BL' && $this->situcao_transacao == 'succeeded'){
                /** crédito par cliente */
                $lancamento = new Lancamento(
                    'P',
                    1,
                    'CM',
                    $info_transacao['pagador'],
                    '',
                    $this->valor_transacao - $info_transacao['valor_acrescimo'],
                    1,
                    15
                );
                $lancamento->transacao_origem = $info_transacao['id'];
                $lancamento->observacao = 'Gerado a partir de pagamento de uma transacao cancelada';
        
                LancamentoCrud::salva($this->conexao, $lancamento );

                $lancamento_pag = new Lancamento(
                    'R',
                    1,
                    'FA',
                    $info_transacao['pagador'],
                    '',
                    $this->valor_transacao,
                    1,
                    15
                );
                $lancamento_pag->situacao = 2;
                $lancamento_pag->valor = $this->valor_transacao;
                $lancamento_pag->valor_pago = $this->valor_transacao;
                $lancamento_pag->transacao_origem = $info_transacao['id'];
                $lancamento_pag->cod_transacao = $this->id_zoop_transacao;
                $lancamento_pag->observacao = 'Gerado a partir de pagamento de uma transacao cancelada';
                $lancamento_pag->documento_pagamento = '15';
        
                LancamentoCrud::salva($this->conexao, $lancamento_pag);

                foreach ($this->split_rules as $key => $f) {
                    $id_seller = ZoopSellerService::buscarIdColaboradorComCodZoop(null,$f->recipient);
                    $lanc = new Lancamento('R', 1, 'PF',$id_seller, '', $f->amount, 1, 15);
                    $lanc->transacao_origem = $info_transacao['id'];
                    $lanc->cod_transacao = $this->id_zoop_transacao;
                    $lanc->valor_total = $f->amount;
                    $lanc->id_split = $f->id;
                    $lanc->sequencia = 1;
                    LancamentoCrud::salva($this->conexao, $lanc);
                } 

            }    
        }else{
            
            throw new Exception(" Nao existe transacao valida com id transacao " . $this->id_zoop_transacao, 1);           
        }

    }
    public function transacao_cancelada()
    {
        Notificacoes::criaNotificacoes($this->conexao, "Recebemos o cancelamento da transacao  " . $this->id_zoop_transacao);
    }

    public function transacao_disputa()
    {
        Notificacoes::criaNotificacoes($this->conexao, "Recebemos uma transação em disputa  " . $this->id_zoop_transacao);
    }
    
}
