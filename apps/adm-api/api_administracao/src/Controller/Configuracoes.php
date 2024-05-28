<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\model\Municipio;
use MobileStock\service\CatalogoPersonalizadoService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
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
        } catch (Throwable $ex) {
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
        } catch (Throwable $ex) {
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
        } catch (Throwable $error) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

    public function buscaConfiguracoesFrete()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaConfiguracoesDeFrete($this->conexao);
            $this->retorno['message'] = 'Configurações de frete buscadas com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = Response::HTTP_OK;
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

        $dados = FacadesRequest::all();

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

    public function buscaPaineisImpressao()
    {
        $retorno = ConfiguracaoService::buscaPaineisImpressao();
        return $retorno;
    }

    public function alteraPaineisImpressao()
    {
        $dadosJson = FacadesRequest::all();
        foreach ($dadosJson['paineis_impressao'] as $item) {
            Validador::validar(
                [
                    'painel' => $item,
                ],
                [
                    'painel' => [Validador::NUMERO],
                ]
            );
        }
        ConfiguracaoService::alteraPaineisImpressao($dadosJson['paineis_impressao']);
    }

    public function buscaEstados()
    {
        $estados = Municipio::buscaEstados();
        return $estados;
    }
    public function buscaFatores(string $area)
    {
        Validador::validar(
            ['area' => $area],
            [
                'area' => [
                    Validador::ENUM(
                        ConfiguracaoService::REPUTACAO_FORNECEDORES,
                        ConfiguracaoService::PONTUACAO_PRODUTOS
                    ),
                ],
            ]
        );
        if ($area === ConfiguracaoService::REPUTACAO_FORNECEDORES) {
            $retorno = ConfiguracaoService::buscaFatoresReputacaoFornecedores();
        } else {
            $retorno = ConfiguracaoService::buscaFatoresPontuacaoProdutos();
        }

        return $retorno;
    }
    public function alteraFatores(string $area)
    {
        DB::beginTransaction();
        Validador::validar(
            ['area' => $area],
            [
                'area' => [
                    Validador::ENUM(
                        ConfiguracaoService::REPUTACAO_FORNECEDORES,
                        ConfiguracaoService::PONTUACAO_PRODUTOS
                    ),
                ],
            ]
        );

        $dadosJson = FacadesRequest::all();
        if ($area === ConfiguracaoService::REPUTACAO_FORNECEDORES) {
            $validadores = [
                'dias_mensurar_cancelamento' => [Validador::NUMERO],
                'dias_mensurar_media_envios' => [Validador::NUMERO],
                'dias_mensurar_vendas' => [Validador::NUMERO],
                'media_dias_envio_excelente' => [Validador::NUMERO],
                'media_dias_envio_melhor_fabricante' => [Validador::NUMERO],
                'media_dias_envio_regular' => [Validador::NUMERO],
                'taxa_cancelamento_excelente' => [Validador::NUMERO],
                'taxa_cancelamento_melhor_fabricante' => [Validador::NUMERO],
                'taxa_cancelamento_regular' => [Validador::NUMERO],
                'valor_vendido_excelente' => [Validador::NUMERO],
                'valor_vendido_melhor_fabricante' => [Validador::NUMERO],
                'valor_vendido_regular' => [Validador::NUMERO],
            ];
        } else {
            $validadores = [
                'atraso_separacao' => [Validador::NUMERO],
                'avaliacao_4_estrelas' => [Validador::NUMERO],
                'avaliacao_5_estrelas' => [Validador::NUMERO],
                'devolucao_defeito' => [Validador::NUMERO],
                'devolucao_normal' => [Validador::NUMERO],
                'dias_mensurar_avaliacoes' => [Validador::NUMERO],
                'dias_mensurar_cancelamento' => [Validador::NUMERO],
                'dias_mensurar_trocas_defeito' => [Validador::NUMERO],
                'dias_mensurar_trocas_normais' => [Validador::NUMERO],
                'dias_mensurar_vendas' => [Validador::NUMERO],
                'pontuacao_cancelamento' => [Validador::NUMERO],
                'pontuacao_venda' => [Validador::NUMERO],
                'pontuacao_fulfillment' => [Validador::NUMERO],
                'reputacao_excelente' => [Validador::NUMERO],
                'reputacao_melhor_fabricante' => [Validador::NUMERO],
                'reputacao_regular' => [Validador::NUMERO],
                'reputacao_ruim' => [Validador::NUMERO],
            ];
        }

        Validador::validar($dadosJson, $validadores);
        $dadosJson = Arr::only($dadosJson, array_keys($validadores));
        if ($area === ConfiguracaoService::REPUTACAO_FORNECEDORES) {
            ConfiguracaoService::alteraFatoresReputacaoFornecedores($dadosJson);
        } else {
            ConfiguracaoService::alteraFatoresPontuacaoProdutos($dadosJson);
        }

        DB::commit();
    }
    public function buscaQtdMaximaDiasProdutoParadoEstoque()
    {
        $qtdDias = ConfiguracaoService::buscaQtdMaximaDiasEstoqueParadoFulfillment();

        return $qtdDias;
    }
    public function atualizaDiasProdutoParadoNoEstoque()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'dias' => [Validador::NUMERO],
        ]);

        ConfiguracaoService::alteraQtdDiasEstoqueParadoFulfillment($dadosJson['dias']);
    }
}
