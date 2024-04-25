<?php

namespace MobileStock\service\OpenSearchService;

use DateTime;
use DateTimeInterface;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\HttpClient;
use MobileStock\service\ReputacaoFornecedoresService;
use Symfony\Component\HttpFoundation\Response;

class OpenSearchClient extends HttpClient
{
    const DIAS_MENSURAVEIS_AUTOCOMPLETE = 7;

    protected function antesRequisicao(): HttpClient
    {
        $this->url = $_ENV['OPENSEARCH']['ENDPOINT'] . $this->url;
        $this->headers[] = 'Authorization: ' . $_ENV['OPENSEARCH']['AUTH'];
        return $this;
    }

    public function pesquisa(
        string $pesquisa,
        string $ordenar,
        array $linhas,
        array $sexos,
        array $tamanhos,
        array $cores,
        array $categorias,
        array $reputacoes,
        array $fornecedores,
        string $estoque,
        string $tipo,
        int $pagina,
        string $tipoCliente,
        string $origem
    ): self {
        $this->url = $_ENV['OPENSEARCH']['INDEXES']['PESQUISA'] . '/_search';

        $size = 100;
        $offset = ($pagina - 1) * $size;

        $arrayOrdenamento = [];
        $obrigatorio = [];
        $opcional = [];

        $grade = 'grade_fullfillment';
        if ($origem === 'ML' && $estoque !== 'FULLFILLMENT') {
            $grade = 'grade_produto';
        }

        $chaveValor = 'valor_venda_ms';
        if ($origem === 'ML') {
            $chaveValor = 'valor_venda_ml';
        }

        if (is_numeric($pesquisa)) {
            $obrigatorio[] = ['term' => ['id_produto' => $pesquisa]];
        } else {
            if ($tipo === 'PESQUISA') {
                switch ($ordenar) {
                    case 'MAIS_RELEVANTE':
                        $arrayOrdenamento[] = ['pontuacao_produto' => ['order' => 'desc']];
                        break;
                    case 'MENOR_PRECO':
                        $arrayOrdenamento[] = [$chaveValor => ['order' => 'asc']];
                        break;
                    case 'MAIOR_PRECO':
                        $arrayOrdenamento[] = [$chaveValor => ['order' => 'desc']];
                        break;
                    case 'MELHOR_AVALIADO':
                        $arrayOrdenamento[] = ['5_estrelas' => ['order' => 'desc']];
                        $arrayOrdenamento[] = ['4_estrelas' => ['order' => 'desc']];
                        $arrayOrdenamento[] = ['3_estrelas' => ['order' => 'desc']];
                        $arrayOrdenamento[] = ['2_estrelas' => ['order' => 'desc']];
                        $arrayOrdenamento[] = ['1_estrelas' => ['order' => 'desc']];
                        break;
                    case 'MAIS_RECENTE':
                        $arrayOrdenamento[] = ['id_produto' => ['order' => 'desc']];
                        break;
                    case 'MAIS_ANTIGO':
                        $arrayOrdenamento[] = ['id_produto' => ['order' => 'asc']];
                        break;
                    default:
                        throw new \Exception('Ordenação inválida');
                }
            }

            if ($pesquisa) {
                $pesquisa = ConversorStrings::tratarTermoOpensearch($pesquisa);
                $arrayOrdenamento[] = ['_score' => ['order' => 'desc']];
                $palavras = array_values(array_unique(explode(' ', $pesquisa)));
                foreach ($palavras as $index => $palavra) {
                    switch ($tipo) {
                        case 'PESQUISA':
                            $fuzziness = (int) max(1, mb_strlen($palavra) / 5);
                            $obrigatorio[] = [
                                'fuzzy' => [
                                    'concatenado' => [
                                        'value' => $palavra,
                                        'fuzziness' => $fuzziness,
                                        'boost' => 0,
                                    ],
                                ],
                            ];
                            break;
                        case 'SUGESTAO':
                            $opcional[] = [
                                'terms' => [
                                    'concatenado' => [$palavra],
                                ],
                            ];
                            break;
                        default:
                            throw new \Exception('Tipo inválido');
                    }
                }
            }

            if (!empty($linhas)) {
                $obrigatorio[] = ['terms' => ['linha_produto' => $linhas]];
            }
            if (!empty($sexos)) {
                $obrigatorio[] = ['terms' => ['sexo_produto' => [...$sexos, 'UN']]];
            }
            if (!empty($tamanhos)) {
                $obrigatorio[] = ['match' => [$grade => ['query' => implode('|', $tamanhos), 'boost' => 0]]];
            } else {
                $obrigatorio[] = ['regexp' => [$grade => '.+']];
            }
            if (!empty($cores)) {
                $obrigatorio[] = ['match' => ['cor_produto' => ['query' => implode('|', $cores), 'boost' => 0]]];
            }
            if (!empty($categorias)) {
                $obrigatorio[] = [
                    'match' => ['categoria_produto' => ['query' => implode('|', $categorias), 'boost' => 0]],
                ];
            }
            if ($origem === 'ML') {
                if (!empty($reputacoes)) {
                    $obrigatorio[] = ['terms' => ['reputacao_fornecedor' => $reputacoes]];
                } else {
                    if ($tipoCliente === 'CLIENTE_NOVO') {
                        $obrigatorio[] = [
                            'terms' => [
                                'reputacao_fornecedor' => [
                                    ReputacaoFornecedoresService::REPUTACAO_MELHOR_FABRICANTE,
                                    ReputacaoFornecedoresService::REPUTACAO_EXCELENTE,
                                ],
                            ],
                        ];
                    }
                }
            }
            if ($tipoCliente === 'SELLER') {
                $opcional[] = ['terms' => ['nome_fornecedor' => [array_pop($fornecedores)]]];
            }
            if (!empty($fornecedores)) {
                $obrigatorio[] = ['terms' => ['nome_fornecedor' => $fornecedores]];
            }
        }

        $body = (object) [
            'from' => $offset,
            'size' => $size,
            'sort' => $arrayOrdenamento,
            'query' => [
                'bool' => [
                    'must' => $obrigatorio,
                    'should' => $opcional,
                ],
            ],
        ];
        // $json = json_encode($body);
        return $this->get($this->url, $body);
    }

    // public function pesquisaSemelhante(string $pesquisa, int $offset = 0, int $limit = 100)
    // {
    //     $this->url = $_ENV['OPENSEARCH']['INDEXES']['PESQUISA'] . '/_search';

    //     $body = (object) [
    //         "from" => $offset,
    //         "size" => $limit,
    //         "query" => (object) [
    //             "bool" => [
    //                 "should" => [
    //                     [
    //                         "match" => [
    //                             "concatenado" => $pesquisa
    //                         ]
    //                     ]
    //                 ]
    //             ]
    //         ]
    //     ];

    //     // $json = json_encode($body);

    //     return $this->get($this->url, $body);
    // }
    // public function pesquisaMobileStock(string $pesquisa, int $offset = 0, int $limit = 100):self
    // {
    //     $this->url = $_ENV['OPENSEARCH']['INDEXES']['PESQUISA'] . '/_search';
    //     $arrayPesquisa[] = [ "terms" => [ "concatenado" => explode(' ', $pesquisa) ] ];
    //     $formulaScore = "doc['pontuacao_produto'].size() != 0 ? doc['tem_estoque'].value + doc['pontuacao_produto'].value + _score : _score";
    //     $body = (object) [
    //         "from" => $offset,
    //         "size" => $limit,
    //         "query" => [
    //             "function_score" => [
    //                 "query" => [
    //                     "bool" => [
    //                         "must" => $arrayPesquisa
    //                     ]
    //                 ],
    //                 "boost_mode" => "replace",
    //                 "script_score" => [
    //                     "script" => [
    //                         "source" => $formulaScore
    //                     ]
    //                 ]
    //             ]
    //         ]
    //     ];
    //     //$json = json_encode($body);
    //     return $this->get($this->url, $body);
    // }

    public function autocompletePesquisa(string $pesquisa): self
    {
        $this->url = $_ENV['OPENSEARCH']['INDEXES']['AUTOCOMPLETE'] . '/_search';
        $body = (object) [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'range' => [
                                'data_criacao' => [
                                    'gte' => date(
                                        'Y-m-d',
                                        strtotime('-' . self::DIAS_MENSURAVEIS_AUTOCOMPLETE . ' days')
                                    ),
                                ],
                            ],
                        ],
                        [
                            'prefix' => [
                                'nome' => $pesquisa,
                            ],
                        ],
                    ],
                ],
            ],
            'aggs' => [
                'contagem' => [
                    'terms' => [
                        'field' => 'nome',
                        'size' => 10,
                        'min_doc_count' => 3,
                    ],
                ],
            ],
        ];
        // $json = json_encode($body);
        return $this->get($this->url, $body);
    }

    public function limparhistoricoPesquisaAutocomplete(): self
    {
        $this->url = $_ENV['OPENSEARCH']['INDEXES']['AUTOCOMPLETE'] . '/_delete_by_query';
        $body = (object) [
            'query' => [
                'range' => [
                    'data_criacao' => [
                        'lt' => date('Y-m-d', strtotime('-' . self::DIAS_MENSURAVEIS_AUTOCOMPLETE . ' days')),
                    ],
                ],
            ],
        ];
        return $this->post($this->url, $body);
    }

    public function buscaPesquisasPopulares(): self
    {
        $this->url = $_ENV['OPENSEARCH']['INDEXES']['AUTOCOMPLETE'] . '/_search';
        $body = (object) [
            'query' => [
                'range' => [
                    'data_criacao' => [
                        'gte' => date('Y-m-d', strtotime('-' . self::DIAS_MENSURAVEIS_AUTOCOMPLETE . ' days')),
                    ],
                ],
            ],
            'aggs' => [
                'contagem' => [
                    'terms' => [
                        'field' => 'nome',
                        'size' => 30,
                        'min_doc_count' => 3,
                    ],
                ],
            ],
        ];
        // $json = json_encode($body);
        return $this->get($this->url, $body);
    }

    public function insereLog(array $dados): self
    {
        $dateTime = (new DateTime())->format(DateTimeInterface::ATOM);
        $this->post(
            "{$_ENV['OPENSEARCH']['INDEXES']['LOGS']}/_doc",
            array_merge($dados, ['data_criacao' => $dateTime])
        );

        if ($this->codigoRetorno !== Response::HTTP_CREATED) {
            throw new \DomainException('Não foi possivel inserir os logs no OpenSearch');
        }

        return $this;
    }
}
