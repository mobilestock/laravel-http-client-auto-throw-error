<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\model\CatalogoPersonalizadoModel;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\Origem;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\CatalogoPersonalizadoService;
use MobileStock\service\Publicacao\PublicacoesService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Estoque\EstoqueGradeService;
use MobileStock\service\ProdutoService;
use PDO;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicacoesPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    // public function buscaPublicacaoCompleto(array $dados)
    // {
    //     try {
    //         Validador::validar($dados, [
    //             'id' => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         $this->retorno['data']['publicacao'] = PublicacoesService::consultaPublicacaoCompleto($this->conexao, $dados['id']);

    //         $this->retorno['message'] = 'Produtos buscados com sucesso!!';
    //         $this->status = 200;
    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();

    //         $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //     } catch (\Throwable $ex) {
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    //         $this->status = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function buscaProdutoPublicacao(int $idProduto)
    {
        $dados = PublicacoesService::consultaProdutoPublicacao($idProduto);
        if (empty($dados)) {
            throw new NotFoundHttpException('Produto não existe');
        }
        return $dados;
    }

    public function buscaDetalhesProdutoPublicacao(int $idProduto, Origem $origem)
    {
        $dadosJson = \Illuminate\Support\Facades\Request::all();
        Validador::validar($dadosJson, [
            'id_colaborador_ponto' => [Validador::SE(Validador::OBRIGATORIO, [Validador::NUMERO])],
            'origem' => [Validador::SE($origem->ehMed(), [Validador::OBRIGATORIO, Validador::ENUM('ML', 'MS')])],
        ]);

        $grade = PublicacoesService::consultaGradeProduto(
            $origem->ehMed() ? $dadosJson['origem'] : $origem,
            $idProduto,
            $dadosJson['id_colaborador_ponto'] ?? null
        );

        return $grade;
    }

    public function buscaPublicacoesInfluencer(string $usuarioMeuLook)
    {
        $dados = \Illuminate\Support\Facades\Request::all();
        Validador::validar($dados, [
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'filtro' => [Validador::ENUM('RECENTES', 'PRONTA_ENTREGA', 'MAIS_VENDIDOS')],
        ]);

        if ($dados['filtro'] === 'PRONTA_ENTREGA') {
            $retorno = PublicacoesService::consultaPublicacoesDeProntaEntrega($usuarioMeuLook, $dados['pagina']);
        } else {
            $retorno = ProdutosRepository::consultaProdutosFornecedorPerfil(
                $usuarioMeuLook,
                $dados['pagina'],
                $dados['filtro']
            );
        }
        return $retorno;
    }

    public function catalogoPublicacoes(Origem $origem)
    {
        $filtro = FacadesRequest::get('filtro', '');
        $pagina = FacadesRequest::get('pagina', 1);

        if ($origem->ehMed()) {
            $origem = FacadesRequest::get('origem');
        } else {
            $origem = (string) $origem;
        }

        Validador::validar(
            [
                'filtro' => $filtro,
                'pagina' => $pagina,
                'origem' => $origem,
            ],
            [
                'filtro' => [
                    Validador::SE(
                        !empty($filtro) && !is_numeric($filtro),
                        Validador::ENUM('MELHOR_FABRICANTE', 'MENOR_PRECO', 'PROMOCAO', 'LANCAMENTO')
                    ),
                ],
                'pagina' => [Validador::NUMERO],
                'origem' => [Validador::ENUM('ML', 'MS')],
            ]
        );

        $dataRetorno = [];
        if (is_numeric($filtro)) {
            if ($pagina == 1) {
                $catalogo = CatalogoPersonalizadoModel::find($filtro);
                $dataRetorno = CatalogoPersonalizadoService::buscarProdutosCatalogoPersonalizadoPorIds(
                    json_decode($catalogo->produtos),
                    'CATALOGO',
                    $origem
                );
            }
        } elseif ($filtro) {
            if ($filtro === 'PROMOCAO') {
                if ($pagina == 1) {
                    $dataRetorno = PublicacoesService::buscaPromocoesTemporarias($origem);
                    return $dataRetorno;
                } else {
                    $pagina -= 1;
                }
            }
            $chave = 'catalogo.' . mb_strtolower($origem) . '.' . mb_strtolower($filtro) . ".pagina_{$pagina}";
            $idColaborador = Auth::user()->id_colaborador ?? null;
            if (
                $origem === Origem::ML &&
                (!$idColaborador || !EntregasFaturamentoItem::clientePossuiCompraEntregue())
            ) {
                $chave .= '.cliente_novo';
            }
            $abstractAdapter = app(AbstractAdapter::class);
            $item = $abstractAdapter->getItem($chave);
            if ($item->isHit()) {
                $dataRetorno = $item->get();
            }

            if (!$dataRetorno) {
                $dataRetorno = PublicacoesService::buscarCatalogoComFiltro($pagina, $filtro, $origem);
                $item->set($dataRetorno);
                $item->expiresAfter(60 * 15); // 15 minutos
                $abstractAdapter->save($item);
            }
        } else {
            $pagina += 1;
            $dataRetorno = PublicacoesService::buscarCatalogo($pagina, $origem);
        }

        return $dataRetorno;
    }

    public function consultaStories()
    {
        try {
            $stories = PublicacoesService::consultaStories($this->conexao, $this->idCliente);
            for ($i = 0; $i < count($stories); $i++) {
                $stories[$i]['stories'] = json_decode($stories[$i]['stories'], true);
                for ($j = 0; $j < count($stories[$i]['stories']); $j++) {
                    $stories[$i]['stories'][$j]['cards'] = json_decode($stories[$i]['stories'][$j]['cards'], true);
                }
            }
            $this->retorno['data']['stories'] = $stories;
            $this->retorno['message'] = 'Stories buscados com sucesso';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();
        } catch (\Throwable $ex) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    // public function incrementarVisualizacao(Array $data)
    // {
    //     try {
    //         PublicacoesService::incrementarQuantidadeAcesso($this->conexao, $data['idPublicacao']);
    //     } catch (\PDOException $pdoException) {
    //         $this->codigoRetorno = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();
    //     } catch(\Throwable $ex) {
    //         $this->codigoRetorno = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //     }
    // }

    public function buscaPesquisasPopulares()
    {
        try {
            $query = $this->request->query->all();
            Validador::validar($query, ['origem' => [Validador::OBRIGATORIO, Validador::ENUM('MS', 'ML')]]);
            $cache = app(AbstractAdapter::class);

            $dataRetorno = [];

            $chave = 'pesquisas_populares.' . mb_strtolower($query['origem']);
            $item = $cache->getItem($chave);
            if ($item->isHit()) {
                $dataRetorno = $item->get();
            }

            if (!$dataRetorno) {
                $dataRetorno = PublicacoesService::buscaPesquisasPopulares($this->conexao, $query['origem']);

                $item->set($dataRetorno);
                $item->expiresAfter(3600 * 6); // 6 horas
                $cache->save($item);
            }

            $this->resposta = $dataRetorno;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->resposta['message'] = $ex->getMessage();
            $this->codigoRetorno = 500;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function filtrosCatalogo(PDO $conexao, Origem $origem, Request $request, AbstractAdapter $cache)
    {
        $siglaOrigem = (string) $origem;

        if ($origem->ehMed()) {
            $siglaOrigem = $request->query('origem');
        }

        if (!$origem->ehAdm()) {
            Validador::validar(['origem' => $siglaOrigem], ['origem' => [Validador::ENUM(Origem::MS, Origem::ML)]]);

            $item = $cache->getItem("filtros_catalogo.$origem.$siglaOrigem");
            if ($item->isHit()) {
                return $item->get();
            }
        }

        # BUSCANDO DADOS NECESSÁRIOS PARA OPERAÇÃO E OS ORGANIZANDO
        $configuracoes = ConfiguracaoService::buscarOrdenamentosFiltroCatalogo($conexao);
        $filtrosPesquisaPadrao = $configuracoes['filtros_pesquisa_padrao'];
        $filtrosPesquisaOrdenados = $configuracoes['filtros_pesquisa_ordenados'];

        $catalogosPersonalizadosPublicos = CatalogoPersonalizadoService::buscarListaCatalogosPublicos(
            $conexao,
            $origem->ehAdm() ? null : $origem
        );
        if (!$origem->ehAdm()) {
            $idsProdutosTotais = array_reduce(
                $catalogosPersonalizadosPublicos,
                function (array $idsProdutos, array $catalogo): array {
                    return array_merge($idsProdutos, $catalogo['produtos']);
                },
                []
            );
            $idsProdutosComEstoque = EstoqueGradeService::retornarItensComEstoque(
                $conexao,
                $idsProdutosTotais,
                $siglaOrigem
            );
            $catalogosPersonalizadosPublicos = array_filter($catalogosPersonalizadosPublicos, function (
                array $catalogo
            ) use ($idsProdutosComEstoque) {
                $idsProdutosCatalogoComEstoque = array_intersect($catalogo['produtos'], $idsProdutosComEstoque);
                return !empty($idsProdutosCatalogoComEstoque);
            });
        }

        # OPERAÇÕES PARA TRAZER OS FILTROS NA ORDEM CORRETA
        $filtrosTotais = array_merge($filtrosPesquisaPadrao, $catalogosPersonalizadosPublicos);

        $filtrosNaOrdem = [];
        foreach ($filtrosPesquisaOrdenados as $chaveOrdenamento) {
            $filtroEncontrado = array_filter($filtrosTotais, function (array $filtro) use ($chaveOrdenamento): bool {
                return $filtro['id'] === $chaveOrdenamento;
            });

            if (!empty($filtroEncontrado)) {
                $filtrosNaOrdem[] = current($filtroEncontrado);
            }
        }

        foreach ($filtrosTotais as $filtro) {
            $filtroTotalChave = $filtro['id'];

            $filtroOrdenamentoEncontrado = array_filter($filtrosNaOrdem, function (array $filtroNaOrdem) use (
                $filtroTotalChave
            ) {
                $filtroNaOrdemChave = $filtroNaOrdem['id'];
                return $filtroNaOrdemChave === $filtroTotalChave;
            });

            if (empty($filtroOrdenamentoEncontrado)) {
                $filtrosNaOrdem[] = $filtro;
            }
        }

        if (!$origem->ehAdm()) {
            $duracaoCache = ConfiguracaoService::buscarTempoExpiracaoCacheFiltro($conexao);
            $item->set($filtrosNaOrdem);
            $item->expiresAfter(60 * $duracaoCache);
            $cache->save($item);
        }

        return $filtrosNaOrdem;
    }

    public function gerarCatalogoPdf(PDO $conexao, Request $request)
    {
        $dadosJson = $request->all();
        $dadosJson['ids_produto'] = json_decode($dadosJson['ids_produto'], true);

        Validador::validar($dadosJson, [
            'ids_produto' => [
                Validador::OBRIGATORIO,
                Validador::ARRAY,
                Validador::TAMANHO_MINIMO(1),
                Validador::TAMANHO_MAXIMO(30),
            ],
        ]);

        $produtos = ProdutoService::buscarProdutosParaCatalogoPdf($conexao, $dadosJson['ids_produto']);
        $css = <<<CSS
        h1 {
            margin: 0;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #000;
            display: inline-block;
            color: #fff;
            text-align: center;
            font-family: sans-serif;
        }
        div.container {
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        div.blocoProduto {
            height: 145px;
            width: 145px;
            border: 1px solid #eee;
            float: left;
            margin: 0 10px;
            margin-bottom: 10px;
            background-color: #FFF;
        }
        img.imagemProduto {
            width: 100%;
            height: 100%;
        }
CSS;

        $produtosContainer = '';
        foreach ($produtos as $produto) {
            $produtosContainer .= <<<HTML
            <div class="blocoProduto">
                <img class="imagemProduto" src="{$produto['caminho']}">
            </div>
HTML;
        }

        $html = <<<HTML
        <html>
            <head>
                <link rel="stylesheet" href="styles.css">
            </head>
            <body>
                <h1>Catálogo de Produtos</h1>
                <div class='container'>
                    $produtosContainer
                </div>
            </body>
        </html>
HTML;

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => __DIR__ . '/../../../downloads',
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output('', \Mpdf\Output\Destination::INLINE);
    }

    public function catalogoInicial(Origem $origem)
    {
        if ($origem->ehMed()) {
            $origem = FacadesRequest::get('origem');
        }
        $produtos = PublicacoesService::buscarCatalogo(1, $origem);
        return $produtos;
    }
}
