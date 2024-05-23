<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\model\Origem;
use MobileStock\model\Pedido\PedidoItem;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\AvaliacaoProdutosService;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\IBGEService;
use MobileStock\service\LoggerService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\OpenSearchService\OpenSearchClient;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use MobileStock\service\Publicacao\PublicacoesService;
use MobileStock\service\TipoFreteService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProdutosPublic extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    public function filtroProdutos(Origem $origem)
    {
        $dadosRequest = FacadesRequest::input();
        $dados = [];
        $dados['origem'] = $origem->ehMed() ? $dadosRequest['origem'] : (string) $origem;
        $tratarValor = function ($chave, $valorAlternativo) use ($dadosRequest) {
            if (isset($dadosRequest[$chave]) && $dadosRequest[$chave] !== '') {
                return $dadosRequest[$chave];
            }
            return $valorAlternativo;
        };

        $pesquisa = mb_strtolower(trim($tratarValor('pesquisa', '')));
        $pagina = $tratarValor('pagina', 1);
        $dados += [
            'pesquisa' => $pesquisa,
            'ordenar' => $tratarValor('ordenar', 'MAIS_RELEVANTE'),
            'linhas' => array_filter(explode(',', $tratarValor('linhas', ''))),
            'sexos' => array_filter(explode(',', $tratarValor('sexos', ''))),
            'numeros' => array_filter(explode(',', $tratarValor('numeros', ''))),
            'cores' => array_filter(explode(',', $tratarValor('cores', ''))),
            'categorias' => array_filter(explode(',', $tratarValor('categorias', ''))),
            'reputacoes' => array_filter(explode(',', $tratarValor('reputacoes', ''))),
            'fornecedores' => array_filter(explode(',', $tratarValor('fornecedores', ''))),
            'estoque' => $tratarValor('estoque', 'TODOS'),
            'tipo' => $tratarValor('tipo', 'PESQUISA'),
            'pagina' => $pagina,
        ];

        Validador::validar($dados, [
            'pesquisa' => [],
            'ordenar' => [
                Validador::ENUM(
                    'MAIS_RELEVANTE',
                    'MENOR_PRECO',
                    'MAIOR_PRECO',
                    'MELHOR_AVALIADO',
                    'MAIS_RECENTE',
                    'MAIS_ANTIGO'
                ),
            ],
            'linhas' => [Validador::ARRAY],
            'sexos' => [Validador::ARRAY],
            'numeros' => [Validador::ARRAY],
            'cores' => [Validador::ARRAY],
            'categorias' => [Validador::ARRAY],
            'reputacoes' => [Validador::ARRAY],
            'fornecedores' => [Validador::ARRAY],
            'estoque' => [Validador::ENUM('FULFILLMENT', 'TODOS')],
            'tipo' => [Validador::ENUM('PESQUISA', 'SUGESTAO')],
            'pagina' => [Validador::NUMERO],
            'origem' => [Validador::ENUM('ML', 'MS')],
        ]);

        $produtos = ProdutosRepository::pesquisaProdutos(
            $pesquisa,
            $dados['ordenar'],
            $dados['linhas'],
            $dados['sexos'],
            $dados['numeros'],
            $dados['cores'],
            $dados['categorias'],
            $dados['reputacoes'],
            $dados['fornecedores'],
            $dados['estoque'],
            $dados['tipo'],
            $pagina,
            $dados['origem']
        );

        /**
         * Os utm_source's são padrões de mercado e vão nos ajudar a verificar no google analytics de onde vêm o trafego.
         * https://support.google.com/analytics/answer/1033863?hl=pt-BR#zippy=%2Cneste-artigo:~:text=de%20campanhas%20personalizadas-,Par%C3%A2metros,-Voc%C3%AA%20pode%20adicionar
         */
        if ($pesquisa && $pagina == 1 && isset($dadosGet['utm_source']) && $dadosGet['utm_source'] === 'ml_pesquisa') {
            LoggerService::criarLogPesquisa($pesquisa);
        }

        return $produtos;
    }

    // public function buscaCabecalhoPublicacoesProduto(array $dados)
    // {
    //     try {
    //         Validador::validar($dados, [
    //             'id' => [Validador::OBRIGATORIO]
    //         ]);

    //         ProdutosRepository::gravaAcessoProduto($dados['id'], 'ML');
    //         $this->retorno['data']['header'] = PublicacoesService::consultaCabecalhoPublicacoesProduto($this->conexao, $dados['id'], $this->idCliente);

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

    // public function buscaListaPublicacoesProduto(array $dados)
    // {
    //     try {
    //         $pagina = $this->request->get('pagina', 1);
    //         Validador::validar($dados, ['id' => [Validador::OBRIGATORIO]]);

    //         $this->retorno['data']['publicacoes'] = PublicacoesService::consultaLooksFeed($this->conexao, $this->idCliente, $pagina, $dados['id']);

    //         $this->retorno['message'] = 'Publicações buscadas com sucesso!!';
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

    public function buscaInfosProdutos()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            $this->retorno['data']['produtos'] = ProdutosRepository::buscaDetalhesStorieProduto(
                $this->conexao,
                $dadosJson['produtos']
            );
            $this->retorno['message'] = 'Produtos buscados com sucesso.';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    /**
     * @deprecated
     * Usar /api_meulook/publicacoes/catalogo
     */
    public function buscaListaProdutosInicio()
    {
        try {
            $dados = $this->request->query->all();
            $this->retorno['data'] = ProdutosRepository::produtosCatalogoApiMeuLook(
                $this->conexao,
                $dados['ordenacao'] ?? '',
                $this->idCliente,
                $dados['pagina'] ?? 1
            );

            $this->retorno['message'] = 'Produtos buscados com sucesso!';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
            $this->status = 500;
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    public function buscaFoguinho()
    {
        $dados = FacadesRequest::input();
        Validador::validar($dados, ['produtos' => [Validador::ARRAY]]);
        $gradesProdutos = ProdutosRepository::consultaFoguinho($dados['produtos']);
        return $gradesProdutos;
    }

    public function avaliacoesProduto(array $dados)
    {
        try {
            Validador::validar($dados, ['id_produto' => [Validador::NUMERO]]);
            $this->retorno['data']['avaliacoes'] = AvaliacaoProdutosService::buscaAvaliacoesProduto(
                $this->conexao,
                $dados['id_produto']
            );
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
            $this->status = 500;
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function buscaPrevisaoDeEntregaParaColaborador(int $idProduto, PrevisaoService $previsao)
    {
        $tipoFrete = $previsao->buscaTransportadorPadrao();
        if (empty($tipoFrete)) {
            $tipoFrete = ColaboradoresRepository::buscaTipoFretePadrao();
            if (!empty($tipoFrete) && $tipoFrete['tipo_ponto'] === 'ENVIO_TRANSPORTADORA') {
                throw new UnprocessableEntityHttpException('O colaborador possui transportadora como padrão.');
            }

            throw new NotFoundHttpException('Verifique se o colaborador possui um transportador padrão.');
        }

        $mediasEnvio = $previsao->calculoDiasSeparacaoProduto($idProduto);
        $diasProcessoEntrega = Arr::only($tipoFrete, [
            'dias_entregar_cliente',
            'dias_pedido_chegar',
            'dias_margem_erro',
        ]);
        $previsoes = $previsao->calculaPorMediasEDias($mediasEnvio, $diasProcessoEntrega, $tipoFrete['horarios']);
        $retorno = [
            'cidade' => [
                'uf' => $tipoFrete['uf'],
                'id' => $tipoFrete['id_cidade'],
                'nome' => $tipoFrete['nome'],
            ],
            'previsoes' => $previsoes,
        ];

        return $retorno;
    }
    public function buscaMetodosEnvio(
        PrevisaoService $previsao,
        PontosColetaAgendaAcompanhamentoService $agenda,
        ?int $idProduto = null
    ) {
        $idColaborador = Auth::user()->id_colaborador;

        $transportadores = TipoFreteService::buscaTransportadores();

        $qtdProdutos = PedidoItemMeuLookService::consultaQuantidadeProdutosNoCarrinhoMeuLook($idColaborador);

        // Ponto de Retirada e Entregador
        $pontosRetirada = array_filter(
            $transportadores,
            fn(array $transportador): bool => $transportador['tipo_ponto'] === 'PP'
        );
        $entregadores = array_filter(
            $transportadores,
            fn(array $transportador): bool => $transportador['tipo_ponto'] === 'PM'
        );
        $retorno = [
            'ponto_retirada' => ['disponivel' => !empty($pontosRetirada)],
            'entregador' => ['disponivel' => !empty($entregadores)],
            'transportadora' => [],
        ];

        if ($retorno['entregador']['disponivel']) {
            $entregador = reset($entregadores);
            $agenda->id_colaborador = $entregador['id_colaborador_ponto_coleta'];
            $pontoColeta = $agenda->buscaPrazosPorPontoColeta();
            $diasProcessoEntrega = [
                'dias_pedido_chegar' => $pontoColeta['dias_pedido_chegar'],
                'dias_entregar_cliente' => $entregador['dias_entregar_cliente'],
                'dias_margem_erro' => $entregador['dias_margem_erro'],
            ];
            $previsaoEntregador = null;
            if (empty($idProduto)) {
                $produtos = PedidoItemMeuLookService::consultaCarrinhoBasico();
                $previsoes = array_map(
                    fn(array $produto): array => $previsao->calculaPorMediasEDias(
                        $produto['medias_envio'],
                        $diasProcessoEntrega,
                        $pontoColeta['agenda']
                    ),
                    $produtos
                );
                $previsoes = array_merge(...$previsoes);
            } else {
                $mediasEnvio = $previsao->calculoDiasSeparacaoProduto($idProduto);
                $previsoes = $previsao->calculaPorMediasEDias(
                    $mediasEnvio,
                    $diasProcessoEntrega,
                    $pontoColeta['agenda']
                );
            }
            if (!empty($previsoes)) {
                usort($previsoes, fn(array $a, array $b): int => $a['dias_minimo'] <=> $b['dias_minimo']);
                $previsaoEntregador['minima'] = $previsoes[0]['media_previsao_inicial'] ?? null;
                usort($previsoes, fn(array $a, array $b): int => $b['dias_maximo'] <=> $a['dias_maximo']);
                $previsaoEntregador['maxima'] = $previsoes[0]['media_previsao_final'] ?? null;
            }

            $retorno['entregador'] = [
                'disponivel' => true,
                'id_tipo_frete' => (int) $entregador['id_tipo_frete'],
                'preco' => (float) $entregador['valor'],
                'previsao' => $previsaoEntregador,
                'qtd_itens_no_carrinho' => $qtdProdutos,
            ];
        }

        // Transportadora
        $itensNaoExpedidos = LogisticaItemService::buscaItensNaoExpedidosPorTransportadora();
        $qtdItensNaoExpedidos = count($itensNaoExpedidos);
        $transportadora = IBGEService::buscaIDTipoFretePadraoTransportadoraMeulook();
        $detalhes = null;
        if (!empty($transportadora)) {
            $detalhes = [
                'qtd_produtos_frete_padrao' => PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
                'preco_adicional_transportadora' => $transportadora['valor_adicional'] ?? null,
            ];
        }

        $retorno['transportadora'] = [
            'disponivel' => !empty($transportadora),
            'id_tipo_frete' => ((int) $transportadora['id_tipo_frete_transportadora_meulook']) ?? null,
            'adicionar_na_entrega' => count($itensNaoExpedidos) > 0,
            'preco' => $transportadora['valor_frete'] ?? null,
            'detalhes' => $detalhes,
            'qtd_itens_nao_expedidos' => $qtdItensNaoExpedidos,
            'qtd_itens_no_carrinho' => $qtdProdutos,
            'qtd_maxima_ate_adicional_frete' => PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE,
        ];

        // Retirar na central
        $pedidoRetireAquiEmAberto = EntregaServices::existePedidoRetireAquiEmAberto();
        $retorno['retire_aqui'] = [
            'disponivel' => $pedidoRetireAquiEmAberto,
        ];
        if ($retorno['retire_aqui']['disponivel']) {
            $retorno['transportadora']['disponivel'] = false;
        }

        return $retorno;
    }

    public function autocompletePesquisa()
    {
        try {
            $pesquisa = $this->request->get('pesquisa');
            Validador::validar(['pesquisa' => $pesquisa], ['pesquisa' => [Validador::OBRIGATORIO]]);

            $this->resposta = PublicacoesService::autocompletePesquisa($pesquisa);
            $this->codigoRetorno = 200;
        } catch (\PDOException $pdoException) {
            $this->resposta['message'] = $pdoException->getMessage();
            $this->codigoRetorno = 500;
        } catch (\Throwable $ex) {
            $this->resposta['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function criarRegistroPesquisaOpensearch(Request $request, LogManager $logger)
    {
        $dados = $request->all();
        Validador::validar($dados, ['pesquisa' => [Validador::OBRIGATORIO]]);
        $pesquisaTratada = ConversorStrings::tratarTermoOpensearch($dados['pesquisa']);

        # As palavras estão codificados em base64 para não deixá-las explicitas no código.
        $palavroes = [
            'YnVjZXRh',
            'Y2FyYWxobw==',
            'Y3U=',
            'Zm9kYQ==',
            'bWVyZGE=',
            'cHV0YQ==',
            'aGl0bGVy',
            'cGVuaXM=',
            'cG9ycmE=',
        ];

        foreach ($palavroes as $palavraoBase64) {
            $palavrao = base64_decode($palavraoBase64);
            $regex = "/\b$palavrao\b/";
            if (preg_match($regex, $pesquisaTratada)) {
                throw new UnprocessableEntityHttpException('Palavra proibida pesquisada!');
            }
        }

        $dados = [
            'id_colaborador' => $this->idCliente,
            'nome' => $pesquisaTratada,
            'data_criacao' => date('c'),
        ];

        $opensearchClient = new OpenSearchClient();
        $opensearchClient->post("{$_ENV['OPENSEARCH']['INDEXES']['AUTOCOMPLETE']}/_doc", $dados);

        if ($opensearchClient->codigoRetorno !== 201) {
            $logger->withContext([
                'opensearch_response' => $opensearchClient,
            ]);
            throw new Exception('Histórico de pesquisa não pôde ser criado no Opensearch');
        }
    }
}
