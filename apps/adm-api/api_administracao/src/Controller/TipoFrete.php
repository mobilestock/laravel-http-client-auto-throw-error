<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Origem;
use MobileStock\model\PontoColetaModel;
use MobileStock\model\PontosColetaAgendaAcompanhamento;
use MobileStock\model\PontosColetaAgendaAcompanhamentoModel;
use MobileStock\model\TransportadoresRaio;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\IBGEService;
use MobileStock\service\Monitoramento\MonitoramentoService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PontosColetaService;
use MobileStock\service\TipoFreteGruposService;
use MobileStock\service\TipoFreteService;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TipoFrete extends Request_m
{
    public function __construct()
    {
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    //    public function listaPontosMeuLook()
    //	{
    //		try {
    //			$this->retorno['data']['pontos'] = TipoFreteService::listaPontosMeuLook($this->conexao);
    //			$this->retorno['data']['cidades'] = IBGEService::buscarCidadesMeuLookPontos($this->conexao, '');
    //			$this->status = 200;
    //		} catch (\Throwable $ex) {
    //			$this->retorno = [
    //				'status' => false,
    //				'message' => $ex->getMessage(),
    //				'data' => []
    //			];
    //			$this->status = 400;
    //		} finally {
    //			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //		}
    //    }
    // descontinuado substitui pela funcao de alterar os dados do ponto
    // public function voltarPontoParaPendente()
    // {
    // 	try {

    // 		Validador::validar(['json' => $this->json], [
    // 			'json' => [Validador::JSON]
    // 		]);

    // 		$dadosJson = json_decode($this->json, true);

    // 		Validador::validar($dadosJson, [
    // 			'id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
    // 			'id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO]
    // 		]);

    // 		$tipoFrete = new TipoFreteService();
    // 		$tipoFrete->id = $dadosJson['id_ponto'];
    // 		$tipoFrete->categoria = 'PE';
    // 		$tipoFrete->alteraCategoriaTipoFrete($this->conexao, $dadosJson['id_usuario']);

    // 		$this->status = 200;
    // 	} catch (\Throwable $ex) {
    // 		$this->retorno = [
    // 			'status' => false,
    // 			'message' => $ex->getMessage(),
    // 			'data' => []
    // 		];
    // 		$this->status = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    // 	}
    // }

    // public function rejeitaPendente(array $dados)
    // {
    //     try {
    //         Validador::validar($dados, [
    //             'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
    //         ]);

    //         $tipoFrete = new TipoFreteService();
    //         $tipoFrete->id_usuario = $this->idUsuario;
    //         $tipoFrete->id = $dados['id'];
    //         $tipoFrete->rejeitaPendente($this->conexao);
    //         $this->status = 200;
    //     } catch (\Throwable $ex) {
    //         $this->retorno = [
    //             'status' => false,
    //             'message' => $ex->getMessage(),
    //             'data' => [],
    //         ];
    //         $this->status = 400;
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->status)
    //             ->send();
    //     }
    // }

    public function alteraPrevisaoTipoFrete()
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
                // 'previsao' => [Validador::DATA],
                'id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $tipoFrete = new TipoFreteService();
            $tipoFrete->id_usuario = $this->idUsuario;
            $tipoFrete->id = $dadosJson['id_ponto'];
            $tipoFrete->previsao_entrega = $dadosJson['previsao'] ?? 'NULL';
            $tipoFrete->alteraDataPrevisaoTipoFrete($this->conexao, $this->idUsuario);

            $this->status = 200;
        } catch (Throwable $ex) {
            $this->retorno = [
                'status' => false,
                'message' => $ex->getMessage(),
                'data' => [],
            ];
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function buscaProdutosDoPonto()
    {
        $frete = new TipoFreteService();
        $frete->id_usuario = $this->idUsuario;
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            $frete->id_colaborador = $dadosJson['id_colaborador'];
            $this->retorno['data'] = $frete->buscaProdutosDoPonto($this->conexao);
            $this->status = 200;
        } catch (Throwable $ex) {
            $this->retorno = [
                'status' => false,
                'message' => $ex->getMessage(),
                'data' => [],
            ];
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function alteraPonto(TipoFreteService $tipoFrete)
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'emitir_nota_fiscal' => [Validador::NUMERO],
            'horario_de_funcionamento' => [Validador::OBRIGATORIO],
            'prazo_forcar_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'mensagem' => [Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(1000)],
            'preco_ponto' => [Validador::SE(Validador::NAO_NULO, [Validador::NUMERO])],
            'categoria' => [Validador::ENUM('ML', 'PE')],
            'nome' => [Validador::OBRIGATORIO],
            'email' => [Validador::OBRIGATORIO, Validador::EMAIL],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_margem_erro' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        $dadosJson['telefone'] = FacadesRequest::telefone();
        $situacaoAnterior = TipoFreteService::buscaSituacaoPonto(DB::getPdo(), $dadosJson['id_colaborador']);
        $situacaoAtual = TipoFreteService::converteCategoria($dadosJson['categoria']);

        $idUsuario = Auth::user()->id;

        $tipoFrete->id_usuario = $idUsuario;
        $tipoFrete->id = $dadosJson['id_ponto'];
        $tipoFrete->emitir_nota_fiscal = $dadosJson['emitir_nota_fiscal'];
        $tipoFrete->horario_de_funcionamento = $dadosJson['horario_de_funcionamento'];
        $tipoFrete->mensagem = $dadosJson['mensagem'];
        $tipoFrete->categoria = $dadosJson['categoria'];
        $tipoFrete->nome = $dadosJson['nome'];
        $tipoFrete->salva(DB::getPdo());

        $colaboradores = ColaboradorModel::buscaInformacoesColaborador($dadosJson['id_colaborador']);
        if ($dadosJson['email'] !== $colaboradores->email) {
            $colaboradores->email = $dadosJson['email'];
        }
        if ($dadosJson['telefone'] !== $colaboradores->telefone) {
            $colaboradores->telefone = $dadosJson['telefone'];
        }
        $colaboradores->update();

        TransportadoresRaio::removeRaiosDeOutrasCidadesDoColaboraboradorSemVerificar(
            $dadosJson['id_colaborador'],
            $dadosJson['id_cidade']
        );
        $idRaio = TransportadoresRaio::consultaIdRaioDaCidadeDoColaborador(
            $dadosJson['id_colaborador'],
            $dadosJson['id_cidade']
        );

        $transportadoresRaio = new TransportadoresRaio();
        if (!empty($idRaio)) {
            $transportadoresRaio->exists = true;
            $transportadoresRaio->id = $idRaio;
        }
        $transportadoresRaio->id_colaborador = $dadosJson['id_colaborador'];
        $transportadoresRaio->id_cidade = $dadosJson['id_cidade'];
        $transportadoresRaio->preco_entrega = $dadosJson['preco_ponto'];
        $transportadoresRaio->esta_ativo = $situacaoAtual === 'ATIVO';
        $transportadoresRaio->dias_margem_erro = $dadosJson['dias_margem_erro'];
        $transportadoresRaio->prazo_forcar_entrega = $dadosJson['prazo_forcar_entrega'];
        $transportadoresRaio->save();

        if ($situacaoAnterior !== $situacaoAtual) {
            IBGEService::gerenciarPontoColeta(
                DB::getPdo(),
                $dadosJson['id_colaborador'],
                $dadosJson['categoria'] === 'ML',
                $idUsuario
            );
        }

        DB::commit();
    }

    public function buscaListaPontos()
    {
        $pontos = TipoFreteService::buscaPorPontos();

        return $pontos;
    }

    public function listaPontosAtivos()
    {
        $pontosAtivos = TipoFreteService::buscaPontosAtivos();

        return $pontosAtivos;
    }

    public function buscaProdutosPorPonto(int $idPonto)
    {
        $idColaborador = MonitoramentoService::buscaIdColaborador($idPonto);

        $retorno = [
            'chegando' => MonitoramentoService::buscaProdutosChegada($idColaborador),
            'retirando' => MonitoramentoService::buscaProdutosEntrega($idColaborador),
        ];

        return $retorno;
    }

    public function listaEntregadoresComProdutos()
    {
        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'pagina' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
            'pesquisa' => [],
        ]);

        $retorno = TipoFreteService::listaEntregadoresComProdutos($dadosJson['pagina'], $dadosJson['pesquisa']);

        return $retorno;
    }

    public function buscarGruposTipoFrete()
    {
        try {
            $retorno = TipoFreteGruposService::buscarGruposTipoFrete($this->conexao);

            $this->retorno['status'] = true;
            $this->retorno['data'] = $retorno;
            $this->retorno['message'] = 'Grupos encontrados com sucesso';
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

    public function criarGrupoTipoFrete()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $jsonData = json_decode($this->json, true);

            Validador::validar($jsonData, [
                'nome_grupo' => [Validador::OBRIGATORIO],
                'ids_tipo_frete' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'dia_fechamento' => [
                    Validador::OBRIGATORIO,
                    Validador::ENUM('SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA'),
                ],
            ]);

            $idGrupoTipoFrete = TipoFreteGruposService::criarGrupoTipoFrete(
                $this->conexao,
                $jsonData['nome_grupo'],
                $jsonData['dia_fechamento'],
                $this->idUsuario
            );

            TipoFreteGruposService::criaItemGrupoTipoFrete(
                $this->conexao,
                $idGrupoTipoFrete,
                $jsonData['ids_tipo_frete'],
                $this->idUsuario
            );

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Grupo de entrega criado com sucesso';
            $this->conexao->commit();
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscarTipoFrete()
    {
        try {
            $dadosJson = [
                'pesquisa' => $this->request->get('pesquisa'),
                'tipo_ponto' => (string) $this->request->get('tipo_ponto'),
            ];

            Validador::validar($dadosJson, [
                'pesquisa' => [Validador::OBRIGATORIO],
            ]);

            $retorno = TipoFreteService::buscarTipoFrete(
                $this->conexao,
                $dadosJson['pesquisa'],
                $dadosJson['tipo_ponto']
            );

            $this->retorno['status'] = true;
            $this->retorno['data'] = $retorno;
            $this->retorno['message'] = 'Ponto encontrado com sucesso';
        } catch (Throwable $ex) {
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function mudarSituacaoGrupoTipoFrete()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $jsonData = json_decode($this->json, true);

            Validador::validar($jsonData, [
                'id_grupo' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            TipoFreteGruposService::mudarSituacaoGrupoTipoFrete(
                $this->conexao,
                $jsonData['id_grupo'],
                $this->idUsuario
            );

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Grupo de entregas alterado com sucesso';
            $this->conexao->commit();
        } catch (Throwable $ex) {
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

    public function apagarGrupoTipoFrete(array $dados)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dados, [
                'id_grupo' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            TipoFreteGruposService::apagarGrupoTipoFrete($this->conexao, $dados['id_grupo']);

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Grupo apagado com sucesso';
            $this->conexao->commit();
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscarDetalhesGrupoTipoFrete(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id_grupo' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $retorno = TipoFreteGruposService::buscarDetalhesGrupoTipoFrete($this->conexao, $dados['id_grupo']);

            $this->retorno['status'] = true;
            $this->retorno['data'] = $retorno;
            $this->retorno['message'] = 'Grupo de entregas encontrado com sucesso';
        } catch (Throwable $ex) {
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function listarTipoFretePorGrupo($dados)
    {
        try {
            Validador::validar($dados, [
                'id_grupo_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $retorno = TipoFreteGruposService::listarTipoFretePorGrupo($this->conexao, $dados['id_grupo_entrega']);

            $this->resposta = $retorno;
        } catch (Throwable $ex) {
            $this->codigoRetorno = 500;
            $this->resposta['status'] = false;
            $this->resposta['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function editarGrupoTipoFrete()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $jsonData = json_decode($this->json, true);

            Validador::validar($jsonData, [
                'id_grupo' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nome_grupo' => [Validador::OBRIGATORIO],
                'ids_tipo_frete' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'dia_fechamento' => [
                    Validador::OBRIGATORIO,
                    Validador::ENUM('SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA'),
                ],
            ]);

            TipoFreteGruposService::editarGrupoTipoFrete(
                $this->conexao,
                $jsonData['id_grupo'],
                $jsonData['nome_grupo'],
                $jsonData['dia_fechamento'],
                $jsonData['ids_tipo_frete'],
                $this->idUsuario
            );

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Grupo de entregas editado com sucesso';
            $this->conexao->commit();
        } catch (Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function listarGruposPorTipoFrete(PDO $conexao, int $idTipoFrete)
    {
        $retorno = TipoFreteGruposService::listarGruposPorTipoFrete($conexao, $idTipoFrete);

        return $retorno;
    }

    public function listarDestinosDoGrupo(int $idGrupo)
    {
        $retorno = TipoFreteGruposService::listarDestinosDoGrupo($idGrupo);

        return $retorno;
    }

    public function buscaListaPontosColeta(Origem $origem)
    {
        $idColaborador = $origem->ehAdm() ? 0 : Auth::user()->id_colaborador;
        $listaPontosColeta = PontosColetaService::listaPontosDeColeta($idColaborador);

        return $listaPontosColeta;
    }
    public function pesquisarNaListaPontosDeColeta()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [Validador::OBRIGATORIO, Validador::SANIZAR],
        ]);

        $pontosColeta = PontosColetaService::buscaListaPontosDeColeta($dadosJson['pesquisa']);

        return $pontosColeta;
    }
    public function salvaNovosPrazosPontoColeta()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pedido_chegar' => [Validador::NUMERO],
        ]);

        $pontosColeta = PontoColetaModel::buscaInformacoesPontoColeta($dadosJson['id_colaborador']);
        if ($pontosColeta->dias_pedido_chegar === (int) $dadosJson['dias_pedido_chegar']) {
            return;
        }

        $pontosColeta->dias_pedido_chegar = $dadosJson['dias_pedido_chegar'];
        $pontosColeta->update();
    }
    public function buscaDetalhesTarifaPontoColeta()
    {
        $dadosRequest = FacadesRequest::all();

        $dadosRequest['id_colaborador_ponto_coleta'] ??= Auth::user()->id_colaborador;

        Validador::validar($dadosRequest, [
            'id_colaborador_ponto_coleta' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $retorno = PontosColetaService::detalhesTarifaPontoColeta($dadosRequest['id_colaborador_ponto_coleta']);

        return $retorno;
    }
    public function atualizarTarifaPontoColeta()
    {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_colaborador_ponto_coleta' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'valor_custo_frete' => [Validador::NUMERO],
            'deve_recalcular_percentual' => [Validador::BOOLEANO],
            'travar_porcentagem_em' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
        ]);

        $pontoColeta = PontosColetaService::listaPontosDeColeta($dadosJson['id_colaborador_ponto_coleta']);
        $novoValorCustoFrete = (float) $dadosJson['valor_custo_frete'];
        $novoDeveRecalcularPercentual = $dadosJson['deve_recalcular_percentual'];
        if (isset($dadosJson['travar_porcentagem_em'])) {
            $novaPorcentagemFrete = $dadosJson['travar_porcentagem_em'];
            $novaListaEntregas = $pontoColeta['entregas'];
            $novoValorCustoFrete = 0;
            $novoDeveRecalcularPercentual = false;
        } else {
            [
                'porcentagem_frete' => $novaPorcentagemFrete,
                'lista_id_entregas' => $novaListaEntregas,
            ] = PontosColetaService::calculaTarifaPontoColeta(
                $pontoColeta['afiliados'],
                $dadosJson['valor_custo_frete'],
                $pontoColeta['porcentagem_frete']
            );
        }

        PontosColetaService::atualizaTarifaPontoColeta(
            DB::getPdo(),
            $dadosJson['id_colaborador_ponto_coleta'],
            $novoValorCustoFrete,
            $novaPorcentagemFrete,
            $novoDeveRecalcularPercentual
        );
        PontosColetaService::insereLogCalculoPercentualFretePontosColeta(
            $dadosJson['id_colaborador_ponto_coleta'],
            $novaListaEntregas,
            $novoValorCustoFrete,
            $novaPorcentagemFrete
        );

        DB::commit();
    }

    public function listaFilaAprovacao()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'visualizacao' => [Validador::ENUM('ELEGIVEIS', 'PENDENTES')],
            'tipo_frete' => [Validador::ENUM('PONTOS', 'ENTREGADORES')],
        ]);

        $dadosPontos = [];
        if ($dadosJson['tipo_frete'] === 'PONTOS') {
            $dadosPontos = TipoFreteService::buscaListaFiltradaDePontosParados($dadosJson['visualizacao']);
        } else {
            $dadosPontos = TipoFreteService::buscaListaFiltradaDeEntregadores($dadosJson['visualizacao']);
        }

        return $dadosPontos;
    }

    public function buscaListaPedidos()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'situacao' => [Validador::ENUM('AB', 'EX', 'PT', 'EN', 'TD')],
            'pesquisa' => [],
        ]);
        $listaPedidos = EntregaServices::buscaListaDeEntregas($dadosJson);

        if ($dadosJson['situacao'] === 'AB') {
            $listaPedidosSemEntregas = TipoFreteService::listaDePedidosSemEntregas($dadosJson['pesquisa']);

            $listaPedidosSemEntregas = array_filter($listaPedidosSemEntregas, function (array $pedidoSemEntrega) use (
                $listaPedidos
            ): bool {
                foreach ($listaPedidos as $pedidoComEntrega) {
                    $mesmoTransportador = $pedidoSemEntrega['id_tipo_frete'] === $pedidoComEntrega['id_tipo_frete'];
                    $mesmoDestinatario =
                        $pedidoSemEntrega['destinatario']['id_colaborador'] ===
                        $pedidoComEntrega['destinatario']['id_colaborador'];
                    $mesmaCidade =
                        $pedidoSemEntrega['destinatario']['id_cidade'] ===
                        $pedidoComEntrega['destinatario']['id_cidade'];

                    if ($mesmoTransportador && $mesmoDestinatario && $mesmaCidade) {
                        return false;
                    }
                }

                return true;
            });

            $listaPedidos = array_merge($listaPedidos, $listaPedidosSemEntregas);
            $listaPedidos = TipoFreteService::ordenarListaPedidos($listaPedidos);
        }

        return $listaPedidos;
    }

    public function buscaMaisDetalhesDoPedido()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_destinatario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_entrega' => [],
        ]);

        $informacoes = TipoFreteService::buscaMaisDetalhesDoPedido(
            $dadosJson['id_tipo_frete'],
            $dadosJson['id_destinatario'],
            $dadosJson['id_entrega']
        );

        return $informacoes;
    }
    public function buscarAgendaPontosColeta(int $idColaborador, PontosColetaAgendaAcompanhamentoService $agenda)
    {
        $agenda->id_colaborador = $idColaborador;
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();

        return $pontoColeta;
    }

    public function criarHorarioAgendaPontoColeta()
    {
        DB::beginTransaction();
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'frequencia' => [
                Validador::ENUM(
                    PontosColetaAgendaAcompanhamento::FREQUENCIA_PONTUAL,
                    PontosColetaAgendaAcompanhamento::FREQUENCIA_RECORRENTE
                ),
            ],
            'dia' => [Validador::ENUM(...Globals::DIAS_SEMANA)],
            'horario' => [Validador::OBRIGATORIO],
        ]);

        $agenda = new PontosColetaAgendaAcompanhamentoModel();
        $agenda->id_colaborador = $dadosJson['id_colaborador'];
        $agenda->frequencia = $dadosJson['frequencia'];
        $agenda->dia = $dadosJson['dia'];
        $agenda->horario = $dadosJson['horario'];
        $agenda->save();

        DB::commit();
        return new Response(null, Response::HTTP_CREATED);
    }

    public function removerHorarioAgendaPontoColeta(
        PDO $conexao,
        PontosColetaAgendaAcompanhamentoService $agenda,
        int $idAgendamento
    ) {
        try {
            $conexao->beginTransaction();

            $agenda->id = $idAgendamento;
            $agenda->remove();

            $conexao->commit();
            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }
    public function buscarCentrais()
    {
        $centrais = PontosColetaService::buscaCentrais();

        return $centrais;
    }

    public function buscaEntregadoresProximos()
    {
        $colaboradorEndereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
        $raiosCidadeColaborador = TransportadoresRaio::buscaRaiosDeCidades($colaboradorEndereco->id_cidade);
        $entregadorProximo = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho('PM');

        return [
            'entregadores_locais' => $raiosCidadeColaborador,
            'entregador_proximo' => $entregadorProximo,
        ];
    }
}
