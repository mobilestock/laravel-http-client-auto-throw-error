<?php

use MobileStock\service\ConfiguracaoService;
use MobileStock\service\Pagamento\PagamentoCartaoCielo;
use MobileStock\service\Pagamento\PagamentoCartaoIugu;
use MobileStock\service\Pagamento\PagamentoCartaoZoop;
use MobileStock\service\Pagamento\PagamentoCreditoInterno;
use MobileStock\service\Pagamento\PagamentoPixBoletoIugu;
use MobileStock\service\Pagamento\PagamentoPixSicoob;
use PHPUnit\Framework\TestCase;

class GerenciamentoInterfacesPagamentoTest extends TestCase
{
    public function testDeveSerInternoCasoValorLiquidoSeja0()
    {
        $pdoMock = $this->createMock(PDO::class);

        $interfaces = ConfiguracaoService::buscaInterfacesPagamento(
            $pdoMock,
            'CA',
            0,
            0
        );

        $this->assertEquals([PagamentoCreditoInterno::class], $interfaces);
    }

    public function testDeveSerCieloCasoValorPasse4000()
    {
        $pdoMock = $this->createMock(PDO::class);

        $interfaces = ConfiguracaoService::buscaInterfacesPagamento(
            $pdoMock,
            'CA',
            8001,
            1000
        );

        $this->assertEquals([PagamentoCartaoCielo::class], $interfaces);
    }

    public function testDeveSerCieloCasoValorParcelaSejaMenorQue5()
    {
        $pdoMock = $this->createMock(PDO::class);

        $interfaces = ConfiguracaoService::buscaInterfacesPagamento(
            $pdoMock,
            'CA',
            40,
            4
        );

        $this->assertEquals([PagamentoCartaoCielo::class], $interfaces);
    }

    /**
     * @dataProvider dadosDeveSerAInterfacePagamento
     */
    public function testDeveConseguirLigarRegistroBancoDadosComInterfacePhp(
        array $informacoesMetodosPagamento,
        array $resultadoEsperado
    ) {
        # TODO: testar no VScode.
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'informacoes_metodos_pagamento' => json_encode(
                    $informacoesMetodosPagamento
                )
            ]);

        $pdoMock->expects($this->any())
            ->method('query')
            ->willReturn($stmtMock);

        $interfaces = ConfiguracaoService::buscaInterfacesPagamento(
            $pdoMock,
            $informacoesMetodosPagamento[0]['prefixo'],
            400,
            40
        );

        $this->assertEquals($resultadoEsperado, $interfaces);
    }

    public function dadosDeveSerAInterfacePagamento(): array
    {
        return [
            'pix_iugu_sozinha' => [
                [
                    [
                        'prefixo' => 'PX',
                        'nome' => 'Pix',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ]
                        ]
                    ]
                ],
                [PagamentoPixBoletoIugu::class]
            ],
            'pix_iugu_sozinha_e_zoop_desativada' => [
                [
                    [
                        'prefixo' => 'PX',
                        'nome' => 'Pix',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ],
                            [
                                'local_pagamento' => 'Zoop',
                                'situacao' => 'desativado'
                            ]
                        ]
                    ]
                ],
                [PagamentoPixBoletoIugu::class]
            ],
            'pix_iugu_principal_e_sicoob_secundaria' => [
                [
                    [
                        'prefixo' => 'PX',
                        'nome' => 'Pix',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ],
                            [
                                'local_pagamento' => 'Sicoob',
                                'situacao' => 'ativo'
                            ]
                        ]
                    ]
                ],
                [PagamentoPixBoletoIugu::class, PagamentoPixSicoob::class]
            ],
            'cartao_iugu_sozinha' => [
                [
                    [
                        'prefixo' => 'CA',
                        'nome' => 'Cartão',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ]
                        ]
                    ]
                ],
                [PagamentoCartaoIugu::class]
            ],
            'cartao_iugu_e_zoop_desativada' => [
                [
                    [
                        'prefixo' => 'CA',
                        'nome' => 'Cartão',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ],
                            [
                                'local_pagamento' => 'Zoop',
                                'situacao' => 'desativado'
                            ]
                        ]
                    ]
                ],
                [PagamentoCartaoIugu::class]
            ],
            'cartao_iugu_principal_e_zoop_secundaria' => [
                [
                    [
                        'prefixo' => 'CA',
                        'nome' => 'Cartão',
                        'meios_pagamento' => [
                            [
                                'local_pagamento' => 'Iugu',
                                'situacao' => 'ativo'
                            ],
                            [
                                'local_pagamento' => 'Zoop',
                                'situacao' => 'ativo'
                            ]
                        ]
                    ]
                ],
                [PagamentoCartaoIugu::class, PagamentoCartaoZoop::class]
            ]
        ];
    }
}
