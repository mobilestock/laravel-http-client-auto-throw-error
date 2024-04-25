<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Exception;
use MobileStock\service\Ranking\RankingService;
use MobileStock\service\Ranking\RankingVencedoresItensService;

class Ranking extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    // public function vendasAndamentoColaborador(array $dados)
    // {
    //     try {
    //         $idColaborador = $dados['idColaborador'];
    //         $periodo = $this->request->get('periodo') ?? 'hoje';
    //         $situacao = $this->request->get('situacao') ?? 'geral';

    //         $dados = RankingService::vendasRanking($this->conexao, $idColaborador, $periodo, $situacao);

    //         $this->retorno['data'] = $dados;
    //         $this->retorno['message'] = 'Vendas ranking buscadas com sucesso';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException) {
    //         $this->retorno['message'] = $pdoException->getMessage();
    //         $this->status = 500;

    //     } catch (\Throwable $ex) {
    //         $this->retorno['message'] = $ex->getMessage();
    //         $this->status = 400;

    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function vendasApuracaoColaborador(array $dados)
    {
        try {
            $idLancamento = $dados['idLancamento'];
            $situacao = $this->request->get('situacao') ?? 'geral';

            $dados = RankingVencedoresItensService::buscarVendasLancamentoPendente($this->conexao, $idLancamento, $situacao);

            $this->retorno['data'] = $dados;
            $this->retorno['message'] = 'Vendas ranking buscadas com sucesso';
            $this->status = 200;

        } catch (\PDOException $pdoException) {
            $this->retorno['message'] = $pdoException->getMessage();
            $this->status = 500;

        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;

        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
            exit;
        }
    }
}