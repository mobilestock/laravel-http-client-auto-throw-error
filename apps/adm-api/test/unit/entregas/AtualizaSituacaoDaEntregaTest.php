<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use MobileStock\model\Entrega;

class AtualizaSituacaoDaEntregaTest extends test\TestCase
{
    public function dadosDeveAtualizarSituacaoProduto(): array
    {
        return [
            'transportadora' => [
                ['id' => 1, 'id_tipo_frete' => 2, 'situacao' => 'EX', 'tipo_ponto' => 'PP'],
                'entregas_faturamento_item.id_entrega = :idEntrega;',
            ],
            'retirada' => [
                ['id' => 1, 'id_tipo_frete' => 3, 'situacao' => 'PT', 'tipo_ponto' => 'PP'],
                'entregas_faturamento_item.id_entrega = :idEntrega;',
            ],
            'entregador' => [
                ['id' => 1, 'id_tipo_frete' => 10, 'situacao' => 'PT', 'tipo_ponto' => 'PM'],
                'INNER JOIN entregas ON entregas.id = entregas_faturamento_item.id_entrega',
            ],
        ];
    }
    /**
     * @dataProvider dadosDeveAtualizarSituacaoProduto
     */
    public function testCondicionalAntesAtualizarSituacao(array $dados, string $matchString): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectColumns']);
        $connectionMock->__construct($pdoMock);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        $connectionMock->expects($this->once())->method('selectColumns')->with($this->stringContains($matchString));

        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $entrega = new Entrega();
        $modelEntrega = $entrega->hydrate([$dados])->first();
        $modelEntrega->situacao = 'EN';
        $entrega->antesAtualizarEntrega($modelEntrega);
    }
    public function testCairNaRemocaoDeAcompanhamento(): void
    {
        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['delete']);
        $connectionMock->method('delete')->with($this->stringContains('DELETE FROM acompanhamento_temp'));
        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManagerMock);

        $entrega = new Entrega();
        $modelEntrega = $entrega
            ->hydrate([
                [
                    'id' => 1,
                    'id_cliente' => 4922,
                    'id_tipo_frete' => 2,
                    'situacao' => 'AB',
                    'tipo_ponto' => 'PP',
                ],
            ])
            ->first();
        $modelEntrega->situacao = 'EX';
        $entrega->antesAtualizarEntrega($modelEntrega);
        $this->assertTrue(true);
    }

    // |------------------Bipagem de entrega prevista-------------------|
    public function dadosConfiguraNovaSituacao(): array
    {
        return [
            '[adm] transportadora AB => EX' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 4922,
                        'id_tipo_frete' => 2,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'FECHAR_ENTREGA',
                'EX',
            ],
            '[adm] transportadora AB => EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 4922,
                        'id_tipo_frete' => 2,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
            '[adm] transportadora EX => EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 4922,
                        'id_tipo_frete' => 2,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
            '[adm] retirada AB => EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 4922,
                        'id_tipo_frete' => 3,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
            '[adm] retirada EX => EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 4922,
                        'id_tipo_frete' => 3,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
            '[adm] ponto de retirada AB => PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 8647,
                        'id_tipo_frete' => 20,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[adm] ponto de retirada EX => PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 8647,
                        'id_tipo_frete' => 20,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[adm] entregador AB => PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8646,
                        'id_tipo_frete' => 21,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[adm] entregador EX => PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8646,
                        'id_tipo_frete' => 21,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[entregador] entregador AB => PT' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8228,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[entregador] entregador EX => PT' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8228,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[ponto de coleta] entregador AB => PT' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8228,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'PT',
            ],
            '[ponto de coleta] entregador PT => EN' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => 8002,
                        'id_cliente' => 8228,
                        'id_tipo_frete' => 10,
                        'situacao' => 'PT',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
            '[ponto de retirada] ponto de retirada PT => EN' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 1000,
                ],
                [
                    [
                        'id' => 1,
                        'id_raio' => null,
                        'id_cliente' => 8647,
                        'id_tipo_frete' => 20,
                        'situacao' => 'PT',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
                'BIPAGEM_PADRAO',
                'EN',
            ],
        ];
    }
    /**
     * @dataProvider dadosConfiguraNovaSituacao
     */
    public function testConfiguraNovaSituacao(
        array $usuario,
        array $retornoEntrega,
        string $acao,
        string $situacaoEsperada
    ): void {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(1);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createMock(DatabaseManager::class);
        $connectionMock = $this->createPartialMock(Connection::class, ['select', 'delete']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturn($retornoEntrega);

        $connectionMock
            ->method('delete')
            ->with($this->stringContains('DELETE FROM acompanhamento_temp'))
            ->willReturn(1);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        Auth::setUser(new GenericUser($usuario));
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));
        $entrega = Entrega::configuraNovaSituacao(1, $acao);

        $this->assertEquals($situacaoEsperada, $entrega->situacao);
    }
    // |------------------Bipagem de entrega nao prevista-------------------|
    public function dadosEsperaErroAoConfigurarEntrega(): array
    {
        return [
            'ponto de retirada bipando expedicao' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'Falha ao configurar entrega',
            ],
            'ponto de retirada bipando para EN a entrega de outro ponto de retirada' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'PT',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
                'Falha ao configurar entrega',
            ],
            'ponto de retirada bipando entrega de transportadora' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 2,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'Falha ao configurar entrega',
            ],
            'entregador bipa entrega de transportadora' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 1000,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 2,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'Falha ao configurar entrega',
            ],
            'adm bipa entrega PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'PT',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'Esta entrega ja foi expedida.',
            ],
            'adm bipa entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
                'Esta entrega ja foi entregue ao cliente.',
            ],
            'entregador aleatorio confirma bipagem AB entregador para PT' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 111,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'Você não tem permissão para realizar esta ação',
            ],
            'entregador aleatorio bipa entrega que nao lhe pertence' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 111,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EX',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'Você não tem permissão para realizar esta ação',
            ],
            'entregador bipar entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
                'Esta entrega ja foi bipada, bipe os itens para concluir o processo.',
            ],
            'ponto de retirada bipa entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 1000,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
                'Esta entrega ja foi bipada, bipe os itens para concluir o processo.',
            ],
        ];
    }
    /**
     * @dataProvider dadosEsperaErroAoConfigurarEntrega
     */
    public function testEsperaErroAoConfigurarEntrega(array $usuario, array $retornoEntrega, string $erroEsperado): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(1);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createMock(DatabaseManager::class);
        $connectionMock = $this->createPartialMock(Connection::class, ['select']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturn($retornoEntrega);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        Auth::setUser(new GenericUser($usuario));
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $this->expectExceptionMessage($erroEsperado);
        Entrega::configuraNovaSituacao(1, 'BIPAGEM_PADRAO');
    }
    public function dadosEsperaErroAoFecharEntrega(): array
    {
        return [
            'ponto de retirada fecha entrega' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
            ],
            'entregador fecha entrega' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 1000,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'AB',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
            ],
            'adm fecha entrega PT' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'PT',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
            ],
            'adm fecha entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '57',
                    'id_colaborador' => 123456,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 100,
                        'id_colaborador_ponto_coleta' => 200,
                    ],
                ],
            ],
            'entregador fecha entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '62',
                    'id_colaborador' => 2020,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PM',
                        'id_colaborador_tipo_frete' => 2020,
                        'id_colaborador_ponto_coleta' => 1000,
                    ],
                ],
            ],
            'ponto de retirada fecha entrega EN' => [
                [
                    'id' => 1,
                    'permissao' => '60',
                    'id_colaborador' => 1000,
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 10,
                        'situacao' => 'EN',
                        'tipo_ponto' => 'PP',
                        'id_colaborador_tipo_frete' => 1000,
                        'id_colaborador_ponto_coleta' => 2020,
                    ],
                ],
            ],
        ];
    }
    /**
     * @dataProvider dadosEsperaErroAoFecharEntrega
     */
    public function testEsperaErroAoFecharEntrega(array $usuario, array $retornoEntrega): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(1);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createMock(DatabaseManager::class);
        $connectionMock = $this->createPartialMock(Connection::class, ['select']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturn($retornoEntrega);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        Auth::setUser(new GenericUser($usuario));
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $this->expectExceptionMessage('Falha ao configurar entrega');
        Entrega::configuraNovaSituacao(1, 'FECHAR_ENTREGA');
    }
}
