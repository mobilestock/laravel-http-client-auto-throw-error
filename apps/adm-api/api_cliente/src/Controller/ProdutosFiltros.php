<?php

namespace api_cliente\Controller;

use api_cliente\Models\Conect;
use api_cliente\Models\Request_m;
use MobileStock\repository\CategoriasRepository;
use MobileStock\repository\TagsRepository;

class ProdutosFiltros extends Request_m
{
    public function __construct($rota)
    {
        $this->nivelAcesso = '0';
        parent::__construct();
        $this->conexao = Conect::conexao();
    }

    public function listaCategorias_lista()
    {
        try {
            $categorias = CategoriasRepository::CategoriasCadastradas();
            $this->retorno['data'] = $categorias;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
    public function listaCategorias()
    {
        try {
            $categorias = CategoriasRepository::buscaArvoreCategorias();
            $this->retorno['data'] = $categorias;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function listaLinhas()
    {
        try {
            $this->retorno['data'] = [
                [
                    'id' => '1',
                    'nome' => 'Adulto',
                    'icone_imagem' => '',
                    'tamanho_padrao_foto' => '36',
                    'numeros' => [
                        [
                            'tamanho' => '33',
                            'nome' => '33',
                        ],
                        [
                            'tamanho' => '34',
                            'nome' => '34',
                        ],
                        [
                            'tamanho' => '35',
                            'nome' => '35',
                        ],
                        [
                            'tamanho' => '36',
                            'nome' => '36',
                        ],
                        [
                            'tamanho' => '37',
                            'nome' => '37',
                        ],
                        [
                            'tamanho' => '38',
                            'nome' => '38',
                        ],
                        [
                            'tamanho' => '39',
                            'nome' => '39',
                        ],
                        [
                            'tamanho' => '40',
                            'nome' => '40',
                        ],
                        [
                            'tamanho' => '41',
                            'nome' => '41',
                        ],
                        [
                            'tamanho' => '42',
                            'nome' => '42',
                        ],
                        [
                            'tamanho' => '43',
                            'nome' => '43',
                        ],
                        [
                            'tamanho' => '44',
                            'nome' => '44',
                        ],
                        [
                            'tamanho' => '45',
                            'nome' => '45',
                        ],
                        [
                            'tamanho' => '46',
                            'nome' => '46',
                        ],
                        [
                            'tamanho' => '47',
                            'nome' => '47',
                        ],
                        [
                            'tamanho' => '48',
                            'nome' => '48',
                        ],
                        [
                            'tamanho' => '49',
                            'nome' => '49',
                        ],
                        [
                            'tamanho' => '50',
                            'nome' => '50',
                        ],
                        [
                            'tamanho' => 'PP',
                            'nome' => 'PP',
                        ],
                        [
                            'tamanho' => 'P',
                            'nome' => 'P',
                        ],
                        [
                            'tamanho' => 'M',
                            'nome' => 'M',
                        ],
                        [
                            'tamanho' => 'G',
                            'nome' => 'G',
                        ],
                        [
                            'tamanho' => 'GG',
                            'nome' => 'GG',
                        ],
                        [
                            'tamanho' => 'XG',
                            'nome' => 'XG',
                        ],
                    ],
                ],
                [
                    'id' => '2',
                    'nome' => 'Infantil',
                    'icone_imagem' => '',
                    'tamanho_padrao_foto' => '29',
                    'numeros' => [
                        [
                            'tamanho' => '16',
                            'nome' => '16',
                        ],
                        [
                            'tamanho' => '17',
                            'nome' => '17',
                        ],
                        [
                            'tamanho' => '18',
                            'nome' => '18',
                        ],
                        [
                            'tamanho' => '19',
                            'nome' => '19',
                        ],
                        [
                            'tamanho' => '20',
                            'nome' => '20',
                        ],
                        [
                            'tamanho' => '21',
                            'nome' => '21',
                        ],
                        [
                            'tamanho' => '22',
                            'nome' => '22',
                        ],
                        [
                            'tamanho' => '23',
                            'nome' => '23',
                        ],
                        [
                            'tamanho' => '24',
                            'nome' => '24',
                        ],
                        [
                            'tamanho' => '25',
                            'nome' => '25',
                        ],
                        [
                            'tamanho' => '26',
                            'nome' => '26',
                        ],
                        [
                            'tamanho' => '27',
                            'nome' => '27',
                        ],
                        [
                            'tamanho' => '28',
                            'nome' => '28',
                        ],
                        [
                            'tamanho' => '29',
                            'nome' => '29',
                        ],
                        [
                            'tamanho' => '30',
                            'nome' => '30',
                        ],
                        [
                            'tamanho' => '31',
                            'nome' => '31',
                        ],
                        [
                            'tamanho' => '32',
                            'nome' => '32',
                        ],
                        [
                            'tamanho' => '33',
                            'nome' => '33',
                        ],
                        [
                            'tamanho' => '34',
                            'nome' => '34',
                        ],
                        [
                            'tamanho' => '35',
                            'nome' => '35',
                        ],
                        [
                            'tamanho' => '36',
                            'nome' => '36',
                        ],
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
    /**
     * @deprecated
     * Criar filtros da pesquisa igual ao MeuLook
     */
    public function filtrosDeOrdenacao()
    {
        try {
            $menus = [
                [
                    'id' => 1,
                    'nome' => 'Mais Relevantes',
                    'valor' => 'MAIS_RELEVANTE',
                ],
                [
                    'id' => 2,
                    'nome' => 'Menor preço',
                    'valor' => 'MENOR_PRECO',
                ],
                [
                    'id' => 3,
                    'nome' => 'Maior preço',
                    'valor' => 'MAIOR_PRECO',
                ],
                // [
                //     "id" => 1,
                //     "nome" => "Lançamentos",
                //     "valor" => "lancamentos",
                //     'niveis_acessos' => ['logado', 'deslogado']
                // ],
                // [
                //     "id" => 2,
                //     "nome" => "Menor Preco",
                //     "valor" => "menorPreco",
                //     'niveis_acessos' => ['logado', 'deslogado']
                // ],
                // [
                //     "id" => 3,
                //     "nome" => "Promoções",
                //     "valor" => "promocao",
                //     'niveis_acessos' => ['logado', 'deslogado']
                // ],
                // [
                //     "id" => 5,
                //     "nome" => "Ultimos Produtos Comprados",
                //     "valor" => "ultimosProdutosComprados",
                //     'niveis_acessos' => ['logado']
                // ],
                // [
                //     "id" => 6,
                //     "nome" => "Melhor Avaliados",
                //     "valor" => "melhorAvaliados",
                //     'niveis_acessos' => ['logado']
                // ],
                // [
                //     "id" => 7,
                //     "nome" => "Fotos Calcadas",
                //     "valor" => "fotosCalcadas",
                //     'niveis_acessos' => ['logado', 'deslogado']
                // ]
            ];

            // $nivelAcesso = !!$this->idCliente ? 'logado' : 'deslogado';
            // $menus = array_filter($menus, function (array $menu) use ($nivelAcesso) {
            //     return in_array($nivelAcesso, $menu['niveis_acessos']);
            // });

            // $menuTemp = [];
            // foreach ($menus as $menu) {
            //     if (in_array($nivelAcesso, $menu['niveis_acessos'])) {
            //         array_push($menuTemp, $menu);
            //     }
            // }

            // $menus = $menuTemp;

            // $menus = array_map(function (array $menu) {
            //     unset($menu['niveis_acessos']);
            //     return $menu;
            // }, $menus);

            $this->retorno['data'] = $menus;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function filtrosDeOrdenacaoLogado()
    {
        $this->retorno['data'] = [
            [
                'id' => 1,
                'nome' => 'Lançamentos',
                'valor' => 'lancamentos',
            ],
            [
                'id' => 2,
                'nome' => 'Menor Preco',
                'valor' => 'menorPreco',
            ],
            [
                'id' => 3,
                'nome' => 'Promoções',
                'valor' => 'promocao',
            ],
            [
                'id' => 5,
                'nome' => 'Ultimos Produtos Comprados',
                'valor' => 'ultimosProdutosComprados',
            ],
            [
                'id' => 6,
                'nome' => 'Melhor Avaliados',
                'valor' => 'melhorAvaliados',
            ],
            [
                'id' => 7,
                'nome' => 'Fotos Calcadas',
                'valor' => 'fotosCalcadas',
            ],
        ];

        $this->respostaJson
            ->setData($this->retorno)
            ->setStatusCode($this->codigoRetorno)
            ->send();
    }

    public function listaCoresEMateriais()
    {
        try {
            $tags = TagsRepository::buscaTagsTipos($this->conexao);
            $this->retorno['data'] = $tags;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    public function listaSexos()
    {
        try {
            $categorias = [
                [
                    'nome' => 'Feminino',
                    'valor' => 'FE',
                ],
                [
                    'nome' => 'Masculino',
                    'valor' => 'MA',
                ],
            ];
            $this->retorno['data'] = $categorias;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }
}
