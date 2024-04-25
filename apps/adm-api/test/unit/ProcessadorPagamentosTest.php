<?php

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ClienteException;
use MobileStock\helper\IuguEstaIndisponivel;
use MobileStock\helper\Pagamento\PagamentoAntiFraudeException;
use MobileStock\service\Pagamento\PagamentoAbstrato;
use MobileStock\service\Pagamento\ProcessadorPagamentos;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use test\TestCase;

class ProcessadorPagamentosTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        app()->bind(PDO::class, fn() => $this->createMock(PDO::class));

        $loggerMock = $this->createMock(LogManager::class);
        $loggerMock->method('driver')->willReturn($loggerMock);

        app()->bind(LogManager::class, fn() => $loggerMock);
    }

    public function testHouveErroNoPixRetentarComProximoMeioPagamento()
    {
        $pdoMock = $this->createMock(PDO::class);

        $transacaoMock = $this->getTransacaoFinanceira();
        $transacaoMock->id = 666;
        $transacaoMock->metodo_pagamento = 'PX';

        $interfacePagamento1 = new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
            public static string $LOCAL_PAGAMENTO = 'Elon Musk';

            public function comunicaApi(): TransacaoFinanceiraService
            {
                throw new Exception('Erro no Pix');
            }
        };

        $interfacePagamento2 = new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
            public static string $LOCAL_PAGAMENTO = 'Bill Gates';

            public function comunicaApi(): TransacaoFinanceiraService
            {
                Assert::assertEquals(1, 1);

                return $this->transacao;
            }
        };

        $processadorPagamentos = new ProcessadorPagamentos(
            $pdoMock,
            $transacaoMock,
            [get_class($interfacePagamento1), get_class($interfacePagamento2)],
            666
        );
        $processadorPagamentos->comunicaApis();
    }

    /**
     * @dataProvider dadosInterfacesPagamento
     */
    public function testHouveErroNoPixNaoDeveRetentar(
        PDO $conexao,
        TransacaoFinanceiraService $transacao,
        array $interfacesPagamento
    ) {
        $processadorPagamentos = new ProcessadorPagamentos(
            $conexao,
            $transacao,
            array_map(fn($interface) => get_class($interface), $interfacesPagamento),
            666
        );

        $this->expectException(PDOException::class);
        $processadorPagamentos->comunicaApis();
    }

    public function dadosInterfacesPagamento(): array
    {
        $pdoMock = $this->createMock(PDO::class);

        $transacaoMock = $this->getTransacaoFinanceira();
        $transacaoMock->id = 666;
        $transacaoMock->metodo_pagamento = 'PX';

        return [
            'uma_interface' => [
                $pdoMock,
                $transacaoMock,
                [
                    new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
                        public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
                        public static string $LOCAL_PAGAMENTO = 'Elon Musk';

                        public function comunicaApi(): TransacaoFinanceiraService
                        {
                            throw new PDOException('Erro no Pix');
                        }
                    },
                ],
            ],
            'duas_interfaces' => [
                $pdoMock,
                $transacaoMock,
                [
                    new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
                        public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
                        public static string $LOCAL_PAGAMENTO = 'Elon Musk';

                        public function comunicaApi(): TransacaoFinanceiraService
                        {
                            throw new Exception('Erro no Pix');
                        }
                    },
                    new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
                        public static array $METODOS_PAGAMENTO_SUPORTADOS = ['PX'];
                        public static string $LOCAL_PAGAMENTO = 'Elon Musk';

                        public function comunicaApi(): TransacaoFinanceiraService
                        {
                            throw new PDOException('Erro no Pix');
                        }
                    },
                ],
            ],
        ];
    }

    public function testCartaoIuguCaiuDeveDesativarCasoTenhaProximoMeioPagamento()
    {
        $_ENV['AMBIENTE'] = 'teste';
        $pdoMock = $this->createMock(PDO::class);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetch')->willReturn([
            'informacoes_metodos_pagamento' => json_encode([
                [
                    'prefixo' => 'CA',
                    'nome' => 'Cartão',
                    'meios_pagamento' => [
                        [
                            'local_pagamento' => 'Iugu',
                            'situacao' => 'ativo',
                        ],
                        [
                            'local_pagamento' => 'Zoop',
                            'situacao' => 'ativado',
                        ],
                    ],
                ],
            ]),
        ]);
        $pdoMock
            ->expects($this->once())
            ->method('query')
            ->willReturn($stmtMock);
        $pdoMock
            ->expects($this->once())
            ->method('exec')
            ->with(
                $this->stringContains(
                    json_encode(
                        [
                            [
                                'prefixo' => 'CA',
                                'nome' => 'Cartão',
                                'meios_pagamento' => [
                                    [
                                        'local_pagamento' => 'Iugu',
                                        'situacao' => 'desativado',
                                    ],
                                    [
                                        'local_pagamento' => 'Zoop',
                                        'situacao' => 'ativado',
                                    ],
                                ],
                            ],
                        ],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE
                    )
                )
            );

        app()->bind(PDO::class, fn() => $pdoMock);

        $transacaoMock = $this->getTransacaoFinanceira();
        $transacaoMock->id = 666;
        $transacaoMock->metodo_pagamento = 'CA';

        $interfacePagamento1 = new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];
            public static string $LOCAL_PAGAMENTO = 'Iugu';

            public function comunicaApi(): TransacaoFinanceiraService
            {
                throw new IuguEstaIndisponivel();
            }
        };

        $interfacePagamento2 = new class ($pdoMock, $transacaoMock) extends PagamentoAbstrato {
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];
            public static string $LOCAL_PAGAMENTO = 'Bill Gates';

            public function comunicaApi(): TransacaoFinanceiraService
            {
                return $this->transacao;
            }
        };

        $processadorPagamentos = new ProcessadorPagamentos(
            $pdoMock,
            $transacaoMock,
            [get_class($interfacePagamento1), get_class($interfacePagamento2)],
            666
        );

        $this->expectException(IuguEstaIndisponivel::class);
        # TODO: Mockar configuracoes service.
        $processadorPagamentos->comunicaApis();
    }

    public function testFalhaAposCobrarCartaoClienteDeveEnviarMsgTelegram()
    {
        $conexao = $this->createMock(PDO::class);

        $exceptionBase = new PDOException('Não consegui salvar no banco');
        $conexao
            ->expects($this->exactly(2))
            ->method('exec')
            ->willReturnOnConsecutiveCalls(true, $this->throwException($exceptionBase));

        $transacao = $this->getTransacaoFinanceira();
        $transacao->id = 666;
        $transacao->metodo_pagamento = 'CA';

        $meioPagamento1 = new class ($conexao, $transacao) extends PagamentoAbstrato {
            public static string $LOCAL_PAGAMENTO = 'MePaga';
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];

            public function comunicaApi(): TransacaoFinanceiraService
            {
                $this->transacao->cod_transacao = 'PRESSAO!PRESSAO!PRESSAO!';

                return $this->transacao;
            }
        };

        $loggerMock = app(LogManager::class);

        $loggerMock->expects($this->once())->method('emergency');

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->setApplication(App::getFacadeRoot());
        $connectionMock = $this->createMock(Connection::class);
        $builderMock = $this->createMock(Builder::class);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        $connectionMock->method('table')->willReturn($builderMock);
        $builderMock->method('insert')->willReturn(true);

        DB::swap($databaseManagerMock);

        $processadorPagamentos = new ProcessadorPagamentos($conexao, $transacao, [get_class($meioPagamento1)], 666);

        try {
            $processadorPagamentos->executa();
        } catch (ClienteException $exception) {
            $this->assertInstanceOf(get_class($exceptionBase), $exception->getPrevious());
        }
    }

    public function testRetentarAposCartaoRecusadoPorSuspeitaFraude()
    {
        $transacao = $this->getTransacaoFinanceira();
        $transacao->metodo_pagamento = 'CA';
        $transacao->id = 666;
        $transacao->pagador = 666;
        $conexao = $this->createMock(PDO::class);

        $meioPagamento1 = new class ($conexao, $transacao) extends PagamentoAbstrato {
            public static string $LOCAL_PAGAMENTO = 'sou o primeiro meio de pagamento';
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];

            public function comunicaApi(): TransacaoFinanceiraService
            {
                throw new PagamentoAntiFraudeException();
            }
        };

        $meioPagamento2 = new class ($conexao, $transacao) extends PagamentoAbstrato {
            public static string $LOCAL_PAGAMENTO = 'sou o segundo meio de pagamento';
            public static array $METODOS_PAGAMENTO_SUPORTADOS = ['CA'];

            public function comunicaApi(): TransacaoFinanceiraService
            {
                return $this->transacao;
            }
        };

        $conexao
            ->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('ROLLBACK TO SAVEPOINT '));

        $pagamentos = new ProcessadorPagamentos(
            $conexao,
            $transacao,
            [get_class($meioPagamento1), get_class($meioPagamento2)],
            666
        );

        $pagamentos->comunicaApis();

        $this->assertEquals(1, 1);
    }

    /**
     * @return TransacaoFinanceiraService|(TransacaoFinanceiraService&object&MockObject)|(TransacaoFinanceiraService&MockObject)|(object&MockObject)|MockObject
     */
    public function getTransacaoFinanceira()
    {
        $mock = $this->createPartialMock(TransacaoFinanceiraService::class, [
            'BloqueiaLinhaTransacao',
            'atualizaTransacao',
        ]);
        $mock->method('atualizaTransacao')->willReturn(1);

        return $mock;
    }
}
