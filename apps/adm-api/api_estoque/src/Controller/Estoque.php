<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaProdutoEstoque;
use Error;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Images\Etiquetas\ImagemPainelEstoque;
use MobileStock\helper\Validador;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\MessageService;

class Estoque extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        $this->conexao = Conexao::criarConexao();
        parent::__construct();
    }

    public function limparAnalise(): void
    {
        try {
            $this->conexao->beginTransaction();
            EstoqueService::limparAnalise($this->conexao, $this->idUsuario);
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Analise limpa';
            $this->conexao->commit();
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
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

    public function analisarLocalizacao(array $dados)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($dados, [
                'id_localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            EstoqueService::limparAnalise($this->conexao, $this->idUsuario);

            $resultado = EstoqueService::buscaProdutosNaLocalizacao($this->conexao, $dados['id_localizacao']);

            if (empty($resultado)) {
                throw new Error('Não foi possível buscar essa localização.');
            }

            $analise_estoque = [];
            $grade = null;

            foreach ($resultado as $key => $value) {
                $grades = EstoqueService::buscaEstoqueGrade($this->conexao, $resultado[$key]['id']);

                foreach ($grades as $grade) {
                    $indice = "{$grade['id_produto']}-{$grade['nome_tamanho']}";
                    if (isset($produtosTamanho[$indice])) {
                        $produto = $produtosTamanho[$indice];
                        $quantidade = $produto['quantidade'] - $grade['estoque'];
                        for ($i = 0; $i < abs($quantidade); $i++) {
                            $analise_estoque[] = [
                                'id_produto' => (int) $produto['id_produto'],
                                'nome_tamanho' => $produto['nome_tamanho'],
                                'codigo_barras' => '',
                                'situacao' => $quantidade > 0 ? 'PS' : 'PF',
                            ];
                        }
                    } else {
                        if ($grade['estoque'] > 0) {
                            for ($i = 0; $i < $grade['estoque']; $i++) {
                                if (
                                    is_null($grade['localizacao']) ||
                                    $grade['localizacao'] != $dados['id_localizacao']
                                ) {
                                    $analise_estoque[] = [
                                        'id_produto' => (int) $grade['id_produto'],
                                        'nome_tamanho' => $grade['nome_tamanho'],
                                        'codigo_barras' => '',
                                        'situacao' => 'LE',
                                    ];
                                } else {
                                    $codBarras = EstoqueService::buscaCodigoDeBarras(
                                        $this->conexao,
                                        $grade['id_produto'],
                                        $grade['nome_tamanho']
                                    );
                                    $analise_estoque[] = [
                                        'id_produto' => (int) $grade['id_produto'],
                                        'nome_tamanho' => $grade['nome_tamanho'],
                                        'codigo_barras' => $codBarras,
                                        'situacao' => 'PF',
                                    ];
                                }
                            }
                        } elseif (isset($produtosTamanho[$indice])) {
                            $produto = $produtosTamanho[$indice];
                            for ($i = 0; $i == $produto['quantidade']; $i++) {
                                $analise_estoque[] = [
                                    'id_produto' => (int) $grade['id_produto'],
                                    'nome_tamanho' => $grade['nome_tamanho'],
                                    'codigo_barras' => '',
                                    'situacao' =>
                                        is_null($grade['localizacao']) ||
                                        $grade['localizacao'] != $dados['id_localizacao']
                                            ? 'LE'
                                            : 'PS',
                                ];
                            }
                        }
                    }
                }
            }

            if (empty($analise_estoque)) {
                throw new Error('Não há produtos para conferir nessa localização.');
            }

            $analise_estoque['id_localizacao'] = $dados['id_localizacao'];

            EstoqueService::adicionaProdutosNaAnalise($this->conexao, $analise_estoque, $this->idUsuario);

            $resultado = EstoqueService::buscaProdutosPorLocalizacaoEmAnalise(
                $this->conexao,
                $dados['id_localizacao'],
                $this->idUsuario
            );

            $this->retorno['data'] = $resultado;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Localização Encontrada. ' . count($resultado) . ' produtos';
            $this->conexao->commit();
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
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

    public function buscarLocalizacaoDoProduto(array $dados)
    {
        try {
            Validador::validar($dados, [
                'cod_barras' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $resultado = EstoqueService::buscarLocalizacaoDoProduto($this->conexao, $dados['cod_barras']);

            if (empty($resultado['estoque'])) {
                throw new Error('Esse produto está sem estoque no sistema. Por favor, faça o procedimento de entrada');
            }

            $this->retorno['data'] = $resultado;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Informações do Produto Encontradas';
        } catch (\Throwable $exception) {
            $this->retorno['erro'] = true;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaProdutoPorCodBarras(array $dados)
    {
        try {
            Validador::validar($dados, [
                'cod_barras' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $dadosProduto = EstoqueService::buscaProdutoPorCodBarras($this->conexao, $dados['cod_barras']);

            if (empty($dadosProduto)) {
                throw new Error('Não foi possível encontrar as informações deste produto');
            }

            $grade = EstoqueService::buscaEstoqueGrade($this->conexao, $dadosProduto['id_produto']);

            $resultado = [];

            foreach ($grade as $tamanho) {
                for ($i = 0; $i < $tamanho['estoque']; $i++) {
                    $resultado[] = [
                        'tamanho' => $tamanho['nome_tamanho'],
                        'id_produto' => (int) $tamanho['id_produto'],
                        'cod_barras' => (int) $tamanho['cod_barras'],
                        'estoque' => (int) $tamanho['estoque'],
                        'localizacao' => $tamanho['localizacao'] ? (int) $tamanho['localizacao'] : null,
                        'foto_produto' => $dadosProduto['foto_produto'],
                        'nome_produto' => $dadosProduto['descricao'],
                    ];
                }
            }

            $this->retorno['data'] = $resultado;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Produto Encontrado. ' . count($resultado) . ' em estoque';
        } catch (\Throwable $exception) {
            $this->retorno['status'] = true;
            $this->retorno['data'] = '';
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscarPainelLocalizacao(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id_painel' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            if (!EstoqueService::buscarPainelLocalizacao($this->conexao, $dados['id_painel'])) {
                throw new Error('Esse painel não existe no sistema');
            }

            $this->retorno['data'] = true;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'O painel ' . $dados['id_painel'] . ' foi encontrado';
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['data'] = '';
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function atualizaLocalizacaoProduto()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $json = json_decode($this->json, true);

            Validador::validar($json, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $quantidade = 0;
            $antigaLocalizacao = 0;

            $informacoesProduto = ProdutosRepository::buscaDetalhesProduto($this->conexao, $json['id_produto']);

            if (!empty($informacoesProduto['localizacao'])) {
                $antigaLocalizacao = $informacoesProduto['localizacao'];
            }

            $grades = EstoqueService::buscaEstoqueGrade($this->conexao, $json['id_produto']);

            foreach ($grades as $grade) {
                $quantidade += $grade['estoque'];
            }

            EstoqueService::atualizaLocalizacaoProduto(
                $this->conexao,
                $json['id_produto'],
                $antigaLocalizacao,
                $json['id_localizacao'],
                $this->idUsuario,
                $quantidade
            );

            $this->retorno['data'] = '';
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'O produto teve sua localização alterada';
            $this->conexao->commit();
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
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

    public function buscaDevolucoesAguardandoEntrada()
    {
        try {
            $dados['cod_barras'] = (int) $this->request->get('cod_barras');

            $resultado = EstoqueService::buscaDevolucoesAguardandoEntrada($this->conexao, $dados['cod_barras']);

            if (empty($resultado)) {
                throw new Error('Não foi possível buscar as devoluções');
            }

            $resultado = array_map(function ($item) {
                $item['id_devolucao'] = (int) $item['id_devolucao'];
                $item['id_produto'] = (int) $item['id_produto'];
                $item['localizacao'] = (int) $item['localizacao'];
                $item['cod_barras'] = (int) $item['cod_barras'];
                $item['data_hora'] = date_format(date_create($item['data_hora']), 'd/m/Y H:i:s');
                return $item;
            }, $resultado);

            $devolucoes = [];

            foreach ($resultado as $value) {
                $devolucoes[$value['localizacao']][] = $value;
            }

            $this->retorno['data'] = $devolucoes;
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Produtos encontrados com sucesso!';
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function devolucaoEntrada()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $json = json_decode($this->json, true);

            Validador::validar($json, [
                'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_devolucao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $resultado = EstoqueService::defineLocalizacaoProduto(
                $this->conexao,
                $json['id_produto'],
                $json['localizacao'],
                $this->idUsuario,
                $json['id_devolucao']
            );

            if (!empty($resultado)) {
                $produto[0] = [
                    'id_produto' => $resultado[0]['id_produto'],
                    'tamanho' => $resultado[0]['nome_tamanho'],
                    'qtd_movimentado' => 1,
                ];

                $aguardandoNotificacao = EstoqueService::BuscaClientesComProdutosNaFilaDeEspera(
                    $this->conexao,
                    $produto
                );

                $messageService = new MessageService();

                foreach ($aguardandoNotificacao as $colaborador) {
                    $messageService->sendImageWhatsApp(
                        $colaborador['telefone'],
                        $colaborador['foto'],
                        $colaborador['mensagem']
                    );
                }
            }

            $this->retorno['data'] = $resultado;
            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Troca guardada com sucesso!';
            $this->conexao->commit();
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage();
            $this->conexao->rollBack();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscarProdutoPorUuid(array $dados)
    {
        try {
            Validador::validar($dados, [
                'uuid_produto' => [Validador::OBRIGATORIO],
            ]);

            $produto = LogisticaItemService::buscaProdutoLogisticaPorUuid($this->conexao, $dados['uuid_produto']);

            $this->retorno['data'] = $produto;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Produto encontrado com sucesso!';
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
    public function imprimirEtiquetaPainel(int $idLocalizacao)
    {
        $painel = new ImagemPainelEstoque($idLocalizacao);
        $etiquetaGerada = $painel->criarZpl();
        return $etiquetaGerada;
    }

    public function imprimirEtiquetaProduto()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_produto' => [Validador::OBRIGATORIO],
            'nome_tamanho' => [Validador::OBRIGATORIO],
            'referencia' => [Validador::OBRIGATORIO],
            'cod_barras' => [Validador::OBRIGATORIO],
        ]);

        $etiqueta = new ImagemEtiquetaProdutoEstoque(
            $dados['id_produto'],
            $dados['nome_tamanho'],
            $dados['referencia'],
            $dados['cod_barras']
        );

        $etiquetaGerada = $etiqueta->criarZpl();
        return $etiquetaGerada;
    }

    public function imprimirEtiquetaLocalizacao()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $etiqueta = new ImagemPainelEstoque($dados['id_localizacao']);
        $etiquetaGerada = $etiqueta->criarZpl();
        return $etiquetaGerada;
    }
}
