<?php

use MobileStock\Shared\PdoInterceptor\Middlewares\CastWithDatabaseColumns;

class CastWithDatabaseColumnsTest extends TestCase
{
    public function consultasEsperaResultados(): Generator
    {
        yield '[PDO::FETCH_COLUMN] #1 INT.' => [
            ['0', '1'],
            [0, 1],
            function () {
                return [
                    'native_type' => 'INT24',
                    'name' => 'int',
                ];
            },
        ];

        yield '[PDO::FETCH_ASSOC ] #1 INT #2 STR' => [
            [
                [
                    'int' => '1',
                    'string' => '1',
                ],
            ],
            [
                [
                    'int' => 1,
                    'string' => '1',
                ],
            ],
            function ($column) {
                if ($column === 0) {
                    return [
                        'native_type' => 'INT24',
                        'name' => 'int',
                    ];
                }

                return [
                    'native_type' => 'VAR_STRING',
                    'name' => 'string',
                ];
            },
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo termina com _json e seu valor é válido' => [
            [
                [
                    'campo_json' => '{"campo1":"valor1","campo2":"valor2"}',
                ],
            ],
            [
                [
                    'campo' => [
                        'campo1' => 'valor1',
                        'campo2' => 'valor2',
                    ],
                ],
            ],
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo com prefixo json_' => [
            [
                [
                    'json_campo' => '{"campo1":"valor1","campo2":"valor2"}',
                ],
            ],
            [
                [
                    'campo' => [
                        'campo1' => 'valor1',
                        'campo2' => 'valor2',
                    ],
                ],
            ],
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo termina com _json e seu valor é inválido' => [
            [
                [
                    'campo_json' => '{"campo1":"valor1","campo2":"valor2"',
                ],
                [
                    'campo_json' => null,
                ],
            ],
            [
                [
                    'campo' => '{"campo1":"valor1","campo2":"valor2"',
                ],
                [
                    'campo' => null,
                ],
            ],
        ];

        yield '[PDO::FETCH_COLUMN] alias/campo termina com _json e seu valor é válido' => [
            ['{"campo1":"valor1","campo2":null}', '{"campo1":"valor1","campo2":null}'],
            [
                [
                    'campo1' => 'valor1',
                    'campo2' => null,
                ],
                [
                    'campo1' => 'valor1',
                    'campo2' => null,
                ],
            ],
            function () {
                return [
                    'native_type' => 'VAR_STRING',
                    'name' => 'campo_json',
                ];
            },
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo termina com _bool' => [
            [
                [
                    'campo_bool' => '1',
                ],
            ],
            [
                [
                    'campo' => true,
                ],
            ],
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo com prefixo bool_' => [
            [
                [
                    'bool_campo' => '1',
                ],
            ],
            [
                [
                    'campo' => true,
                ],
            ],
        ];

        yield '[PDO::FETCH_ASSOC ] alias/campo verbo bool, int e float' => [
            [
                [
                    'esta_pago' => '1',
                    'valor' => '1',
                    'valor2' => '1.1',
                ],
            ],
            [
                [
                    'esta_pago' => true,
                    'valor' => 1,
                    'valor2' => 1.1,
                ],
            ],
            function ($column) {
                if ($column === 0) {
                    return [
                        'native_type' => 'INT24',
                        'name' => 'esta_pago',
                    ];
                } elseif ($column === 1) {
                    return [
                        'native_type' => 'INT24',
                        'name' => 'valor',
                    ];
                }

                return [
                    'native_type' => 'FLOAT',
                    'name' => 'valor2',
                ];
            },
        ];

        yield '[PDO::FETCH_ASSOC ] campo json recursivo' => [
            [
                [
                    'campo_json' => '{"esta_viajando":"0","eh_programador":null}',
                    'campo2_json' => '{"campo_int":null,"campo2_json":"{\"esta_invalido\":\"1\",\"campo_float\":0}"}',
                ],
            ],
            [
                [
                    'campo' => [
                        'esta_viajando' => false,
                        'eh_programador' => false,
                    ],
                    'campo2' => [
                        'campo' => 0,
                        'campo2' => [
                            'esta_invalido' => true,
                            'campo' => 0.0,
                        ],
                    ],
                ],
            ],
        ];

        yield '[PDO::FETCH_ASSOC ] recursivo + 500 profundidade' => [
            [
                [
                    'campo_json' => '{"item1":
                            {"item2":
                                {"item3":
                                    {"item4":
                                        {"item5":
                                            {"item6":
                                                {"item7":
                                                    {"item8":
                                                        {"item9":
                                                            {"item10":
                                                                {"eh_cast":0}
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }',
                ],
            ],
            function () {
                $this->expectException(Exception::class);
            },
        ];

        yield '[PDO::FETCH_ASSOC ] json recursivo dois campos iguais' => [
            [
                [
                    'campo_json' => '{"item_float":"valor1","item_bool":null}',
                ],
            ],
            [
                [
                    'campo' => [
                        'item' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider consultasEsperaResultados
     * @var array $resultadoNativo
     * @var array|Closure $resultadoEsperado
     */
    public function testEsperaResultados(
        array $resultadoNativo,
        $resultadoEsperado,
        ?callable $getColumnMeta = null
    ): void {
        $stmt = parent::getStmt(
            (new Illuminate\Pipeline\Pipeline())->through(function (array $data, callable $next) {
                $middleware = new CastWithDatabaseColumns(['eh', 'deve', 'esta']);
                return $middleware->handle($data, $next);
            })
        );

        $stmt->parent = new class ($resultadoNativo, $getColumnMeta) {
            private array $resultadoNativo;
            /**
             * @var callable
             */
            private $getColumnMeta;

            public function __construct(array $resultadoNativo, ?callable $getColumnMeta)
            {
                $this->resultadoNativo = $resultadoNativo;
                $this->getColumnMeta = $getColumnMeta;
            }

            public function fetchAll(): array
            {
                return $this->resultadoNativo;
            }

            public function getColumnMeta(): array
            {
                if (!$this->getColumnMeta) {
                    return [];
                }

                return call_user_func_array($this->getColumnMeta, func_get_args());
            }
        };

        if ($resultadoEsperado instanceof Closure) {
            $resultadoEsperado->call($this);
        }

        $dados = $stmt->fetchAll();

        if (!is_callable($resultadoEsperado)) {
            $this->assertSame($resultadoEsperado, $dados);
        }
    }
}
