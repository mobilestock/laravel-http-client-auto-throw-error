<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizadoModel;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\Origem;
use MobileStock\model\Produto;
use MobileStock\model\ProdutoModel;
use MobileStock\model\ProdutosCategorias;
use MobileStock\model\Reposicao;
use MobileStock\repository\EstoqueRepository;
use MobileStock\repository\NotificacaoRepository;
use MobileStock\repository\ProdutosCategoriasRepository;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\CatalogoPersonalizadoService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\Compras\MovimentacoesService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\MessageService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\ProdutoService;
use MobileStock\service\TipoFreteService;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class Produtos extends Request_m
{
    public function __construct()
    {
        parent::__construct();

        $this->conexao = Conexao::criarConexao();
    }

    public function salva(PDO $conexao, Request $request, Authenticatable $usuario, Gate $gate)
    {
        try {
            $conexao->beginTransaction();

            $dadosFormData = $request->all();

            if ($gate->allows('FORNECEDOR') && $usuario->id_colaborador !== (int) $dadosFormData['id_fornecedor']) {
                throw new BadRequestHttpException('Você não tem permissão para editar este produto.');
            }

            if (
                isset($dadosFormData['outras_informacoes']) &&
                mb_strtolower($dadosFormData['outras_informacoes']) == 'null'
            ) {
                unset($dadosFormData['outras_informacoes']);
            }
            if (mb_strtolower($dadosFormData['embalagem']) == 'null') {
                unset($dadosFormData['embalagem']);
            }

            Validador::validar($dadosFormData, [
                'descricao' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'valor_custo_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_linha' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'especial' => [Validador::BOOLEANO],
                'nome_comercial' => [Validador::OBRIGATORIO],
                'sexo' => [Validador::OBRIGATORIO, Validador::ENUM('FE', 'MA', 'UN')],
                'grade_min' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'grade_max' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'tipo_grade' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'array_id_categoria' => [Validador::OBRIGATORIO, Validador::JSON],
                'grades' => [Validador::OBRIGATORIO, Validador::JSON],
                'cores' => [Validador::OBRIGATORIO, Validador::JSON],
                'fora_de_linha' => [Validador::BOOLEANO],
                'permitido_repor' => [Validador::BOOLEANO],
                'embalagem' => [
                    Validador::SE(
                        isset($dadosFormData['tipo_grade']) && in_array($dadosFormData['tipo_grade'], [1, 3]),
                        [Validador::OBRIGATORIO, Validador::ENUM('CAIXA', 'SACOLA')]
                    ),
                ],
                'forma' => [
                    Validador::SE(in_array($dadosFormData['tipo_grade'], [1, 3]), [
                        Validador::OBRIGATORIO,
                        Validador::ENUM('PEQUENA', 'NORMAL', 'GRANDE'),
                    ]),
                ],
            ]);
            if ($dadosFormData['valor_custo_produto'] < 0.5) {
                throw new InvalidArgumentException('O valor de custo do produto não pode ser menor que R$ 0,50');
            }

            $dadosFormData['array_id_categoria'] = json_decode($dadosFormData['array_id_categoria'], true);
            $dadosFormData['listaFotosRemover'] = json_decode($dadosFormData['listaFotosRemover'], true);
            $dadosFormData['grades'] = json_decode($dadosFormData['grades'], true);
            $dadosFormData['cores'] = json_decode($dadosFormData['cores'], true);
            $dadosFormData['especial'] = json_decode($dadosFormData['especial'], true);
            $dadosFormData['bloqueado'] = json_decode($dadosFormData['bloqueado'], true);
            $dadosFormData['fora_de_linha'] = json_decode($dadosFormData['fora_de_linha'], true);
            $dadosFormData['permitido_repor'] = json_decode($dadosFormData['permitido_repor'], true);
            $dadosFormData['cores'] = preg_replace('/ /', '_', $dadosFormData['cores']);
            if ($dadosFormData['tipo_grade'] == 3) {
                $dadosFormData['grades'] = (array) array_map(function ($grade) {
                    $pattern = '/[^0-9]+/';
                    if (preg_match_all($pattern, $grade['nome_tamanho']) !== 1) {
                        throw new ConflictHttpException('A grade foi cadastrada de forma errada');
                    }
                    $grade['nome_tamanho'] = preg_replace($pattern, '/', trim($grade['nome_tamanho']));
                    return $grade;
                }, $dadosFormData['grades']);
            }

            $nomeComercialTratado = trim(preg_replace('/\s+/', ' ', $dadosFormData['nome_comercial']));

            $produtoSalvar = new Produto(
                $dadosFormData['descricao'],
                $usuario->id,
                $dadosFormData['id_fornecedor'],
                $dadosFormData['id_linha'],
                $dadosFormData['valor_custo_produto'],
                $dadosFormData['grade_min'],
                $dadosFormData['grade_max'],
                $nomeComercialTratado,
                $dadosFormData['tipo_grade'],
                $dadosFormData['id'] ?? 0
            );
            $produtoSalvar->setSexo($dadosFormData['sexo']);
            $produtoSalvar->setCores($dadosFormData['cores']);
            $produtoSalvar->setEspecial($dadosFormData['especial']);
            $produtoSalvar->setBloqueado($dadosFormData['bloqueado']);
            $produtoSalvar->setForma($dadosFormData['forma']);
            $produtoSalvar->setForaDeLinha($dadosFormData['fora_de_linha']);
            $produtoSalvar->setPermissaoReposicao($dadosFormData['permitido_repor'] ? 1 : 0);
            if (!empty($dadosFormData['embalagem'])) {
                $produtoSalvar->setEmbalagem($dadosFormData['embalagem']);
            }
            if (!empty($dadosFormData['outras_informacoes'])) {
                $produtoSalvar->setOutrasInformacoes($dadosFormData['outras_informacoes']);
            }
            $dadosFormData['array_id_categoria'] = array_slice($dadosFormData['array_id_categoria'], 0, 2);
            $dadosFormData['array_id_categoria'] = array_filter($dadosFormData['array_id_categoria']);
            Validador::validar($dadosFormData, [
                'array_id_categoria' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(2)],
            ]);

            ProdutosRepository::salvaProduto($conexao, $produtoSalvar);
            EstoqueRepository::insereGrade(
                $dadosFormData['grades'],
                $produtoSalvar->getId(),
                $produtoSalvar->getIdFornecedor()
            );
            if ($produtoSalvar->getForaDeLinha()) {
                EstoqueRepository::foraDeLinhaZeraEstoque($conexao, $produtoSalvar->getId());
            }
            ProdutosCategoriasRepository::removeCategoriasProduto($conexao, $produtoSalvar->getId());

            foreach ($dadosFormData['array_id_categoria'] as $idCategoria) {
                $produtoCategoria = new ProdutosCategorias($produtoSalvar->getId(), $idCategoria);
                ProdutosCategoriasRepository::salva($conexao, $produtoCategoria);
            }

            if (isset($_FILES['listaFotosCalcadasAdd']) || isset($_FILES['listaFotosCatalogoAdd'])) {
                $fotosAdd = [
                    'fotos_calcadas' => $_FILES['listaFotosCalcadasAdd'] ?? [],
                    'fotos' => $_FILES['listaFotosCatalogoAdd'] ?? [],
                ];
                ProdutosRepository::insereFotos(
                    $conexao,
                    $fotosAdd,
                    $produtoSalvar->getId(),
                    $produtoSalvar->getDescricao(),
                    $usuario->id
                );
            }
            if ($dadosFormData['listaFotosRemover']) {
                ProdutosRepository::removeFotos(
                    $conexao,
                    $dadosFormData['listaFotosRemover'],
                    $produtoSalvar->getId(),
                    $usuario->id
                );
            }

            $conexao->commit();
        } catch (Throwable $e) {
            $conexao->rollBack();
            throw $e;
        }
    }

    public function tirarProdutoDeLinha(array $dadosJson)
    {
        try {
            $this->conexao->beginTransaction();
            // Validação do ID do produto
            Validador::validar($dadosJson, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            EstoqueRepository::foraDeLinhaZeraEstoque($this->conexao, $dadosJson['id_produto']);
            ProdutosRepository::tirarDeLinha($this->conexao, $dadosJson['id_produto']);

            $this->conexao->commit();
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'O produto foi tirado de linha com sucesso!';
            $this->status = 200;
        } catch (Throwable $exception) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function buscaProdutosFornecedor(PDO $conexao, Request $request, int $idFornecedor)
    {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'items_por_pagina' => [
                Validador::SE(
                    isset($dadosJson['items_por_pagina']) && is_numeric($dadosJson['items_por_pagina']),
                    [Validador::OBRIGATORIO, Validador::NUMERO],
                    [Validador::ENUM('Tudo')]
                ),
            ],
            'fora_de_linha' => [Validador::BOOLEANO],
            'pesquisa_literal' => [Validador::BOOLEANO],
            'pesquisa' => [Validador::NAO_NULO],
        ]);
        $dadosJson['fora_de_linha'] = $request->query->getBoolean('fora_de_linha');
        $dadosJson['pesquisa_literal'] = $request->query->getBoolean('pesquisa_literal');

        $produtos = ProdutosRepository::buscaProdutosFornecedor(
            $conexao,
            $idFornecedor,
            $dadosJson['pagina'],
            $dadosJson['pesquisa'] ?: '',
            $dadosJson['pesquisa_literal'] ?: false,
            $dadosJson['items_por_pagina'] === 'Tudo' ? PHP_INT_MAX : $dadosJson['items_por_pagina'],
            $dadosJson['fora_de_linha']
        );

        return $produtos;
    }

    public function maisVendidos()
    {
        try {
            $mes = (int) $this->request->get('mes');
            $ano = (int) $this->request->get('ano');
            Validador::validar(
                ['mes' => $mes, 'ano' => $ano],
                [
                    'mes' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'ano' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]
            );
            $this->retorno['data']['vendas'] = ProdutosRepository::buscaQuantidadeVendas($this->conexao, $mes, $ano);
            $this->retorno['data']['lista_mais_vendidos'] = ProdutosRepository::buscaProdutosRankingVendas(
                $this->conexao,
                $mes,
                $ano
            );
        } catch (Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function remove(int $idProduto)
    {
        DB::beginTransaction();
        ProdutosRepository::verificaProdutoExisteRegistroNoSistema($idProduto);

        $produto = new ProdutoModel();
        $produto->exists = true;
        $produto->id = $idProduto;
        $produto->delete();
        DB::commit();
    }

    public function buscaProdutosEstoqueInternoFornecedor()
    {
        try {
            $dadosJson = [
                'pesquisa' => $this->request->get('pesquisa', ''),
                'pagina' => $this->request->get('pagina', 0),
            ];

            $this->retorno['data'] = ProdutosRepository::buscaProdutosEstoqueInternoFornecedor(
                $this->conexao,
                $this->idCliente,
                $dadosJson['pagina'],
                $dadosJson['pesquisa']
            );

            $this->retorno['message'] = 'Produtos buscados com sucesso!';
        } catch (Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function movimentacaoManualProduto(PDO $conexao, Request $request, Authenticatable $usuario, Gate $gate)
    {
        try {
            $conexao->beginTransaction();

            $dadosJson = $request->all();
            Validador::validar($dadosJson, [
                'tipo' => [Validador::OBRIGATORIO, Validador::ENUM('E', 'X')],
                'grades' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $movimentacoesServices = new MovimentacoesService($conexao);
            $idMov = (int) $movimentacoesServices->insereHistoricoDeMovimentacaoEstoque(
                $usuario->id,
                'Correção Estoque',
                $dadosJson['tipo']
            );

            $notificacoesReposicaoFilaEspera = [];
            foreach ($dadosJson['grades'] as $grade) {
                Validador::validar($grade, [
                    'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'tamanho' => [Validador::OBRIGATORIO],
                    'qtd_movimentado' => [Validador::NUMERO],
                ]);

                $idProduto = (int) $grade['id_produto'];

                if ($grade['qtd_movimentado'] > 0) {
                    $estoque = new EstoqueGradeService();
                    $estoque->nome_tamanho = (string) $grade['tamanho'];
                    $estoque->id_produto = (int) $grade['id_produto'];
                    $estoque->id_responsavel = (int) $this->nivelAcesso == 30 ? $this->idCliente : 1;
                    $estoque->tipo_movimentacao = $dadosJson['tipo'];

                    if ($dadosJson['tipo'] === 'E') {
                        $estoque->descricao = (string) "Usuario $this->nome adicionou par no estoque";
                        $estoque->alteracao_estoque = (string) $grade['qtd_movimentado'];

                        if (
                            $movimentacoesServices->ehPrimeiraEntradaEstoque(
                                $conexao,
                                $grade['id_produto'],
                                $estoque->id_responsavel
                            )
                        ) {
                            ProdutosRepository::atualizaDataEntrada($conexao, $grade['id_produto']);
                        }
                        $notificacoesReposicaoFilaEspera[] = $grade;
                    } else {
                        $estoque->descricao = "Usuario $this->nome removeu par no estoque";
                        $estoque->alteracao_estoque = (string) '-' . $grade['qtd_movimentado'];
                    }
                    $estoque->movimentaEstoque($conexao, $usuario->id);
                    $movimentacoesServices->insereHistoricoDeMovimentacaoItemEstoque(
                        $conexao,
                        $idMov,
                        $grade['id_produto'],
                        $grade['tamanho'],
                        1,
                        $usuario->id_colaborador,
                        $grade['qtd_movimentado']
                    );
                }
            }

            if (!$gate->allows('FORNECEDOR')) {
                EstoqueService::verificaRemoveLocalizacao($conexao, $idProduto);
            }

            $dadosColaboradoresNotificacaoReposicao = EstoqueService::BuscaClientesComProdutosNaFilaDeEspera(
                $conexao,
                $notificacoesReposicaoFilaEspera
            );

            try {
                $messageService = new MessageService();
                foreach ($dadosColaboradoresNotificacaoReposicao as $colaborador) {
                    $messageService->sendImageWhatsApp(
                        $colaborador['telefone'],
                        $colaborador['foto'],
                        $colaborador['mensagem']
                    );
                    NotificacaoRepository::enviar(
                        [
                            'colaboradores' => [$colaborador['id']],
                            'mensagem' =>
                                'Produto que estava na sua fila de espera chegou! <a href="/carrinho">Ver carrinho</a>',
                            'tipoMensagem' => 'C',
                            'titulo' => 'Reposição!',
                            'destino' => 'ML',
                            'imagem' => $colaborador['foto'],
                        ],
                        ''
                    );
                }
            } catch (Throwable $exception) {
                NotificacaoRepository::enviarSemValidacaoDeErro(
                    [
                        'colaboradores' => [1],
                        'mensagem' => 'Erro ao enviar notificação reposição whatsapp: ' . $exception->getMessage(),
                        'tipoMensagem' => 'Z',
                        'titulo' => 'Erro notificação reposição',
                        'imagem' => '',
                    ],
                    $conexao
                );
            }

            $conexao->commit();
        } catch (Throwable $exception) {
            $conexao->rollBack();
            throw $exception;
        }
    }
    public function buscaEtiquetaAvulsa(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            $produto = ProdutosRepository::buscaEtiquetasProduto($this->conexao, $dados['id']);
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Etiqueta encontrada';
            $this->retorno['data'] = $produto;
            $this->codigoRetorno = 200;
        } catch (Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaProdutos(Origem $origem)
    {
        $dadosJson = FacadesRequest::all();

        Validador::validar($dadosJson, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome_tamanho' => [Validador::NAO_NULO],
        ]);

        ProdutoModel::verificaExistenciaProduto($dadosJson['id_produto'], $dadosJson['nome_tamanho']);

        $retorno['referencias'] = ProdutoService::buscaDetalhesProduto($dadosJson['id_produto']);

        $retorno['reposicoes'] = Reposicao::buscaReposicoesDoProduto($dadosJson['id_produto'], !$origem->ehAdm());

        if ($origem->ehAdm()) {
            $retorno['transacoes'] = ProdutoService::buscaTransacoesProduto(
                $dadosJson['id_produto'],
                $dadosJson['nome_tamanho']
            );
            $retorno['trocas'] = ProdutoService::buscaTrocasDoProduto(
                $dadosJson['id_produto'],
                $dadosJson['nome_tamanho']
            );
            $retorno['referencias'] = array_merge(
                $retorno['referencias'],
                ProdutoModel::logsMovimentacoesLocalizacoes($dadosJson['id_produto'], $dadosJson['nome_tamanho'])
            );
        }

        return $retorno;
    }

    public function buscaLogsMovimentacoesLocalizacoes()
    {
        $dados = FacadesRequest::all();
        Validador::validar($dados, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome_tamanho' => [Validador::OBRIGATORIO],
        ]);

        $logs = ProdutoModel::logsMovimentacoesLocalizacoes($dados['id_produto'], $dados['nome_tamanho']);

        return $logs;
    }

    public function buscaDevolucoes()
    {
        $dados = FacadesRequest::all();
        Validador::validar($dados, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'nome_tamanho' => [Validador::NAO_NULO],
        ]);

        $retorno = ProdutoModel::buscaDevolucoesAguardandoEntrada($dados['id_produto'], $dados['nome_tamanho']);

        return $retorno;
    }

    public function listaDadosPraCadastro()
    {
        try {
            $this->retorno['data']['linhas'] = ProdutoService::listaLinhas($this->conexao);
            $this->retorno['data']['tipos_grade'] = ProdutoService::listaTiposGrade($this->conexao);
            $this->retorno['data']['categorias_tipos'] = ProdutoService::listaCategorias($this->conexao);
            $this->retorno['data']['cores'] = ProdutoService::listaCores($this->conexao);
            $this->retorno['data']['porcentagens'] = ConfiguracaoService::porcentagencComissoesProdutos($this->conexao);
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Parâmetros encontrados com sucesso!';
        } catch (Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function buscaProdutosPromovidos()
    {
        $retorno = ProdutosRepository::buscaProdutosPromocao();
        return $retorno;
    }
    public function buscaProdutosDisponiveisPromocao()
    {
        $retorno = ProdutosRepository::buscaProdutosPromocaoDisponiveis();
        return $retorno;
    }

    public function buscaDetalhesPraConferenciaEstoque(array $dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ProdutosRepository::buscaDetalhesProduto($this->conexao, $dadosJson['id_produto']);
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Produtos encontrados com sucesso!';
        } catch (Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function buscaSaldoProdutosFornecedor(int $pagina)
    {
        $retorno = ProdutosRepository::buscaSaldoProdutosFornecedor($pagina);
        return $retorno;
    }

    public function buscarGradesDeUmProduto(array $dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = EstoqueService::consultaEstoqueGradeProduto(
                $this->conexao,
                $dadosJson['id_produto']
            );
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Grades encontradas com sucesso!';
        } catch (Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
    public function buscaAvaliacoesProduto(PDO $conexao, int $idProduto)
    {
        $produtosRepository = new ProdutosRepository();
        $avaliacoes = $produtosRepository->buscaAvaliacaoProduto($conexao, $idProduto);
        return $avaliacoes;
    }
    public function salvaPromocao()
    {
        $produtos = FacadesRequest::all();
        Validador::validar(['produtos' => $produtos], ['produtos' => [Validador::ARRAY]]);
        foreach ($produtos as $produto) {
            Validador::validar($produto, [
                'promocao' => [Validador::NAO_NULO, Validador::NUMERO],
                'id' => [Validador::NAO_NULO, Validador::NUMERO],
            ]);

            if ($produto['promocao'] === 100) {
                throw new BadRequestHttpException('Uma promoção de 100% não é permitida.');
            }

            DB::beginTransaction();
            $produtoModel = new ProdutoModel();
            $produtoModel->exists = true;
            $produtoModel->id = $produto['id'];
            $produtoModel->preco_promocao = $produto['promocao'];
            $produtoModel->save();
            DB::commit();
        }
    }
    public function pesquisaProdutoLista()
    {
        $filtros = FacadesRequest::all();

        Validador::validar($filtros, [
            'codigo' => [Validador::NAO_NULO],
            'eh_moda' => [Validador::SE(Validador::NAO_NULO, [Validador::BOOLEANO])],
            'descricao' => [Validador::NAO_NULO],
            'categoria' => [Validador::NAO_NULO],
            'fornecedor' => [Validador::NAO_NULO],
            'nao_avaliado' => [Validador::NAO_NULO, Validador::BOOLEANO],
            'bloqueados' => [Validador::NAO_NULO, Validador::BOOLEANO],
            'sem_foto_pub' => [Validador::NAO_NULO, Validador::BOOLEANO],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'fotos' => [Validador::NAO_NULO],
        ]);

        if (isset($filtros['eh_moda'])) {
            $filtros['eh_moda'] = FacadesRequest::boolean('eh_moda');
        }
        $filtros['nao_avaliado'] = json_decode($filtros['nao_avaliado']);
        $filtros['bloqueados'] = json_decode($filtros['bloqueados']);
        $filtros['sem_foto_pub'] = json_decode($filtros['sem_foto_pub']);
        $filtros['pagina'] = json_decode($filtros['pagina']);

        $retorno = ProdutosRepository::filtraProdutosPagina($filtros['pagina'], $filtros);
        return $retorno;
    }

    public function buscaListaPontuacoesProdutos()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [Validador::NAO_NULO],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'listar_todos' => [Validador::BOOLEANO],
        ]);

        $dadosJson['listar_todos'] = FacadesRequest::boolean('listar_todos');
        $produtos = ProdutoService::buscaListaPontuacoes(
            $dadosJson['pesquisa'],
            $dadosJson['pagina'],
            $dadosJson['listar_todos']
        );

        return $produtos;
    }

    public function buscaProdutosMaisVendidos()
    {
        try {
            $dadosJson = [
                'data_inicial' => $this->request->get('data_inicial', ''),
                'pagina' => $this->request->get('pagina', 1),
            ];

            $validadores = ['pagina' => [Validador::OBRIGATORIO, Validador::NUMERO]];
            if ($dadosJson['data_inicial'] !== '') {
                $validadores = array_merge($validadores, ['data_inicial' => [Validador::OBRIGATORIO, Validador::DATA]]);
            }
            Validador::validar($dadosJson, $validadores);

            $this->retorno['data'] = ProdutoService::listaDeProdutosMaisVendidos(
                $this->conexao,
                $dadosJson['pagina'],
                $dadosJson['data_inicial']
            );
            $this->retorno['message'] = 'Produtos encontrados com sucesso';
        } catch (Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = [];
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaProdutosSemEntrega()
    {
        try {
            $this->resposta = ProdutoService::listaDeProdutosSemEntrega($this->conexao);
        } catch (Throwable $th) {
            $this->codigoRetorno = Response::HTTP_BAD_REQUEST;
            $this->resposta['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function permissaoReporFulfillment()
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
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'autorizado' => [Validador::BOOLEANO],
            ]);

            ProdutosRepository::atualizaPermissaoReporFulfillment(
                $this->conexao,
                $dadosJson['id_produto'],
                $dadosJson['autorizado']
            );

            $this->conexao->commit();
            $this->retorno['message'] = 'Autorização atualizada com sucesso!';
        } catch (Throwable $th) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = [];
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaPromocoesAnalise(Request $request)
    {
        $pesquisa = $request->input('pesquisa');
        $retorno = ProdutosRepository::buscaPromocoesAnalise($this->conexao, $pesquisa);
        return $retorno;
    }
    public function buscaProdutosPedido()
    {
        $dados = FacadesRequest::all();
        Validador::validar($dados, [
            'tipo_pedido' => [Validador::ENUM('ENTREGA', 'PONTO_ENTREGADOR', 'RETIRADA_TRANSPORTADORA')],
            'identificador' => [Validador::OBRIGATORIO],
        ]);
        if ($dados['tipo_pedido'] === 'ENTREGA') {
            $produtos = LogisticaItemService::listaLogisticaPendenteParaEnvio($dados['identificador']);
        } else {
            $produtos = LogisticaItemService::listaProdutosPedido(
                $dados['tipo_pedido'] === 'RETIRADA_TRANSPORTADORA',
                $dados['identificador']
            );
        }

        return $produtos;
    }
    public function buscaPrevisao(
        PDO $conexao,
        Request $request,
        PontosColetaAgendaAcompanhamentoService $agenda,
        PrevisaoService $previsao
    ) {
        $dadosJson = $request->all();
        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $produto = ProdutoService::buscaInformacoesProduto($conexao, $dadosJson['id_produto']);
        $mediasEnvio = $previsao->calculoDiasSeparacaoProduto($dadosJson['id_produto']);
        if ($mediasEnvio['FULFILLMENT'] === null) {
            $mediasEnvio['FULFILLMENT'] = 0;
        }
        if ($mediasEnvio['EXTERNO'] === null) {
            $fatores = ConfiguracaoService::buscaFatoresReputacaoFornecedores(['dias_mensurar_cancelamento']);
            $mediasEnvio['EXTERNO'] = $fatores['dias_mensurar_cancelamento'];
        }
        $produto = array_merge($produto, $mediasEnvio);
        $fornecedor = ColaboradoresService::buscaInformacoesFornecedor($conexao, $produto['id_fornecedor']);
        $tipoFrete = TipoFreteService::buscaInformacoesDoTransportador($dadosJson['id_colaborador']);
        $agenda->id_colaborador = $tipoFrete['ponto_coleta']['id_colaborador'];
        $pontoColeta = $agenda->buscaPrazosPorPontoColeta();

        $retorno = [
            'produto' => $produto,
            'fornecedor' => [
                'nome' => "({$fornecedor['id_fornecedor']}) {$fornecedor['razao_social']}",
                'telefone' => $fornecedor['telefone'],
                'whatsapp' => Globals::geraQRCODE("https://api.whatsapp.com/send/?phone=55{$fornecedor['telefone']}"),
                'foto' => $fornecedor['foto'],
                'reputacao' => $fornecedor['reputacao'],
            ],
            'transportador' => [
                'nome' => "({$tipoFrete['transportador']['id_colaborador']}) {$tipoFrete['transportador']['razao_social']}",
                'tipo_ponto' => $tipoFrete['transportador']['tipo_ponto'],
                'telefone' => $tipoFrete['transportador']['telefone'],
                'whatsapp' => Globals::geraQRCODE(
                    "https://api.whatsapp.com/send/?phone=55{$tipoFrete['transportador']['telefone']}"
                ),
                'foto' => $tipoFrete['transportador']['foto'],
                'cidades' => $tipoFrete['transportador']['cidades'],
            ],
            'ponto_coleta' => [
                'nome' => "({$tipoFrete['ponto_coleta']['id_colaborador']}) {$tipoFrete['ponto_coleta']['razao_social']}",
                'telefone' => $tipoFrete['ponto_coleta']['telefone'],
                'whatsapp' => Globals::geraQRCODE(
                    "https://api.whatsapp.com/send/?phone=55{$tipoFrete['ponto_coleta']['telefone']}"
                ),
                'foto' => $tipoFrete['ponto_coleta']['foto'],
                'horarios' => $pontoColeta['agenda'],
                'dias_pedido_chegar' => $pontoColeta['dias_pedido_chegar'],
            ],
        ];

        return $retorno;
    }
    public function buscaCatalogosPersonalizados(PDO $conexao)
    {
        $catalogos = CatalogoPersonalizadoService::buscarTodosCatalogos($conexao);
        return $catalogos;
    }
    public function ativarDesativarCatalogoPersonalizado(int $idCatalogo)
    {
        $catalogo = CatalogoPersonalizadoModel::consultaCatalogoPersonalizadoPorId($idCatalogo);
        $catalogo->ativo = !$catalogo->ativo;
        $catalogo->update();
    }
    public function buscaInformacoesProdutoNegociado(PDO $conexao, string $uuidProduto)
    {
        $produto = ProdutoService::informacoesDoProdutoNegociado($conexao, $uuidProduto);
        return $produto;
    }
    public function desativaPromocaoMantemValores(PDO $conexao, int $idProduto, Authenticatable $usuario)
    {
        try {
            $conexao->beginTransaction();
            ProdutoService::desativaPromocaoMantemValores($conexao, $idProduto, $usuario->id);
            $conexao->commit();
        } catch (Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }

    public function buscaProdutosCancelados()
    {
        $produtos = LogisticaItemModel::buscaListaProdutosCancelados();

        return $produtos;
    }

    public function alterarPermissaoReporFulfillment(int $idProduto)
    {
        $produto = ProdutoModel::buscarProdutoPorId($idProduto);
        $produto->permitido_reposicao = !$produto->permitido_reposicao;
        $produto->save();
    }

    public function alterarEhModa(int $idProduto)
    {
        $produto = ProdutoModel::buscarProdutoPorId($idProduto);
        $produto->eh_moda = !$produto->eh_moda;
        $produto->save();
    }
}
