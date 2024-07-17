<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\Origem;
use MobileStock\model\PedidoItem;
use MobileStock\model\Produto;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\PedidoItem\TransacaoPedidoItem;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CriarTransacaoTest extends test\TestCase
{
    private const MOCK_ITENS = ['item_1', 'item_2', 'item_3'];

    public function setUp(): void
    {
        parent::setUp();
        Auth::setUser(new GenericUser(['id_colaborador' => 666]));
    }

    public function dadosVerificaSeClienteNaoPossuiDadosSuficientesParaCriarTransacao(): array
    {
        return [
            'Cliente não tem cidade' => ['Para finalizar um pedido é necessário ter uma cidade preenchida', false],
            'Cliente não tem método de envio padrão' => [
                'Para finalizar um pedido é necessário selecionar um ponto de entrega',
                false,
            ],
            'Cliente tem todas as informações necessárias' => ['correto', true],
        ];
    }

    /**
     * @dataProvider dadosVerificaSeClienteNaoPossuiDadosSuficientesParaCriarTransacao
     */
    public function testVerificaDadosClienteCriarTransacao(?string $mensagem, bool $deveDarCerto): void
    {
        if (!$deveDarCerto) {
            $this->expectExceptionMessage($mensagem);
        }

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOneColumn')->willReturn($mensagem);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        ColaboradoresService::verificaDadosClienteCriarTransacao();
        $this->assertTrue(true);
    }

    public function dadosProdutosEstaoNoCarrinho(): array
    {
        $itens = self::MOCK_ITENS;
        return [
            'Produtos estão no carrinho' => [count($itens), $itens, true],
            'Produtos não estão no carrinho' => [0, $itens, false],
        ];
    }

    /**
     * @dataProvider dadosProdutosEstaoNoCarrinho
     */
    public function testProdutosEstaoNoCarrinho(int $qtdProdutosCarrinho, array $produtos, bool $deveDarCerto): void
    {
        if (!$deveDarCerto) {
            $this->expectException(NotFoundHttpException::class);
        }

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOneColumn')->willReturn($qtdProdutosCarrinho);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        PedidoItem::verificaProdutosEstaoCarrinho($produtos);
        $this->assertTrue(true);
    }

    public function dadosRetornaEstoqueDisponivel(): array
    {
        $itens = self::MOCK_ITENS;
        $qtdEstoqueSuficiente = count($itens);
        $qtdEstoqueInsuficiente = (int) ($qtdEstoqueSuficiente / 2);
        $qtdEstoqueComplemento = $qtdEstoqueSuficiente - $qtdEstoqueInsuficiente;
        return [
            'Fulfillment possui 0 de estoque + Externo possui 0 de estoque' => [
                $itens,
                0,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => 0,
                ],
                null,
                false,
            ],
            'Fulfillment possui 0 de estoque + Externo não existe' => [
                $itens,
                0,
                [
                    'id_responsavel_estoque' => null,
                    'qtd_estoque_externo' => null,
                ],
                null,
                false,
            ],
            "Fulfillment possui 0 de estoque + Externo possui $qtdEstoqueSuficiente de estoque" => [
                $itens,
                0,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => $qtdEstoqueSuficiente,
                ],
                [
                    'estoque_fulfillment' => 0,
                    'estoque_externo' => $qtdEstoqueSuficiente,
                ],
                true,
            ],
            "Fulfillment possui $qtdEstoqueSuficiente de estoque + Externo possui 0 de estoque" => [
                $itens,
                $qtdEstoqueSuficiente,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => 0,
                ],
                [
                    'estoque_fulfillment' => $qtdEstoqueSuficiente,
                    'estoque_externo' => 0,
                ],
                true,
            ],
            "Fulfillment possui $qtdEstoqueSuficiente de estoque + Externo não existe" => [
                $itens,
                $qtdEstoqueSuficiente,
                [
                    'id_responsavel_estoque' => null,
                    'qtd_estoque_externo' => null,
                ],
                [
                    'estoque_fulfillment' => $qtdEstoqueSuficiente,
                    'estoque_externo' => 0,
                ],
                true,
            ],
            "Fulfillment possui $qtdEstoqueSuficiente de estoque + Externo possui $qtdEstoqueSuficiente de estoque" => [
                $itens,
                $qtdEstoqueSuficiente,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => $qtdEstoqueSuficiente,
                ],
                [
                    'estoque_fulfillment' => $qtdEstoqueSuficiente,
                    'estoque_externo' => 0,
                ],
                true,
            ],
            "Fulfillment possui $qtdEstoqueInsuficiente de estoque + Externo possui $qtdEstoqueComplemento de estoque" => [
                $itens,
                $qtdEstoqueInsuficiente,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => $qtdEstoqueComplemento,
                ],
                [
                    'estoque_fulfillment' => $qtdEstoqueInsuficiente,
                    'estoque_externo' => $qtdEstoqueComplemento,
                ],
                true,
            ],
            "Fulfillment possui $qtdEstoqueComplemento de estoque + Externo possui $qtdEstoqueInsuficiente de estoque" => [
                $itens,
                $qtdEstoqueComplemento,
                [
                    'id_responsavel_estoque' => 11224,
                    'qtd_estoque_externo' => $qtdEstoqueInsuficiente,
                ],
                [
                    'estoque_fulfillment' => $qtdEstoqueComplemento,
                    'estoque_externo' => $qtdEstoqueInsuficiente,
                ],
                true,
            ],
        ];
    }
    /**
     * @dataProvider dadosRetornaEstoqueDisponivel
     */
    public function testRetornaEstoqueDisponivel(
        array $produtos,
        int $qtdEstoqueFulfillment,
        array $valorConsultaExterno,
        ?array $qtdFinalDeProdutos,
        bool $deveDarCerto
    ): void {
        if (!$deveDarCerto) {
            $this->expectException(UnprocessableEntityHttpException::class);
        }

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn', 'select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOneColumn')->willReturn(count($produtos));
        $connectionMock->method('select')->willReturn([
            [
                'produto' => [
                    'id_produto' => 69,
                    'nome_tamanho' => 'Unico',
                ],
                'qtd_estoque_fulfillment' => $qtdEstoqueFulfillment,
                'externo' => $valorConsultaExterno,
                'informacoes_unitarias' => $produtos,
            ],
        ]);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        $origem = $this->createPartialMock(Origem::class, ['__call']);
        $origem->method('__call')->willReturn(false);
        app()->bind(Origem::class, fn() => $origem);
        $produtosDisponiveis = TransacaoPedidoItem::retornaEstoqueDisponivel($produtos);
        $produtosFulfillment = array_filter(
            $produtosDisponiveis,
            fn(array $produto): bool => $produto['id_responsavel_estoque'] === 1
        );
        $produtosExterno = array_filter(
            $produtosDisponiveis,
            fn(array $produto): bool => $produto['id_responsavel_estoque'] ===
                $valorConsultaExterno['id_responsavel_estoque']
        );

        $this->assertEquals($qtdFinalDeProdutos['estoque_fulfillment'], count($produtosFulfillment));
        $this->assertEquals($qtdFinalDeProdutos['estoque_externo'], count($produtosExterno));
    }
    public function dadosCriaComissoesMeuLook(): array
    {
        $dadosCriarComissao = [
            [
                'tipo_ponto' => 'PP',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CE',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CE',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CE',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],
            [
                'tipo_ponto' => 'PM',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 472,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 466,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],
            [
                'tipo_ponto' => 'ENVIO_TRANSPORTADORA',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 11224,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 20,
                        'preco' => 23.6,
                        'id_produto' => 69,
                        'id_responsavel_estoque' => 11224,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 1.4,
                        'preco' => 2.8,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32257,
                        'tipo_item' => 'FR',
                        'uuid_produto' => null,
                        'comissao_fornecedor' => 200,
                        'preco' => 200,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],
        ];
        $retorno = array_map(
            fn(array $dadoComissao): array => [
                "Para o metodo envio: {$dadoComissao['tipo_ponto']}" => [
                    [
                        'id_cidade' => 17934,
                        'latitude' => 0,
                        'longitude' => 0,
                        'id_colaborador' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 32257 : 472,
                        'tipo_ponto' =>
                            $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 'MS' : $dadoComissao['tipo_ponto'],
                        'porcentagem_comissao_ponto_coleta' => 7,
                        'valor_frete' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 200 : 0,
                        'valor_adicional' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 3 : 0,
                        'valor_transporte' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 0 : 3,
                        'id_colaborador_ponto_coleta' =>
                            $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 32254 : 466,
                        'porcentagem_frete_ponto_coleta' => 7,
                    ],
                    $dadoComissao['comissoes_esperadas'],
                ],
            ],
            $dadosCriarComissao
        );
        $retorno = array_merge(...$retorno);

        return $retorno;
    }
    /**
     * @dataProvider dadosCriaComissoesMeuLook
     */
    public function testCriaComissoesMeuLook(array $freteColaborador, array $comissoesEsperadas): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn', 'select']);
        $connectionMock->__construct($pdoMock);

        $itensReservados = array_map(
            fn(string $item): array => [
                'id_produto' => 69,
                'nome_tamanho' => 'Unico',
                'id_responsavel_estoque' => 11224,
                'uuid' => $item,
                'id_fornecedor' => 11224,
                'valor_custo_produto' => 20,
                'preco' => 23.6,
            ],
            self::MOCK_ITENS
        );

        if ($freteColaborador['tipo_ponto'] === 'MS') {
            $connectionMock->method('selectOneColumn')->willReturn($freteColaborador['id_colaborador']);
            $connectionMock->method('select')->willReturn([]);
        }

        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        $transacaoPedidoItemMock = new TransacaoPedidoItem();
        $transacaoPedidoItemMock->id_transacao = 1;
        $listaComissoes = $transacaoPedidoItemMock->calcularComissoesOrigemTransacaoML(
            $freteColaborador,
            $itensReservados
        );
        $listaComissoes = array_map('get_object_vars', $listaComissoes);
        $this->assertEquals($comissoesEsperadas, $listaComissoes);
    }

    public function dadosCriaComissoesMobileEntregas(): array
    {
        $dadosCriarComissao = [
            [
                'tipo_de_frete' => 'Frete Padrao',
                'tipo_ponto' => 'PM',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],

            [
                'tipo_de_frete' => 'Frete Padrao Com Coleta',
                'tipo_ponto' => 'PM',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3.39,
                        'preco' => 4,
                        'id_produto' => Produto::ID_PRODUTO_FRETE,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 53042,
                        'tipo_item' => 'CM_ENTREGA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 3,
                        'preco' => 3,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.24,
                        'preco' => 0.48,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 79563,
                        'tipo_item' => 'DIREITO_COLETA',
                        'uuid_produto' => null,
                        'comissao_fornecedor' => 2.25,
                        'preco' => 2.5,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],

            [
                'tipo_de_frete' => 'Frete Expresso',
                'tipo_ponto' => 'ENVIO_TRANSPORTADORA',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32257,
                        'tipo_item' => 'FR',
                        'uuid_produto' => null,
                        'comissao_fornecedor' => 200,
                        'preco' => 200,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],

            [
                'tipo_de_frete' => 'Frete Expresso com Coleta',
                'tipo_ponto' => 'ENVIO_TRANSPORTADORA',
                'comissoes_esperadas' => [
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_1',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_2',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 30726,
                        'tipo_item' => 'PR',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.1,
                        'preco' => 0.12,
                        'id_produto' => Produto::ID_PRODUTO_FRETE_EXPRESSO,
                        'id_responsavel_estoque' => 1,
                        'nome_tamanho' => 'Unico',
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32254,
                        'tipo_item' => 'CM_PONTO_COLETA',
                        'uuid_produto' => 'item_3',
                        'comissao_fornecedor' => 0.01,
                        'preco' => 0.02,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 79563,
                        'tipo_item' => 'DIREITO_COLETA',
                        'uuid_produto' => null,
                        'comissao_fornecedor' => 2.25,
                        'preco' => 2.5,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                    [
                        'nome_tabela' => 'transacao_financeiras_produtos_itens',
                        'id_transacao' => 1,
                        'id_fornecedor' => 32257,
                        'tipo_item' => 'FR',
                        'uuid_produto' => null,
                        'comissao_fornecedor' => 200,
                        'preco' => 200,
                        'id_produto' => null,
                        'id_responsavel_estoque' => null,
                        'nome_tamanho' => null,
                    ],
                ],
            ],
        ];
        $retorno = array_map(
            fn(array $dadoComissao): array => [
                "Para o tipo de frete: {$dadoComissao['tipo_de_frete']}" => [
                    [
                        'id_cidade' => 17934,
                        'latitude' => 0,
                        'longitude' => 0,
                        'id_colaborador' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 32257 : 53042,
                        'tipo_ponto' =>
                            $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 'MS' : $dadoComissao['tipo_ponto'],
                        'porcentagem_comissao_ponto_coleta' => 7,
                        'valor_frete' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 200 : 0,
                        'valor_adicional' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 3 : 0,
                        'valor_transporte' => $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 0 : 3,
                        'id_colaborador_ponto_coleta' =>
                            $dadoComissao['tipo_ponto'] === 'ENVIO_TRANSPORTADORA' ? 32254 : 30726,
                        'porcentagem_frete_ponto_coleta' => 7,
                        'preco_coleta' => in_array($dadoComissao['tipo_de_frete'], [
                            'Frete Padrao Com Coleta',
                            'Frete Expresso com Coleta',
                        ])
                            ? 2.5
                            : null,
                        'comissao_direito_coleta' => in_array($dadoComissao['tipo_de_frete'], [
                            'Frete Padrao Com Coleta',
                            'Frete Expresso com Coleta',
                        ])
                            ? 10
                            : null,
                        'id_colaborador_direito_coleta' => in_array($dadoComissao['tipo_de_frete'], [
                            'Frete Padrao Com Coleta',
                            'Frete Expresso com Coleta',
                        ])
                            ? 79563
                            : null,
                    ],
                    $dadoComissao['comissoes_esperadas'],
                ],
            ],
            $dadosCriarComissao
        );
        $retorno = array_merge(...$retorno);

        return $retorno;
    }

    /**
     * @dataProvider dadosCriaComissoesMobileEntregas
     */
    public function testCriaComissoesMobileEntregas(array $freteColaborador, array $comissoesEsperadas): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn', 'select']);
        $connectionMock->__construct($pdoMock);

        $itensReservados = array_map(
            fn(string $item): array => [
                'id_produto' =>
                    $freteColaborador['tipo_ponto'] === 'PM'
                        ? Produto::ID_PRODUTO_FRETE
                        : Produto::ID_PRODUTO_FRETE_EXPRESSO,
                'nome_tamanho' => 'Unico',
                'id_responsavel_estoque' => 1,
                'uuid' => $item,
                'id_fornecedor' => 30726,
                'valor_custo_produto' => $freteColaborador['tipo_ponto'] === 'PM' ? 3.39 : 0.1,
                'preco' => $freteColaborador['tipo_ponto'] === 'PM' ? 4 : 0.12,
            ],
            self::MOCK_ITENS
        );

        if ($freteColaborador['tipo_ponto'] === 'MS') {
            $connectionMock->method('selectOneColumn')->willReturn($freteColaborador['id_colaborador']);
            $connectionMock->method('select')->willReturn([]);
        }

        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        $transacaoPedidoItemMock = new TransacaoPedidoItem();
        $transacaoPedidoItemMock->id_transacao = 1;
        $listaComissoes = $transacaoPedidoItemMock->calcularComissoesOrigemTransacaoML(
            $freteColaborador,
            $itensReservados
        );
        $listaComissoes = array_map('get_object_vars', $listaComissoes);
        $this->assertEquals($comissoesEsperadas, $listaComissoes);
    }
}
