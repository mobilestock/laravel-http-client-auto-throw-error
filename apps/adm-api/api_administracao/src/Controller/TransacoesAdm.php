<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\service\Lancamento\LancamentoFinanceiroAbates;
use MobileStock\service\TransacaoFinanceira\LancamentoConsultasService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use PDO;

class TransacoesAdm extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = self::AUTENTICACAO;
        parent::__construct();
    }

    public function consultaTransacao(PDO $conexao, int $idTransacao)
    {
        $consulta = TransacaoConsultasService::buscaInfoTransacaoDetalhe($conexao, $idTransacao);

        return $consulta;
    }

    public function consultaLancamentos(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data']['lancamentos'] = LancamentoConsultasService::buscaLancamentosDaTransacao(
                $this->conexao,
                $dados['id']
            );
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao buscar meios de pagamento: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function consultaTransferencias(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data']['transferencias'] = TransacaoConsultasService::buscaRecebiveisTransacao(
                $this->conexao,
                $dados['id']
            );
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] =
                'Ocorreu um erro ao buscar as transferencias do transação: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function consultaTrocas(LancamentoFinanceiroAbates $lancamentoFinanceiroAbates, int $idTransacao)
    {
        $idColaborador = TransacaoFinanceiraModel::buscaPagador($idTransacao);
        $lancamentoFinanceiroAbates->abateLancamentosSeNecessario($idColaborador);
        $trocas = $lancamentoFinanceiroAbates->buscaTrocasTransacao($idTransacao, $idColaborador);

        return $trocas;
    }

    public function buscaTransacoesPendentes()
    {
        try {
            Validador::validar($dadosGet = $this->request->query->all(), [
                'pesquisa' => [Validador::NAO_NULO],
                'pagamento' => [Validador::NAO_NULO, Validador::STRING],
            ]);

            $this->retorno['data'] = TransacaoConsultasService::buscaTransacoesPendentes(
                $this->conexao,
                $dadosGet['pagamento'],
                $dadosGet['pesquisa']
            );
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao buscar as transações pendentes: ' . $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function listarTransacoes()
    {
        try {
            $dadosGet = $this->request->query->all();
            if (!empty($dadosGet)) {
                Validador::validar($dadosGet, [
                    'pesquisa' => [Validador::SANIZAR],
                ]);
            }
            $pesquisa = $dadosGet['pesquisa'] ?? null;
            $transacaoConsulta = TransacaoConsultasService::filtroBuscaTransacaoMarketplace($this->conexao, $pesquisa);
            $this->retorno['data'] = $transacaoConsulta;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao listar transações: ' . $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function BuscaTransacaoFiltro()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'id' => [Validador::NUMERO],
                'cod_transacao' => [],
                'entrega' => [Validador::NUMERO],
                'pagador' => [Validador::STRING],
                'meio_pagamento' => [Validador::STRING],
                'status' => [Validador::STRING],
                'data_de' => [Validador::NAO_NULO],
                'data_ate' => [Validador::NAO_NULO],
            ]);

            if ($dadosJson['data_de']) {
                Validador::validar(
                    ['data_de' => $dadosJson['data_de']],
                    [
                        'data_de' => [Validador::DATA],
                    ]
                );
            }

            if ($dadosJson['data_ate']) {
                Validador::validar(
                    ['data_ate' => $dadosJson['data_ate']],
                    [
                        'data_ate' => [Validador::DATA],
                    ]
                );
            }

            $this->retorno['data']['transacoes'] = TransacaoConsultasService::buscaTransacaoFiltros(
                $this->conexao,
                $dadosJson['id'],
                $dadosJson['pagador'],
                $dadosJson['cod_transacao'],
                $dadosJson['entrega'],
                $dadosJson['meio_pagamento'],
                $dadosJson['status'],
                $dadosJson['data_de'],
                $dadosJson['data_ate']
            );
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao buscar transações: ' . $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaTentativaTransacao(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data']['tentativas'] = TransacaoConsultasService::buscaTentativaTransacao(
                $this->conexao,
                $dados['id']
            );
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    // public function buscaProdutosEntregaPendente()
    // {
    //     try {
    //         $dadosJson = array(
    //             "categoria" => $this->request->get('categoria'),
    //             "id_cidade" => $this->request->get('id_cidade', 0),
    //             "id_destinatario" => $this->request->get('id_destinatario', 0),
    //             "id_tipo_frete" => $this->request->get('id_tipo_frete'),
    //         );

    //         Validador::validar($dadosJson, [
    //             "categoria" => [Validador::ENUM('MS', 'ML', 'PE', 'ENVIO_TRANSPORTADORA')],
    //             "id_cidade" => [Validador::NUMERO],
    //             "id_destinatario" => [Validador::NUMERO],
    //             "id_tipo_frete" => [Validador::NUMERO]
    //         ]);
    //         if (empty($dadosJson["id_cidade"]) && empty($dadosJson["id_destinatario"])) {
    //             throw new Exception('Destinatário não encontrado');
    //         }

    //         $this->retorno["data"] = TransacaoConsultasService::produtosEntregaPendentePorCategoria(
    //             $this->conexao,
    //             $dadosJson["id_destinatario"],
    //             $dadosJson["id_cidade"],
    //             $dadosJson["categoria"],
    //             $dadosJson["id_tipo_frete"]
    //         );
    //         $this->codigoRetorno = 200;
    //     } catch (\Throwable $e) {
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $e->getMessage();
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //     }
    // }
}
