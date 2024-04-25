<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as RequestFacade;
use MobileStock\helper\Validador;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\TransacaoFinanceira\TransacaoFinanceiraModel;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\PedidoItem\PedidoItem;

class Fraudes extends Request_m
{
    public function buscaFraudes()
    {
        $dadosGet = \Illuminate\Support\Facades\Request::all();
        Validador::validar($dadosGet, [
            'pesquisa' => [],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'itens_por_pagina' => [Validador::NAO_NULO],
            'ordenar_decrescente' => [Validador::BOOLEANO],
        ]);
        $dadosGet['itens_por_pagina'] = json_decode($dadosGet['itens_por_pagina'], true) ?? 30;
        $dadosGet['ordenar_decrescente'] = \Illuminate\Support\Facades\Request::boolean('ordenar_decrescente');
        $fraudatarios = ColaboradoresService::buscaColaboradoresFraudulentos(
            $dadosGet['pagina'],
            $dadosGet['itens_por_pagina'],
            $dadosGet['pesquisa'] ?? '',
            $dadosGet['ordenar_decrescente']
        );

        return $fraudatarios;
    }

    public function alteraSituacaoFraude(int $idColaborador, TransacaoFinanceiraModel $transacaoFinanceira)
    {
        DB::beginTransaction();

        $dadosJson = RequestFacade::all();
        Validador::validar($dadosJson, [
            'situacao' => [Validador::OBRIGATORIO, Validador::ENUM('PE', 'LG', 'LT', 'FR')],
            'origem' => [Validador::OBRIGATORIO, Validador::ENUM('CARTAO', 'DEVOLUCAO')],
        ]);

        $colaborador = new ColaboradoresService();
        $colaborador->id = $idColaborador;
        $colaborador->situacao_fraude = $dadosJson['situacao'];
        $colaborador->alteraSituacaoFraude(DB::getPdo(), $dadosJson['origem']);

        if (in_array($dadosJson['situacao'], ['LT', 'LG'])) {
            $transacoes = $transacaoFinanceira->buscaTransacoesEmFraude($idColaborador);

            $logisticaItem = new LogisticaItemModel();
            $logisticaItem->id_cliente = $idColaborador;
            foreach ($transacoes->pluck('id') as $idTransacao) {
                PedidoItem::atualizaFraudeParaDireitoDeItem($idTransacao);
                $logisticaItem->id_transacao = $idTransacao;
                $logisticaItem->liberarLogistica(Origem::ML);
            }
        }

        DB::commit();
    }

    public function buscaSuspeitos()
    {
        try {
            $this->retorno['data'] = ColaboradoresService::buscaColaboradoresSuspeitos($this->conexao);
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['data'] = [];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function listaTransacoesSuspeito(int $idColaborador, TransacaoFinanceiraModel $transacaoFinanceira)
    {
        $transacaoFinanceiras = $transacaoFinanceira->buscaTransacoesEmFraude($idColaborador);

        return $transacaoFinanceiras;
    }

    public function buscaSituacaoFraude()
    {
        try {
            $colaboradoresService = new ColaboradoresService();
            $colaboradoresService->id = $this->idCliente;
            $colaboradoresService->buscaSituacaoFraude($this->conexao, ['DEVOLUCAO', 'CARTAO']);
            $this->retorno['data'] = $colaboradoresService->situacao_fraude;
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

    public function buscaFraudesDevolucoes()
    {
        $fraudatarios = ColaboradoresService::buscaFraudesPendentesDevolucoes();

        return $fraudatarios;
    }

    public function alteraValorMinimoParaEntrarFraude()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_colaborador' => [Validador::NUMERO],
            ]);
            ColaboradoresService::alteraValorMinimoPraEntrarNaFraude(
                $this->conexao,
                $dadosJson['id_colaborador'],
                $dadosJson['valor']
            );

            $colaboradoresService = new ColaboradoresService();
            $colaboradoresService->situacao_fraude = 'LG';
            $colaboradoresService->id = $dadosJson['id_colaborador'];
            $colaboradoresService->alteraSituacaoFraude($this->conexao, 'DEVOLUCAO');
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

    public function forcaEntradaFraude()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );
            $idColaborador = json_decode($this->json, true)['id_colaborador'];
            Validador::validar(
                ['id_colaborador' => $idColaborador],
                [
                    'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]
            );

            $colaboradoresService = new ColaboradoresService();
            $colaboradoresService->id = $idColaborador;
            $colaboradoresService->origem_transacao = null;

            $colaboradoresService->buscaSituacaoFraude($this->conexao, ['DEVOLUCAO']);

            if (!is_null($colaboradoresService->situacao_fraude)) {
                throw new \Exception('Colaborador já está na fraude de devoluções!');
            }
            $valorMinimoFraude = ConfiguracaoService::buscaValorMinimoEntrarFraude($this->conexao);
            $colaboradoresService->insereFraude($this->conexao, 'DEVOLUCAO', $valorMinimoFraude);
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
}
