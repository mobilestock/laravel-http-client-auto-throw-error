<?php

namespace MobileStock\service;

use DomainException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use PDO;
use PDOStatement;
use test\TestCase;

class CancelamentoProdutosTest extends TestCase
{
    public function dados(): array
    {
        return [
            'apenas credito normal com valor superior ao da correção' => [
                [
                    [
                        'id' => 1,
                        'valor_pode_pagar_credito' => 101,
                        'valor_pode_pagar_bloqueado' => 0,
                        'valor_corrigido' => 100.0,
                        'id_cliente' => 2,
                        'valor_estornado' => 0.0,
                        'valor_total' => 800.0,
                    ],
                ],
                [
                    'normal' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 100.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                ],
            ],
            'apenas credito pendente' => [
                [
                    [
                        'id' => 1,
                        'valor_pode_pagar_credito' => 0,
                        'valor_pode_pagar_bloqueado' => 101,
                        'valor_corrigido' => 100.0,
                        'id_cliente' => 2,
                        'valor_estornado' => 0,
                        'valor_total' => 800.0,
                    ],
                ],
                [
                    'pendente' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 100.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                ],
            ],
            'credito normal (99%) + pendente (1%)' => [
                [
                    [
                        'id' => 1,
                        'valor_pode_pagar_credito' => 99,
                        'valor_pode_pagar_bloqueado' => 1,
                        'valor_corrigido' => 100.0,
                        'id_cliente' => 2,
                        'valor_estornado' => 0,
                        'valor_total' => 800.0,
                    ],
                ],
                [
                    'normal' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 99.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                    'pendente' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 1.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                ],
            ],
            'credito normal (1%) + pendente (99%)' => [
                [
                    [
                        'id' => 1,
                        'valor_pode_pagar_credito' => 1,
                        'valor_pode_pagar_bloqueado' => 99,
                        'valor_corrigido' => 100.0,
                        'id_cliente' => 2,
                        'valor_estornado' => 0,
                        'valor_total' => 800.0,
                    ],
                ],
                [
                    'normal' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 1.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                    'pendente' => [
                        [
                            'tipo' => 'P',
                            'origem' => 'ES',
                            'valor' => 99.0,
                            'transacao_origem' => 1,
                            'id_colaborador' => 2,
                        ],
                    ],
                ],
            ],
            'credito insuficiente' => [
                [
                    [
                        'id' => 1,
                        'valor_pode_pagar_credito' => 0,
                        'valor_pode_pagar_bloqueado' => 5,
                        'valor_corrigido' => 100.0,
                        'id_cliente' => 2,
                        'valor_estornado' => 0,
                        'valor_total' => 800.0,
                    ],
                ],
                InvalidArgumentException::class,
            ],
            'nenhum pedido corrigido' => [[], DomainException::class],
        ];
    }

    /**
     * @dataProvider dados
     * @param array|class-string $valorEsperado
     */
    public function testEstornaCliente(array $pedidosCorrigidos, $valorEsperado): void
    {
        if (is_string($valorEsperado) && class_exists($valorEsperado)) {
            $this->expectException($valorEsperado);
        }

        $cancelamentoProdutos = $this->createPartialMock(CancelamentoProdutos::class, []);
        $cancelamentoProdutos->__construct($pedidosCorrigidos);
        $resultado = $cancelamentoProdutos->estornaCliente($pedidosCorrigidos);

        $assertArrayHasValues = function ($valorEsperado, $resultado) use (&$assertArrayHasValues) {
            foreach ($valorEsperado as $key => $item) {
                $this->assertArrayHasKey($key, $resultado);
                if (is_array($item)) {
                    $assertArrayHasValues($item, $resultado[$key]);
                } else {
                    $this->assertEquals($item, $resultado[$key]);
                }
            }
        };

        $assertArrayHasValues($valorEsperado, $resultado);
    }

    public function testTransacaoFoiPagaAteFicarTotalmenteEstornada()
    {
        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createMock(MysqlConnection::class);
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('rowCount')->willReturn(1);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $connectionMock->expects($this->once())->method('getPdo')->willReturn($pdoMock);

        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManagerMock);
        $cancelamentoProdutos = $this->createPartialMock(CancelamentoProdutos::class, []);
        $pedidosCorrigidos = [
            [
                'id' => 1,
                'valor_pode_pagar_credito' => 100,
                'valor_pode_pagar_bloqueado' => 0,
                'valor_corrigido' => 100.0,
                'id_cliente' => 2,
                'valor_estornado' => 0,
                'valor_total' => 100.0,
            ],
        ];
        $cancelamentoProdutos->__construct($pedidosCorrigidos);

        $cancelamentoProdutos->estornaCliente($pedidosCorrigidos);
    }
}
