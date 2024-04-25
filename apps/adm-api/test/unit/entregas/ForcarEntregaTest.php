<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\model\Entrega;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\service\EntregaService\EntregaServices;

class ForcarEntregaTest extends test\TestCase
{
    public function dadosForcarEntrega(): array
    {
        return [
            'forcar entrega AB com produto PE' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'AB',
                    'situacao_produto' => 'PE',
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 1,
                        'tipo_ponto' => 'PP',
                    ],
                ],
            ],
            'forcar entrega EX com produto PE' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'EX',
                    'situacao_produto' => 'PE',
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 1,
                        'tipo_ponto' => 'PP',
                    ],
                ],
            ],
            'forcar entrega PT com produto PE' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'PT',
                    'situacao_produto' => 'PE',
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 1,
                        'tipo_ponto' => 'PM',
                    ],
                ],
            ],
            'forcar entrega AB com produto AR' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'AB',
                    'situacao_produto' => 'AR',
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 1,
                        'tipo_ponto' => 'PM',
                    ],
                ],
            ],
            'forcar entrega AB com produto EN' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'AB',
                    'situacao_produto' => 'AR',
                ],
                [
                    [
                        'id' => 1,
                        'id_tipo_frete' => 1,
                        'tipo_ponto' => 'PM',
                    ],
                ],
            ],
        ];
    }
    /**
     * @dataProvider dadosForcarEntrega
     */
    public function testForcarEntrega(array $retornoSelectOne, array $retornoSelect): void
    {
        $pdoMock = $this->createMock(PDO::class);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(Connection::class, ['selectOne', 'select', 'insert']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturnOnConsecutiveCalls([], $retornoSelect);
        $connectionMock->method('insert')->willReturn(null);
        $connectionMock->method('selectOne')->willReturn($retornoSelectOne);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        $entregaItemServiceMock = $this->createPartialMock(EntregasFaturamentoItem::class, [
            'confirmaEntregaDeProdutos',
        ]);
        $entregaItemServiceMock->expects($this->once())->method('confirmaEntregaDeProdutos');
        app()->bind(EntregasFaturamentoItem::class, fn() => $entregaItemServiceMock);

        Auth::setUser(
            new GenericUser([
                'id' => 1,
            ])
        );
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        Entrega::saving(function () {
            $this->assertEquals(1, 1);
            return false;
        });

        EntregaServices::forcarEntregaDeProduto('13223_Onomatopeia!');
    }

    public function dadosForcarEntregaEntregue(): array
    {
        return [
            'forcar entrega AB com produto PE' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'EN',
                    'situacao_produto' => 'PE',
                ],
            ],
            'forcar entrega EX com produto AR' => [
                [
                    'id_entrega' => 1,
                    'situacao_entrega' => 'EN',
                    'situacao_produto' => 'AR',
                ],
            ],
        ];
    }
    /**
     * @dataProvider dadosForcarEntregaEntregue
     */
    public function testForcarEntregaEnProdutoPe(array $retornoSelectOne): void
    {
        $pdoMock = $this->createMock(PDO::class);

        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(Connection::class, ['selectOne', 'select', 'insert']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturn([]);
        $connectionMock->method('insert')->willReturn(null);
        $connectionMock->method('selectOne')->willReturn($retornoSelectOne);

        $entregaItemServiceMock = $this->createPartialMock(EntregasFaturamentoItem::class, [
            'confirmaEntregaDeProdutos',
        ]);
        $entregaItemServiceMock->expects($this->once())->method('confirmaEntregaDeProdutos');
        app()->bind(EntregasFaturamentoItem::class, fn() => $entregaItemServiceMock);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        Auth::setUser(
            new GenericUser([
                'id' => 1,
            ])
        );
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        EntregaServices::forcarEntregaDeProduto('13223_Onomatopeia!');
        $this->assertEquals(1, 1);
    }
}
