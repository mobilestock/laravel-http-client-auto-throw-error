<?php

use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ValidadorTest extends TestCase
{
    public function setUp(): void
    {
        $_ENV['AMBIENTE'] = 'teste';
    }

    public function testValida100Indices()
    {
        for ($i = 1; $i <= 100; $i++) {
            Validador::validar(
                ['id' => $i],
                [
                    'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]
            );
        }
        self::assertEquals(1, 1);
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoArraySemElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [Validador::SE(Validador::OBRIGATORIO, Validador::CPF)],
            ]
        );

        if ($valido) {
            self::assertEquals(1, 1);
        } else {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoBooleanaSemElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [Validador::SE(true, Validador::CPF)],
            ]
        );

        if ($valido) {
            self::assertEquals(1, 1);
        } else {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoFuncaoSemElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [
                    Validador::SE(function (string $cpfRecebido) use ($cpf) {
                        Assert::assertEquals($cpf, $cpfRecebido, 'O cpf recebido é diferente do enviado.');

                        return true;
                    }, Validador::CPF),
                ],
            ]
        );

        if (!$valido) {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testMultiploValidacaoComCondicaoBooleanaSemElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [Validador::SE(true, [Validador::OBRIGATORIO, Validador::CPF])],
            ]
        );

        if (!$valido) {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        } else {
            self::assertEquals(1, 1);
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoArrayComElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [Validador::SE(Validador::OBRIGATORIO, Validador::CPF, Validador::TAMANHO_MINIMO(11))],
            ]
        );

        if ($valido) {
            self::assertEquals(1, 1);
        } else {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoBooleanaComElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [Validador::SE(true, Validador::CPF, Validador::TAMANHO_MINIMO(11))],
            ]
        );

        if ($valido) {
            self::assertEquals(1, 1);
        } else {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testValidacaoCpfComCondicaoFuncaoComElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [
                    Validador::SE(
                        function (string $cpfRecebido) use ($cpf) {
                            Assert::assertEquals($cpf, $cpfRecebido, 'O cpf recebido é diferente do enviado.');

                            return true;
                        },
                        Validador::CPF,
                        Validador::TAMANHO_MINIMO(11)
                    ),
                ],
            ]
        );

        if (!$valido) {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        }
    }

    /**
     * @dataProvider listaCpf
     */
    public function testMultiploValidacaoComCondicaoBooleanaComElse(string $cpf, bool $valido)
    {
        if (!$valido) {
            self::expectException(ValidacaoException::class);
        }

        Validador::validar(
            [
                'cpf' => $cpf,
            ],
            [
                'cpf' => [
                    Validador::SE(
                        true,
                        [Validador::OBRIGATORIO, Validador::CPF],
                        [Validador::NUMERO, Validador::TAMANHO_MINIMO(11)]
                    ),
                ],
            ]
        );

        if (!$valido) {
            self::assertEquals(1, 2, 'Deveria ter lançado uma exceção');
        } else {
            self::assertEquals(1, 1);
        }
    }

    public function testNaoDeveValidarCpfPorCondicaoArraySemElse()
    {
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [Validador::SE(Validador::OBRIGATORIO, Validador::CPF)],
            ]
        );

        self::assertEquals(1, 1);
    }

    public function testNaoDeveValidarCpfPorCondicaoBooleanaSemElse()
    {
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [Validador::SE(false, Validador::CPF)],
            ]
        );

        self::assertEquals(1, 1);
    }

    public function testNaoDeveValidarCpfPorCondicaoFuncaoSemElse()
    {
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [
                    Validador::SE(function ($cpf) {
                        self::assertEquals(null, $cpf, 'O cpf recebido é diferente do enviado.');

                        return false;
                    }, Validador::CPF),
                ],
            ]
        );

        self::assertEquals(1, 1);
    }

    public function testNaoDeveValidarCpfPorCondicaoArrayComElse()
    {
        self::expectException(ValidacaoException::class);
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [Validador::SE(Validador::OBRIGATORIO, Validador::CPF, Validador::TAMANHO_MINIMO(11))],
            ]
        );

        self::assertEquals(1, 1);
    }

    public function testNaoDeveValidarCpfPorCondicaoBooleanaComElse()
    {
        self::expectException(ValidacaoException::class);
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [Validador::SE(false, Validador::CPF, Validador::TAMANHO_MINIMO(11))],
            ]
        );

        self::assertEquals(1, 1);
    }

    public function testNaoDeveValidarCpfPorCondicaoFuncaoComElse()
    {
        self::expectException(ValidacaoException::class);
        Validador::validar(
            [
                'cpf' => null,
            ],
            [
                'cpf' => [
                    Validador::SE(
                        function ($cpf) {
                            self::assertEquals(null, $cpf, 'O cpf recebido é diferente do enviado.');

                            return false;
                        },
                        Validador::CPF,
                        Validador::TAMANHO_MINIMO(11)
                    ),
                ],
            ]
        );

        self::assertEquals(1, 1);
    }

    /**
     * @dataProvider listaValoresAceitosCondicional
     */
    public function testValidadorCondicaoDeveAceitarParametro($valor, ?bool $resultadoEsperado = null)
    {
        if ($resultadoEsperado === false) {
            self::expectException(ValidacaoException::class);
        }
        Validador::SE($valor, Validador::OBRIGATORIO, Validador::NUMERO)['metodo']('teste', 'teste');
        self::assertEquals(1, 1);
    }

    /**
     * @dataProvider listaValoresNaoAceitosCondicional
     */
    public function testValidadorCondicaoNaoDeveAceitarParametro($valor)
    {
        self::expectException(InvalidArgumentException::class);
        Validador::SE($valor, [])['metodo']('teste', 'teste');
    }

    public function listaCpf(): array
    {
        return [
            'Com cpf inválido' => ['123', false],
            'Com cpf válido' => ['698.572.970-30', true],
        ];
    }

    public function listaValoresAceitosCondicional(): array
    {
        return [
            [true],
            [false, false],
            [1500],
            [PHP_INT_MAX],
            [Validador::OBRIGATORIO, true],
            [[Validador::OBRIGATORIO], true],
            [[Validador::OBRIGATORIO, Validador::NUMERO], false],
            [[['id' => 123]]],
            [[], false],
            [null, false],
            [new stdClass()],
            [(object) ['id' => 123]],
            ['peteca'],
            [fn() => true],
            [fn() => false, false],
            [fn() => 1500],
            [fn() => PHP_INT_MAX],
            [fn() => Validador::OBRIGATORIO],
            [fn() => [Validador::OBRIGATORIO]],
            [fn() => [[['id' => 123]]]],
            [fn() => [[]]],
            [fn() => null, false],
            [fn() => new stdClass()],
            [fn() => (object) ['id' => 123]],
            [fn() => 'peteca'],
        ];
    }

    public function listaValoresNaoAceitosCondicional(): array
    {
        return [[fn() => STDOUT], [fn() => fn() => true]];
    }
}
