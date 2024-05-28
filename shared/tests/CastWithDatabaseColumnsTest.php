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
                    'flags' => ['not_null'],
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
                        'flags' => ['not_null'],
                    ];
                }

                return [
                    'native_type' => 'VAR_STRING',
                    'name' => 'string',
                    'flags' => ['not_null'],
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
                    'flags' => ['not_null'],
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
                        'flags' => ['not_null'],
                    ];
                } elseif ($column === 1) {
                    return [
                        'native_type' => 'INT24',
                        'name' => 'valor',
                        'flags' => ['not_null'],
                    ];
                }

                return [
                    'native_type' => 'FLOAT',
                    'name' => 'valor2',
                    'flags' => ['not_null'],
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

        $json =
            '{"item1":{"item2":{"item3":{"item4":{"item5":{"item6":{"item7":{"item8":{"item9":{"item10":{"item11":{"item12":{"item13":{"item14":{"item15":{"item16":{"item17":{"item18":{"item19":{"item20":{"item21":{"item22":{"item23":{"item24":{"item25":{"item26":{"item27":{"item28":{"item29":{"item30":{"item31":{"item32":{"item33":{"item34":{"item35":{"item36":{"item37":{"item38":{"item39":{"item40":{"item41":{"item42":{"item43":{"item44":{"item45":{"item46":{"item47":{"item48":{"item49":{"item50":{"item51":{"item52":{"item53":{"item54":{"item55":{"item56":{"item57":{"item58":{"item59":{"item60":{"item61":{"item62":{"item63":{"item64":{"item65":{"item66":{"item67":{"item68":{"item69":{"item70":{"item71":{"item72":{"item73":{"item74":{"item75":{"item76":{"item77":{"item78":{"item79":{"item80":{"item81":{"item82":{"item83":{"item84":{"item85":{"item86":{"item87":{"item88":{"item89":{"item90":{"item91":{"item92":{"item93":{"item94":{"item95":{"item96":{"item97":{"item98":{"item99":{"item100":{"item101":{"item102":{"item103":{"item104":{"item105":{"item106":{"item107":{"item108":{"item109":{"item110":{"item111":{"item112":{"item113":{"item114":{"item115":{"item116":{"item117":{"item118":{"item119":{"item120":{"item121":{"item122":{"item123":{"item124":{"item125":{"item126":{"item127":{"item128":{"item129":{"item130":{"item131":{"item132":{"item133":{"item134":{"item135":{"item136":{"item137":{"item138":{"item139":{"item140":{"item141":{"item142":{"item143":{"item144":{"item145":{"item146":{"item147":{"item148":{"item149":{"item150":{"item151":{"item152":{"item153":{"item154":{"item155":{"item156":{"item157":{"item158":{"item159":{"item160":{"item161":{"item162":{"item163":{"item164":{"item165":{"item166":{"item167":{"item168":{"item169":{"item170":{"item171":{"item172":{"item173":{"item174":{"item175":{"item176":{"item177":{"item178":{"item179":{"item180":{"item181":{"item182":{"item183":{"item184":{"item185":{"item186":{"item187":{"item188":{"item189":{"item190":{"item191":{"item192":{"item193":{"item194":{"item195":{"item196":{"item197":{"item198":{"item199":{"item200":{"item201":{"item202":{"item203":{"item204":{"item205":{"item206":{"item207":{"item208":{"item209":{"item210":{"item211":{"item212":{"item213":{"item214":{"item215":{"item216":{"item217":{"item218":{"item219":{"item220":{"item221":{"item222":{"item223":{"item224":{"item225":{"item226":{"item227":{"item228":{"item229":{"item230":{"item231":{"item232":{"item233":{"item234":{"item235":{"item236":{"item237":{"item238":{"item239":{"item240":{"item241":{"item242":{"item243":{"item244":{"item245":{"item246":{"item247":{"item248":{"item249":{"item250":{"item251":{"item252":{"item253":{"item254":{"item255":{"item256":{"item257":{"item258":{"item259":{"item260":{"item261":{"item262":{"item263":{"item264":{"item265":{"item266":{"item267":{"item268":{"item269":{"item270":{"item271":{"item272":{"item273":{"item274":{"item275":{"item276":{"item277":{"item278":{"item279":{"item280":{"item281":{"item282":{"item283":{"item284":{"item285":{"item286":{"item287":{"item288":{"item289":{"item290":{"item291":{"item292":{"item293":{"item294":{"item295":{"item296":{"item297":{"item298":{"item299":{"item300":{"item301":{"item302":{"item303":{"item304":{"item305":{"item306":{"item307":{"item308":{"item309":{"item310":{"item311":{"item312":{"item313":{"item314":{"item315":{"item316":{"item317":{"item318":{"item319":{"item320":{"item321":{"item322":{"item323":{"item324":{"item325":{"item326":{"item327":{"item328":{"item329":{"item330":{"item331":{"item332":{"item333":{"item334":{"item335":{"item336":{"item337":{"item338":{"item339":{"item340":{"item341":{"item342":{"item343":{"item344":{"item345":{"item346":{"item347":{"item348":{"item349":{"item350":{"item351":{"item352":{"item353":{"item354":{"item355":{"item356":{"item357":{"item358":{"item359":{"item360":{"item361":{"item362":{"item363":{"item364":{"item365":{"item366":{"item367":{"item368":{"item369":{"item370":{"item371":{"item372":{"item373":{"item374":{"item375":{"item376":{"item377":{"item378":{"item379":{"item380":{"item381":{"item382":{"item383":{"item384":{"item385":{"item386":{"item387":{"item388":{"item389":{"item390":{"item391":{"item392":{"item393":{"item394":{"item395":{"item396":{"item397":{"item398":{"item399":{"item400":{"item401":{"item402":{"item403":{"item404":{"item405":{"item406":{"item407":{"item408":{"item409":{"item410":{"item411":{"item412":{"item413":{"item414":{"item415":{"item416":{"item417":{"item418":{"item419":{"item420":{"item421":{"item422":{"item423":{"item424":{"item425":{"item426":{"item427":{"item428":{"item429":{"item430":{"item431":{"item432":{"item433":{"item434":{"item435":{"item436":{"item437":{"item438":{"item439":{"item440":{"item441":{"item442":{"item443":{"item444":{"item445":{"item446":{"item447":{"item448":{"item449":{"item450":{"item451":{"item452":{"item453":{"item454":{"item455":{"item456":{"item457":{"item458":{"item459":{"item460":{"item461":{"item462":{"item463":{"item464":{"item465":{"item466":{"item467":{"item468":{"item469":{"item470":{"item471":{"item472":{"item473":{"item474":{"item475":{"item476":{"item477":{"item478":{"item479":{"item480":{"item481":{"item482":{"item483":{"item484":{"item485":{"item486":{"item487":{"item488":{"item489":{"item490":{"item491":{"item492":{"item493":{"item494":{"item495":{"item496":{"item497":{"item498":{"item499":{"item500":{"item501":{"item502":{"item503":{"item504":{"item505":{"item506":{"item507":{"item508":{"item509":{"item510":{"item511":{"item512":{"eh_cast":0}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}}';
        $estrutura = [
            [
                'campo_json' => $json,
            ],
        ];

        $estruturaNova[0]['campo'] = json_decode(str_replace('"eh_cast":0', '"eh_cast":false', $json), true, 802);
        yield '[PDO::FETCH_ASSOC ] recursivo + 800 profundidade' => [$estrutura, $estruturaNova];

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

        yield '[PDO::FETCH_COLUMN] #1 INT e nullable' => [
            ['1', null],
            [1, null],
            function () {
                return [
                    'native_type' => 'INT24',
                    'name' => 'int',
                    'flags' => [],
                ];
            },
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
