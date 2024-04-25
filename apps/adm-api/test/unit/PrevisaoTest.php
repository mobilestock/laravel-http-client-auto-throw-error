<?php

use Illuminate\Support\Carbon;
use MobileStock\service\DiaUtilService;
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
    protected function setUp(): void
    {
        parent::setUp();
        app()->bind(PDO::class, fn() => $this->createMock(PDO::class));
        app()->bind(CacheInterface::class, fn() => new NullAdapter());
    }
    public function testBuscaHorarioAcompanhamentoTrazOHorarioMaisProximo(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(json_encode(['08:00', '14:00']));
        $pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

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
}
