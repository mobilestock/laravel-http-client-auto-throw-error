<?php

use Illuminate\Support\Carbon;
use MobileStock\service\DiaUtilService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use test\TestCase;

class PrevisaoTest extends TestCase
{
    private const MOCK_DIAS_UTEIS = [
        '2023-12-06',
        '2023-12-07',
        '2023-12-08',
        '2023-12-11',
        '2023-12-12',
        '2023-12-13',
        '2023-12-14',
        '2023-12-15',
        '2023-12-18',
        '2023-12-19',
        '2023-12-20',
        '2023-12-21',
        '2023-12-22',
        '2023-12-26',
        '2023-12-27',
        '2023-12-28',
        '2023-12-29',
        '2024-01-02',
        '2024-01-03',
        '2024-01-04',
        '2024-01-05',
    ];
    private const MOCK_AGENDA_PONTO_COLETA = [
        ['id' => 16, 'dia' => 'SEGUNDA', 'horario' => '14:00', 'frequencia' => 'RECORRENTE'],
        ['id' => 17, 'dia' => 'TERCA', 'horario' => '14:00', 'frequencia' => 'RECORRENTE'],
        ['id' => 18, 'dia' => 'QUARTA', 'horario' => '14:00', 'frequencia' => 'RECORRENTE'],
        ['id' => 20, 'dia' => 'QUINTA', 'horario' => '14:00', 'frequencia' => 'RECORRENTE'],
        ['id' => 21, 'dia' => 'SEXTA', 'horario' => '14:00', 'frequencia' => 'RECORRENTE'],
    ];

    private const MOCK_DIAS_PROCESSO_ENTREGA = [
        'dias_entregar_cliente' => 1,
        'dias_pedido_chegar' => 1,
        'dias_margem_erro' => 2,
    ];
    protected function setUp(): void
    {
        parent::setUp();
        app()->bind(PDO::class, fn() => $this->createMock(PDO::class));
        app()->bind(CacheInterface::class, fn() => new NullAdapter());
        $_ENV['AMBIENTE'] = 'producao';
    }
    public function testBuscaHorarioAcompanhamentoTrazOHorarioMaisProximo(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(json_encode(['08:00', '14:00']));
        $pdoMock->expects($this->once())->method('prepare')->willReturn($stmtMock);

        app()->bind(PDO::class, fn() => $pdoMock);
        app()->bind(CacheInterface::class, fn() => new NullAdapter());
        $previsao = app(PrevisaoService::class);
        $previsao->data = Carbon::createFromFormat('H:i', '10:00');
        $retorno = $previsao->buscaHorarioSeparando();

        $this->assertEquals('08:00', $retorno);
    }
    public function testTimezoneEhValido(): void
    {
        app()->bind(CacheInterface::class, fn() => new NullAdapter());
        $previsao = app(PrevisaoService::class);
        $this->assertEquals('America/Sao_Paulo', $previsao->data->getTimezone()->getName());
    }
    public function datasTesteDiasUteis()
    {
        return [
            ['2023-12-06', 1, '2023-12-07'],
            ['2023-12-06', 5, '2023-12-13'],
            ['2023-12-08', 17, '2024-01-04'],
            ['2023-12-08', 20, '2024-01-09'],
        ];
    }
    /**
     * @dataProvider datasTesteDiasUteis
     */
    public function testEncontrarDiaUtil(string $dataInicio, int $qtdDiasUteis, string $dataFinal): void
    {
        $diaUtilMock = $this->createMock(DiaUtilService::class);
        $diaUtilMock
            ->expects($this->once())
            ->method('buscaCacheProximosDiasUteis')
            ->willReturn(self::MOCK_DIAS_UTEIS);

        $diaUtilMock
            ->expects($this->any())
            ->method('buscaProximosDiasUteis')
            ->willReturn([
                '2024-01-08',
                '2024-01-09',
                '2024-01-10',
                '2024-01-11',
                '2024-01-12',
                '2024-01-15',
                '2024-01-16',
                '2024-01-17',
                '2024-01-18',
                '2024-01-19',
                '2024-01-22',
                '2024-01-23',
                '2024-01-24',
                '2024-01-25',
                '2024-01-26',
                '2024-01-29',
                '2024-01-30',
                '2024-01-31',
                '2024-02-01',
                '2024-02-02',
            ]);
        app()->bind(DiaUtilService::class, fn() => $diaUtilMock);
        $dataCalculo = Carbon::createFromFormat('Y-m-d', $dataInicio);
        $dataCalculo->acrescentaDiasUteis($qtdDiasUteis);
        $this->assertEquals($dataFinal, $dataCalculo->format('Y-m-d'));
    }
    public function testErroEncontrarDiaUtil(): void
    {
        $this->expectException(DomainException::class);
        $diaUtilMock = $this->createMock(DiaUtilService::class);
        $diaUtilMock
            ->expects($this->once())
            ->method('buscaCacheProximosDiasUteis')
            ->willReturn(self::MOCK_DIAS_UTEIS);
        app()->bind(DiaUtilService::class, fn() => $diaUtilMock);
        $dataErrada = Carbon::createFromFormat('Y-m-d', '2023-12-06');
        $dataErrada->acrescentaDiasUteis(DiaUtilService::LIMITE_DIAS_CALCULOS * 5);
    }

    public function providerRetornoDeCalculosPrevisao(): array
    {
        $previsaoFulfillment = [
            'dias_minimo' => 2,
            'dias_maximo' => 4,
            'media_previsao_inicial' => '08/12/2023',
            'media_previsao_final' => '12/12/2023',
            'responsavel' => 'FULFILLMENT',
            'data_limite' => '06/12/2023 às 14:00',
        ];

        $previsaoExterno = [
            'dias_minimo' => 5,
            'dias_maximo' => 7,
            'media_previsao_inicial' => '13/12/2023',
            'media_previsao_final' => '15/12/2023',
            'responsavel' => 'EXTERNO',
            'data_limite' => '06/12/2023 às 14:00',
        ];

        return [
            'Testando produto que é fulfillment' => [
                [1],
                [
                    'FULFILLMENT' => 0,
                    'EXTERNO' => null,
                ],
                [$previsaoFulfillment],
                [
                    [
                        'id' => 999,
                        'previsoes' => [$previsaoFulfillment],
                    ],
                ],
            ],
            'Testando produto que é externo' => [
                [69],
                [
                    'FULFILLMENT' => null,
                    'EXTERNO' => 3,
                ],
                [$previsaoExterno],
                [
                    [
                        'id' => 999,
                        'previsoes' => [$previsaoExterno],
                    ],
                ],
            ],
            'Testando produto de ambos' => [
                [1, 69],
                [
                    'FULFILLMENT' => 0,
                    'EXTERNO' => 3,
                ],
                [$previsaoFulfillment, $previsaoExterno],
                [
                    [
                        'id' => 999,
                        'previsoes' => [$previsaoFulfillment, $previsaoExterno],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerRetornoDeCalculosPrevisao
     */
    public function testCalculosDosDiasSeparacaoProduto(array $responsaveisEstoque, array $resultadoEsperado): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn($responsaveisEstoque);
        $stmtMock->method('fetchColumn')->willReturn(3);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        app()->bind(PDO::class, fn() => $pdoMock);

        $retorno = app(PrevisaoService::class)->calculoDiasSeparacaoProduto(15, 'Unico');
        $this->assertEquals($resultadoEsperado, $retorno);
    }

    public function testCalcularProximoDiaDeEnvioDoPontoColeta(): void
    {
        $agenda = self::MOCK_AGENDA_PONTO_COLETA;

        $dataEnvio = Carbon::createFromFormat('Y-m-d H:i:s', '2023-12-06 08:46:00');
        Carbon::setTestNow($dataEnvio);
        $mockDiasUteis = $this->createPartialMock(DiaUtilService::class, ['buscaCacheProximosDiasUteis']);
        $mockDiasUteis
            ->expects($this->once())
            ->method('buscaCacheProximosDiasUteis')
            ->willReturn(self::MOCK_DIAS_UTEIS);
        app()->bind(DiaUtilService::class, fn() => $mockDiasUteis);

        $previsao = app(PrevisaoService::class);
        $retorno = $previsao->calculaProximaData($agenda);

        $this->assertEquals(
            [
                'qtd_dias_enviar' => 0,
                'data_envio' => $dataEnvio->format('d/m/Y'),
                'horarios_disponiveis' => [
                    2 => [
                        'id' => 18,
                        'dia' => 'QUARTA',
                        'horario' => '14:00',
                        'frequencia' => 'RECORRENTE',
                    ],
                ],
            ],
            $retorno
        );
    }

    /**
     * @dataProvider providerRetornoDeCalculosPrevisao
     */
    public function testCalcularPorMediasEDias(array $ignorar, array $mediasEnvio, array $resultadoEsperado): void
    {
        $agenda = self::MOCK_AGENDA_PONTO_COLETA;

        $dataEnvio = Carbon::createFromFormat('Y-m-d H:i:s', '2023-12-06 08:46:00');
        Carbon::setTestNow($dataEnvio);

        $diaUtilMock = $this->createMock(DiaUtilService::class);
        $diaUtilMock
            ->expects($this->any())
            ->method('buscaCacheProximosDiasUteis')
            ->willReturn(self::MOCK_DIAS_UTEIS);
        app()->bind(DiaUtilService::class, fn() => $diaUtilMock);

        $previsaoMock = $this->createPartialMock(PrevisaoService::class, ['calculaProximaData']);
        $previsaoMock->__construct(app(PDO::class), $diaUtilMock);
        $previsaoMock
            ->expects($this->once())
            ->method('calculaProximaData')
            ->willReturn([
                'qtd_dias_enviar' => 0,
                'data_envio' => $dataEnvio->format('d/m/Y'),
                'horarios_disponiveis' => [
                    2 => [
                        'id' => 18,
                        'dia' => 'QUARTA',
                        'horario' => '14:00',
                        'frequencia' => 'RECORRENTE',
                    ],
                ],
            ]);
        app()->bind(PrevisaoService::class, fn() => $previsaoMock);

        $retorno = app(PrevisaoService::class)->calculaPorMediasEDias(
            $mediasEnvio,
            self::MOCK_DIAS_PROCESSO_ENTREGA,
            $agenda
        );

        $this->assertEquals($resultadoEsperado, $retorno);
    }

    /**
     * @dataProvider providerRetornoDeCalculosPrevisao
     */
    public function testProcessoCalcularPrevisao(
        array $ignorar,
        array $mediasEnvio,
        array $previsoes,
        array $resultadoEsperado
    ): void {
        $agendaServiceMock = $this->createMock(PontosColetaAgendaAcompanhamentoService::class);
        $agendaServiceMock->method('buscaPrazosPorPontoColeta')->willReturn([
            'agenda' => self::MOCK_AGENDA_PONTO_COLETA,
            'dias_pedido_chegar' => 1,
        ]);

        app()->bind(PontosColetaAgendaAcompanhamentoService::class, fn() => $agendaServiceMock);

        $previsaoServiceMock = $this->createPartialMock(PrevisaoService::class, [
            'calculoDiasSeparacaoProduto',
            'calculaPorMediasEDias',
        ]);
        $previsaoServiceMock->method('calculoDiasSeparacaoProduto')->willReturn($mediasEnvio);
        $previsaoServiceMock->method('calculaPorMediasEDias')->willReturn($previsoes);

        $produtoFulfillment = [['id' => 999]];
        $diasProcessoEntrega = self::MOCK_DIAS_PROCESSO_ENTREGA;

        $resultado = $previsaoServiceMock->processoCalcularPrevisoes(1, $diasProcessoEntrega, $produtoFulfillment);

        $this->assertEquals($resultadoEsperado, $resultado);
    }
}
