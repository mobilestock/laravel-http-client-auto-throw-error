<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\repository\UsuariosRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\Lancamento\LancamentoService;
use MobileStock\service\MessageService;
use MobileStock\service\NegociacoesProdutoTempService;
use MobileStock\service\ProdutoService;
use MobileStock\service\ReputacaoFornecedoresService;
use PDO;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class Fornecedor extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();
        // $this->rota = $rota;
        // $this->resposta = new JsonResponse();
        $this->conexao = Conexao::criarConexao();
    }
    // public function buscaDemandaProdutosFornecedor()
    // {
    //     try {
    //         $this->retorno['data'] = ComprasService::buscaDemandaProdutosFornecedor($this->conexao, $this->nivelAcesso, $this->idCliente, "DASHBOARD");
    //         $this->retorno['status'] = true;
    //         $this->retorno['message'] = "Lista de demanda de produtos locazalida com sucesso!";
    //         $this->codigoRetorno = 200;
    //     } catch (\Throwable $exception) {
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $exception->getMessage();
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //     }
    // }
    // public function buscaProdutosMaisAcessados()
    // {
    //     try {
    //         Validador::validar(['json' => $this->json], [
    //             'json' => [Validador::JSON, Validador::OBRIGATORIO]
    //         ]);

    //         $dadosJson = json_decode($this->json, true);
    //         Validador::validar($dadosJson, [
    //             "mes" => [Validador::OBRIGATORIO, Validador::NUMERO],
    //             "ano" => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         $this->retorno['data'] = ProdutosRepository::buscaProdutosMaisClicados($this->conexao, $dadosJson['mes'], $dadosJson['ano'], $this->idCliente);
    //         $this->retorno['message'] = "Produtos encontrados com sucesso!";
    //         $this->retorno['status'] = true;
    //         $this->codigoRetorno = 200;
    //     } catch (\Throwable $e) {
    //         $this->retorno['data'] = null;
    //         $this->retorno['message'] = $e->getMessage();
    //         $this->retorno['status'] = false;
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //         die;
    //     }
    // }
    // public function buscaProdutosMaisAdicionados()
    // {
    //     try {
    //         Validador::validar(['json' => $this->json], [
    //             'json' => [Validador::JSON, Validador::OBRIGATORIO]
    //         ]);

    //         $dadosJson = json_decode($this->json, true);
    //         Validador::validar($dadosJson, [
    //             "mes" => [Validador::OBRIGATORIO, Validador::NUMERO],
    //             "ano" => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         $this->retorno['data'] = ProdutosRepository::buscaProdutosMaisSelecionados($this->conexao, $dadosJson['mes'], $dadosJson['ano'], $this->idCliente);
    //         $this->retorno['message'] = "Produtos encontrados com sucesso!";
    //         $this->retorno['status'] = true;
    //         $this->codigoRetorno = 200;
    //     } catch (\Throwable $e) {
    //         $this->retorno['data'] = null;
    //         $this->retorno['message'] = $e->getMessage();
    //         $this->retorno['status'] = false;
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //         die;
    //     }
    // }
    // public function buscaListaCompraItensEmEstoque($dadosJson)
    // {
    //     try {
    //         Validador::validar($dadosJson, [
    //             "lote" => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         $this->retorno['data'] = ComprasService::consultaComprasItensEmEstoque($this->conexao, $dadosJson['lote']);
    //         $this->retorno['message'] = "Resultado encontrado com sucesso!";
    //         $this->retorno['status'] = true;
    //         $this->codigoRetorno = 200;
    //     } catch (\Throwable $e) {
    //         $this->retorno['data'] = null;
    //         $this->retorno['message'] = $e->getMessage();
    //         $this->retorno['status'] = false;
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //         die;
    //     }
    // }
    public function buscaExtratoFornecedor()
    {
        try {
            Validador::validar($this->request->query->all(), [
                'data_inicial' => [Validador::NAO_NULO],
                'data_final' => [Validador::NAO_NULO],
            ]);
            $dataInicial = $this->request->get('data_inicial');
            $dataFinal = $this->request->get('data_final');
            $this->retorno['data'] = LancamentoService::buscaExtratoFornecedor(
                $this->conexao,
                $this->idCliente,
                $dataInicial,
                $dataFinal
            );
            $this->retorno['message'] = 'Extrato buscado com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $e) {
            $this->retorno['data'] = null;
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
    public function buscaProdutosDefeituosos(int $idFornecedor)
    {
        $resultado = ProdutoService::buscaProdutosDefeituosos($idFornecedor);

        return $resultado;
    }
    public function bloqueiaSeller(array $dadosJson)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dadosJson, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            ColaboradoresService::bloquearReposicaoSeller($this->conexao, $dadosJson['id_fornecedor'], true);

            $this->conexao->commit();
            $this->retorno['message'] = 'Fornecedor bloqueado com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = null;
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
    public function desbloqueiaSeller(array $dadosJson)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dadosJson, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            ColaboradoresService::bloquearReposicaoSeller($this->conexao, $dadosJson['id_fornecedor'], false);

            $this->conexao->commit();
            $this->retorno['message'] = 'Fornecedor desbloqueado com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = null;
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
    public function buscaMediaCancelamentosSeller()
    {
        try {
            $this->retorno['data'] = ColaboradoresService::buscaMediaCancelamentos($this->conexao, $this->idCliente);
            $this->retorno['message'] = 'Média encontrada com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $e) {
            $this->retorno['data'] = null;
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
    public function verificaSellerBloqueado(array $dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ColaboradoresService::verificaSellerBloqueado(
                $this->conexao,
                $dadosJson['id_fornecedor']
            );
            $this->retorno['message'] = 'Informação encontrada com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (Throwable $e) {
            $this->retorno['data'] = null;
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
    public function buscaFornecedores()
    {
        $dadosJson = \Illuminate\Support\Facades\Request::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [],
        ]);
        $fornecedores = ColaboradoresService::buscaFornecedores($dadosJson['pesquisa'] ?? '');

        return $fornecedores;
    }
    public function buscaEstoquesDetalhados()
    {
        $dadosJson = \Illuminate\Support\Facades\Request::all();
        Validador::validar($dadosJson, [
            'estoque' => [Validador::ENUM('FULFILLMENT', 'EXTERNO', 'AGUARD_ENTRADA', 'PONTO_RETIRADA')],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        if (!Gate::allows('ADMIN') && Auth::user()->id_colaborador !== (int) $dadosJson['id_fornecedor']) {
            throw new AccessDeniedHttpException('Você não tem permissão para visualizar esse estoque');
        }

        $retorno = EstoqueService::estoqueDetalhadoPorFornecedor(
            $dadosJson['id_fornecedor'],
            $dadosJson['pagina'],
            $dadosJson['estoque']
        );

        return $retorno;
    }

    public function buscaDiasParaLiberarBotaoUp()
    {
        try {
            $this->retorno[
                'data'
            ] = UsuariosRepository::buscaDiasFaltaParaDesbloquearBotaoAtualizadaDataEntradaProdutos(
                $this->conexao,
                $this->idCliente
            );
            $this->retorno['message'] = 'Dias buscados com sucesso';
            $this->retorno['status'] = true;
        } catch (Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaDadosDashboardFornecedor()
    {
        $retorno = ReputacaoFornecedoresService::buscaDadosDashboardFornecedor();

        return $retorno;
    }

    public function buscaDesempenhoFornecedor(?int $idFornecedor = null)
    {
        $retorno = ColaboradoresService::buscaDesempenhoFornecedores($idFornecedor);

        return $retorno;
    }

    public function buscaProdutosCancelados()
    {
        try {
            $this->retorno['data'] = ProdutoService::buscaListaProdutosCanceladosSeller(
                $this->conexao,
                $this->idCliente
            );
            $this->retorno['message'] = 'Produtos encontrados com sucesso';
            $this->retorno['status'] = true;
        } catch (Throwable $e) {
            $this->retorno['data'] = null;
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
    public function estouCienteCancelamento(array $dadosJson)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dadosJson, [
                'id_alerta' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            ProdutoService::removeAvisoSeller($this->conexao, $this->idCliente, $dadosJson['id_alerta']);

            $this->conexao->commit();
            $this->retorno['message'] = 'Alerta removido com sucesso';
            $this->retorno['status'] = true;
        } catch (Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['data'] = null;
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

    public function buscaValorTotalFulfillment(int $idFornecedor)
    {
        $resposta = EstoqueService::estoqueDetalhadoPorFornecedor($idFornecedor, 1, 'FULFILLMENT');

        return $resposta['valor_total'];
    }
    public function buscaProdutosParaOferecerNegociacaoSubstituicao(
        PDO $conexao,
        Request $request,
        Authenticatable $usuario
    ) {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'pesquisa' => [],
        ]);
        if (!empty($dadosJson['pesquisa'])) {
            $dadosJson['pesquisa'] = Str::toUtf8(trim($dadosJson['pesquisa']));
        }

        $produtos = ProdutoService::buscaProdutosFornecedorParaNegociar(
            $conexao,
            $usuario->id_colaborador,
            $dadosJson['pesquisa'],
            $dadosJson['pagina']
        );

        return $produtos;
    }
    public function abrirNegociacaoSubstituicao(
        PDO $conexao,
        Request $request,
        NegociacoesProdutoTempService $negociacoes,
        EstoqueGradeService $estoque,
        MessageService $msgService,
        Authenticatable $usuario
    ) {
        try {
            $dadosJson = $request->all();
            Validador::validar($dadosJson, [
                'uuid_produto' => [Validador::OBRIGATORIO],
                'itens_oferecidos' => [
                    Validador::OBRIGATORIO,
                    Validador::ARRAY,
                    Validador::TAMANHO_MINIMO(1),
                    Validador::TAMANHO_MAXIMO(5),
                ],
            ]);

            $negociacoes->uuid_produto = $dadosJson['uuid_produto'];
            $existeNegociacao = $negociacoes->buscaNegociacaoAbertaPorProduto();
            if (!empty($existeNegociacao)) {
                throw new ConflictHttpException('Já existe uma negociação aberta para esse produto');
            }

            $produto = ProdutoService::informacoesDoProdutoNegociado($conexao, $dadosJson['uuid_produto']);
            $conexao->beginTransaction();
            // https://github.com/mobilestock/backend/issues/168
            $negociacoes = app(NegociacoesProdutoTempService::class);
            $negociacoes->uuid_produto = $dadosJson['uuid_produto'];
            $negociacoes->itens_oferecidos = $dadosJson['itens_oferecidos'];
            $negociacoes->salva();

            $negociacoes->criarLogNegociacao(
                NegociacoesProdutoTempService::SITUACAO_CRIADA,
                Arr::only($produto, ['id_produto', 'nome_tamanho']),
                $dadosJson['itens_oferecidos'],
                $usuario->id
            );

            $estoque->id_produto = $produto['id_produto'];
            $estoque->nome_tamanho = $produto['nome_tamanho'];
            $estoque->id_responsavel = $produto['id_responsavel_estoque'];
            $estoqueAtual = $estoque->buscaEstoqueEspecifico($conexao);
            if ($estoqueAtual > 0) {
                $estoque->tipo_movimentacao = 'X';
                $estoque->descricao = "Usuário {$usuario->id} abriu uma negociação de substituição do produto {$dadosJson['uuid_produto']}";
                $estoque->alteracao_estoque = '- estoque_grade.estoque';
                $estoque->movimentaEstoque($conexao, $usuario->id);
            }

            $cliente = ColaboradoresService::consultaDadosColaborador($produto['id_cliente']);
            $fornecedor = ColaboradoresService::consultaDadosColaborador(Auth::user()->id_colaborador);
            $mensagem = "Olá, o fornecedor {$fornecedor['nome']} não possui disponibilidade em estoque do ";
            $mensagem .= "produto {$produto['id_produto']} - {$produto['nome_comercial']}, ";
            $mensagem .= "tamanho {$produto['nome_tamanho']}.";
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= 'Acesse o acompanhamento de pedidos do Meu Look ou clique no link abaixo ';
            $mensagem .= 'para ver as opções de substituição, ou caso não gostar de ';
            $mensagem .= 'nenhuma opção oferecida, você poderá cancelar o produto e ter os valores estornados.';
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= "{$_ENV['URL_MEULOOK']}usuario/historico";
            $msgService->sendImageWhatsApp($cliente['telefone'], $produto['foto'], $mensagem);

            $conexao->commit();
        } catch (Throwable $th) {
            if ($conexao->inTransaction()) {
                $conexao->rollback();
            }
            throw $th;
        }
    }
    public function zerarEstoqueResponsavel(EstoqueGradeService $estoque, ?int $idFornecedor = null)
    {
        DB::beginTransaction();

        $idResponsavelEstoque = Auth::user()->id_colaborador;
        if (Gate::allows('ADMIN') && $idFornecedor) {
            $idResponsavelEstoque = $idFornecedor;
        }
        if ($idResponsavelEstoque === 1) {
            throw new InvalidArgumentException('Não é possível zerar o estoque do fulfillment.');
        }

        $estoque->zerarEstoqueDisponivelDoResponsavelSemVerificacao($idResponsavelEstoque, Auth::user()->id);
        DB::commit();
    }
}
