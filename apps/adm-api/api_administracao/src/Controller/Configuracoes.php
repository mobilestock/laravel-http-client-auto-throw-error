<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Validador;
use MobileStock\service\CatalogoPersonalizadoService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\ProdutosPontosMetadadosService;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Configuracoes extends Request_m
{
    public function buscaPorcentagensComissoes()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaPorcentagemComissoes($this->conexao);
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
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

    public function alteraPorcentagensComissoes()
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
                'comissao_ms' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'comissao_ml' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'comissao_ponto_coleta' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            ConfiguracaoService::alteraPorcentagensComissoes($this->conexao, $dadosJson);
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
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

    // public function atualizaAlertaChatAtendimento()
    // {
    //     try {
    //         $this->conexao->beginTransaction();

    //         Validador::validar(["json" => $this->json], [
    //             "json" => [Validador::OBRIGATORIO, Validador::JSON]
    //         ]);

    //         $dadosJson = json_decode($this->json, true);

    //         Validador::validar($dadosJson, [
    //             'mensagem_alerta' => [Validador::NAO_NULO],
    //         ]);

    //         ConfiguracaoService::atualizaAlertaChatAtendimento($this->conexao, $dadosJson['mensagem_alerta']);

    //         $this->conexao->commit();
    //     } catch (\Throwable $error) {
    //         $this->conexao->rollBack();
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $error->getMessage();
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //     }
    // }

    public function buscaValorMinimoEntrarFraude()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaValorMinimoEntrarFraude($this->conexao);
        } catch (\Throwable $error) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $error->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaPorcentagemAntecipacao()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaPorcentagemAntecipacao($this->conexao);
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
            ]);
            $this->retorno['data'] = ConfiguracaoService::alteraValorMinimoPraEntrarNaFraude(
                $this->conexao,
                $dadosJson['valor']
            );
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

    public function buscaFatoresReputacao()
    {
        try {
            $this->retorno['data'] = ProdutosPontosMetadadosService::buscaMetadados(
                $this->conexao,
                ProdutosPontosMetadadosService::GRUPO_REPUTACAO_FORNECEDORES
            );
            $this->retorno['message'] = 'Fatores de reputação encontrados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['data'] = null;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 500;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function alteraPorcentagemAntecipacao()
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
                'porcentagem_antecipacao' => [Validador::NUMERO],
            ]);
            ConfiguracaoService::alteraPorcentagemAntecipacao($this->conexao, $dadosJson['porcentagem_antecipacao']);
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

    public function alteraFatoresReputacao()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar(['json' => $dadosJson], ['json' => [Validador::ARRAY]]);
            ProdutosPontosMetadadosService::alterarMetadados(
                $this->conexao,
                $dadosJson,
                ProdutosPontosMetadadosService::GRUPO_REPUTACAO_FORNECEDORES
            );
            $this->retorno['data'] = true;
            $this->retorno['message'] = 'Fatores de reputação alterados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $e->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaConfiguracoesFrete()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaConfiguracoesDeFrete($this->conexao);
            $this->retorno['message'] = 'Configurações de frete buscadas com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = Response::HTTP_OK;
        } catch (\Throwable $e) {
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function alteraConfiguracoesFrete()
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
                'percentual_para_cortar_pontos' => [Validador::NUMERO],
                'tamanho_raio_padrao_ponto_parado' => [Validador::NUMERO],
                'minimo_entregas_para_cortar_pontos' => [Validador::NUMERO],
            ]);

            ConfiguracaoService::alteraConfiguracoesDeFrete(
                $this->conexao,
                $dadosJson['percentual_para_cortar_pontos'],
                $dadosJson['tamanho_raio_padrao_ponto_parado'],
                $dadosJson['minimo_entregas_para_cortar_pontos']
            );

            $this->codigoRetorno = Response::HTTP_OK;
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaHorariosSeparacao(PDO $conexao)
    {
        $horarios = ConfiguracaoService::horariosSeparacaoFulFillment($conexao);

        return $horarios;
    }
    public function alteraHorariosSeparacao(
        PDO $conexao,
        Request $request,
        PontosColetaAgendaAcompanhamentoService $agenda
    ) {
        try {
            $conexao->beginTransaction();
            $dadosJson = $request->all();
            Validador::validar($dadosJson, [
                'horarios' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $horariosAux = ConfiguracaoService::horariosSeparacaoFulfillment($conexao);
            ConfiguracaoService::salvaHorariosSeparacaoFulfillment($conexao, $dadosJson['horarios']);

            $horariosRemovidos = array_diff($horariosAux, $dadosJson['horarios']);
            foreach ($horariosRemovidos as $horario) {
                $agenda->horario = $horario;
                $agenda->limpaHorarios();
            }

            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }

    public function buscaInformacoesAplicarPromocao(PDO $conexao)
    {
        $dados = ConfiguracaoService::produtosPromocoes($conexao);
        return $dados;
    }

    public function alterarOrdenamentoFiltros(Request $request, PDO $conexao)
    {
        $arrayValores = $request->all();
        Validador::validar(
            ['array_valores' => $arrayValores],
            [
                'array_valores' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]
        );

        $catalogosPersonalizadosPublicos = CatalogoPersonalizadoService::buscarListaCatalogosPublicos($conexao, null);
        $catalogosPersonalizadosPublicos = array_column($catalogosPersonalizadosPublicos, 'id');

        $filtrosPadroes = ConfiguracaoService::buscarOrdenamentosFiltroCatalogo($conexao)['filtros_pesquisa_padrao'];
        $filtrosPadroes = array_column($filtrosPadroes, 'id');

        $filtrosTotais = array_merge($catalogosPersonalizadosPublicos, $filtrosPadroes);
        foreach ($arrayValores as $valor) {
            if (!in_array($valor, $filtrosTotais)) {
                throw new \Exception("O filtro $valor não existe");
            }
        }

        ConfiguracaoService::alterarOrdenamentoFiltroCatalogo($conexao, $arrayValores);
    }

    public function buscarTempoCacheFiltros(PDO $conexao)
    {
        $configuracao = ConfiguracaoService::buscarTempoExpiracaoCacheFiltro($conexao);
        return $configuracao;
    }

    public function buscarTaxaProdutoErrado()
    {
        $configuracao = ConfiguracaoService::buscarTaxaProdutoErrado();
        return $configuracao;
    }

    public function alterarTaxaProdutoErrado()
    {
        DB::beginTransaction();

        if (App::isProduction() && !in_array(Auth::user()->id, [356, 526])) {
            throw new UnauthorizedHttpException('Bearer', 'Este usuário não tem permissão para esse tipo de ação');
        }

        $dados = \Illuminate\Support\Facades\Request::all();

        Validador::validar($dados, ['taxa' => [Validador::OBRIGATORIO, Validador::NUMERO]]);

        ConfiguracaoService::alterarTaxaProdutoErrado($dados['taxa']);
        DB::commit();
    }
    public function buscaTaxaBloqueioFornecedor(PDO $conexao)
    {
        $retorno = ConfiguracaoService::buscaTaxaBloqueioFornecedor($conexao);
        return $retorno;
    }

    public function alteraTaxaBloqueioFornecedor(PDO $conexao, Request $request)
    {
        $dadosJson = $request->all();

        Validador::validar($dadosJson, [
            'taxa_bloqueio_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $retorno = new ConfiguracaoService();
        $retorno->alteraTaxaBloqueioFornecedor($conexao, $dadosJson['taxa_bloqueio_fornecedor']);
    }
}
