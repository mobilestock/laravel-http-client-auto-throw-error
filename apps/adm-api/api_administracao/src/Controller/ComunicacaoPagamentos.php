<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\CreditosDebitosService;
use MobileStock\service\Fila\FilaService;
use MobileStock\service\IuguService\IuguServicePagamento;
use MobileStock\service\Pagamento\PagamentoPixSicoob;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\TransferenciasService;
use PDO;
use Symfony\Component\HttpFoundation\Response;

class ComunicacaoPagamentos extends Request_m
{
    public function __construct()
    {
        parent::__construct();
        $this->conexao = app(PDO::class);
    }
    public function buscaSituacao(FilaService $fila)
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'transacao' => [Validador::OBRIGATORIO],
            'id_pagador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'tipo' => [Validador::OBRIGATORIO, Validador::ENUM('Iugu', 'Sicoob')],
        ]);
        extract($dadosJson);
        switch ($tipo) {
            case 'Iugu':
                $iugu = new IuguServicePagamento($id_pagador);
                $iugu->transacao = $transacao;
                $iugu->id = $id;
                if ($dados = $iugu->sincronizaTransacao()) {
                    $fila->url_fila = $_ENV['SQS_ENDPOINTS']['ATUALIZAR_PAGAMENTO_WEBHOOK'];
                    $fila->conteudoArray = [
                        'event' => 'invoice.due',
                        'data' => [
                            'id' => $dados['resposta']->id,
                            'status' => $dados['resposta']->status,
                        ],
                        'precisa_resposta' => true,
                    ];
                    $fila->envia();
                }
                break;
            case 'Sicoob':
                $transacaoObj = new TransacaoFinanceiraService();
                $transacaoObj->cod_transacao = $transacao;
                $interfacePagamento = app(PagamentoPixSicoob::class, [
                    'transacao' => $transacaoObj,
                ]);

                if (!($situacao = $interfacePagamento->converteSituacaoApi())) {
                    break;
                }
                $fila->url_fila = $_ENV['SQS_ENDPOINTS']['ATUALIZAR_PAGAMENTO_WEBHOOK'];
                $fila->conteudoArray = [
                    'event' => 'invoice.due',
                    'data' => [
                        'id' => $transacao,
                        'status' => $situacao,
                    ],
                    'precisa_resposta' => true,
                ];
                $fila->envia();
        }

        if (empty($fila->id)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        return $fila->id;
    }
    // public function listaRecebiveis()
    // {
    //     try {
    //         $dadosUsuario = json_decode($this->json, true);
    //         $this->retorno['data']['recebiveis'] = RecebiveisConsultas::buscaRecebivel($this->conexao, $dadosUsuario["id_lancamento"]);
    //         $this->retorno['data']['id'] = RecebiveisConsultas::buscaIdIugu($this->conexao, $dadosUsuario["id"]);
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //         $this->conexao->rollBack();
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //     }
    // }

    public function listaTransferencias()
    {
        try {
            $resultado = CreditosDebitosService::listaTransferencias($this->conexao);

            $totalizador['valor_pagamento'] = array_sum(array_column($resultado, 'valor_pagamento'));
            $totalizador['valor_pendente'] = array_sum(array_column($resultado, 'valor_pendente'));
            $totalizador['saldo'] = array_sum(array_column($resultado, 'saldo'));

            $resultado = array_map(function ($item) {
                $item['pagamento_bloqueado'] = $item['pagamento_bloqueado'] === 'T';
                return $item;
            }, $resultado);

            $this->retorno['data'] = ['fila' => $resultado, 'total' => $totalizador];
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->conexao->rollBack();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function inteirarTransferencia(int $idTransferencia)
    {
        TransferenciasService::pagaTransferencia($idTransferencia);
    }
    public function buscaInformacoesPagamentoAutomaticoTransferencias()
    {
        $retorno = [
            'ativado' => ConfiguracaoService::informacaoPagamentoAutomaticoTransferenciasAtivo(DB::getPdo()),
            'informacoes' => TransferenciasService::proximosContempladosAutomaticamente(),
        ];

        return $retorno;
    }
    public function alterarPagamentoAutomaticoTransferenciasPara()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'ativo' => [Validador::BOOLEANO],
            ]);
            $dadosJson['ativo'] = (bool) json_decode($dadosJson['ativo']);

            ConfiguracaoService::modificaPagamentoAutomaticoTransferencia($this->conexao, $dadosJson['ativo']);

            $this->conexao->commit();
            $this->retorno['data'] = ConfiguracaoService::informacaoPagamentoAutomaticoTransferenciasAtivo(
                $this->conexao
            );
            $this->retorno['message'] = 'Pagamento Automático modificado com sucesso!';
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = [];
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function atualizaFilaTransferencia()
    {
        try {
            $this->conexao->beginTransaction();

            TransferenciasService::prioridadePagamentoAutomatico($this->conexao);

            $this->conexao->commit();
            $this->retorno['message'] = 'Fila atualizada com sucesso!';
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = [];
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function deletarTransferencia(array $dados)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dados, [
                'id_transferencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            CreditosDebitosService::deletarTransferencia($this->conexao, $dados['id_transferencia']);

            $this->retorno['status'] = true;
            $this->retorno['data'] = '';
            $this->retorno['message'] = 'Transferência deletada com sucesso!';
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function pagamentoManual()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_transferencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            TransferenciasService::pagamentoManual($this->conexao, $dadosJson['id_transferencia'], $this->idUsuario);

            $this->retorno['message'] = 'Pagamento manual feito com sucesso';
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}
