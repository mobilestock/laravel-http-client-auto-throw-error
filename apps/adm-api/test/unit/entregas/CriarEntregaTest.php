<?php

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use MobileStock\model\Entrega;
use MobileStock\model\Model;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class CriarEntregaTest extends test\TestCase
{
    public function testBloquearCriarEntregaPontoMovelSemIdRaio(): void
    {
        // Configuração da exceção esperada
        $this->expectException(BadRequestException::class);

        // Mock da conexão de banco de dados
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOne']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn([
            'id' => 448,
            'tipo_ponto' => 'PM',
        ]);

        // Mock do DatabaseManager
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        // Swap do DatabaseManager
        DB::swap($databaseManangerMock);

        // Executa o código sob teste
        EntregaServices::criaEntregaOuMesclaComEntregaExistente(1932, 448, 1, null, []);
    }

    public function listaDeRaiosRetornados(): array
    {
        return [
            '2 Raios' => [[20411, 20281]],
            'Nenhum Raio' => [[]],
            'Raios Inválidos' => [[null, null]],
            '2 Raios e 1 Raio Inválido' => [[null, 20281, 20282]],
        ];
    }
    /**
     * @dataProvider listaDeRaiosRetornados
     */
    public function testBloquearCriarEntregaPontoMovelRaiosQuantidadeErradaDeRaios(array $raiosRetornados): void
    {
        // Configuração da exceção esperada
        $this->expectException(BadRequestException::class);

        // Mock da conexão de banco de dados
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectColumns', 'selectOne']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn([
            'id' => 448,
            'tipo_ponto' => 'PM',
        ]);
        $connectionMock->method('selectColumns')->willReturn($raiosRetornados);

        // Mock do DatabaseManager
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        // Swap do DatabaseManager
        DB::swap($databaseManangerMock);

        // Executa o código sob teste
        EntregaServices::criaEntregaOuMesclaComEntregaExistente(1932, 448, 1, 1, [
            '1932_113745269265b951b5256e83.95106896',
            '1932_113745269265b951b5256e83.95106896',
        ]);
    }

    public function testCriarUmaNovaEntrega(): void
    {
        // Mock do PDO
        $pdoMock = $this->createMock(PDO::class);
        app()->bind(PDO::class, fn() => $pdoMock);

        // Mock da conexão de banco de dados
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOne', 'selectOneColumn', 'insert']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn([
            'id' => 440,
            'tipo_ponto' => 'PP',
        ]);
        $connectionMock->method('selectOneColumn')->willReturn(0);
        $connectionMock->method('insert')->willReturn(['id' => 666]);

        // Mock do DatabaseManager
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        // Mock do EntregaServices
        $serviceMock = $this->createPartialMock(EntregaServices::class, ['recalculaEtiquetas']);
        $serviceMock->expects($this->once())->method('recalculaEtiquetas');
        app()->bind(EntregaServices::class, fn() => $serviceMock);

        // Swap do DatabaseManager
        DB::swap($databaseManangerMock);
        Model::setConnectionResolver(app('db'));

        // Configuração do evento Entrega::saving
        Entrega::saving(function (Entrega $entregaModel) {
            $entregaModel->id = 666;
            return false;
        });

        // Executa o código sob teste
        $idDeEntrega = EntregaServices::criaEntregaOuMesclaComEntregaExistente(1932, 440, 1, 1, []);

        $this->assertEquals($idDeEntrega, 666);
    }
    public function testAtualizarEntregaSemEnviarProdutos(): void
    {
        // Mock do PDO
        $pdoMock = $this->createMock(PDO::class);
        app()->bind(PDO::class, fn() => $pdoMock);

        // Mock da conexão de banco de dados
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOne', 'selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn([
            'id' => 440,
            'tipo_ponto' => 'PP',
        ]);
        $connectionMock->method('selectOneColumn')->willReturn(42);

        // Mock do DatabaseManager
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        // Mock do EntregaServices
        $serviceMock = $this->createPartialMock(EntregaServices::class, ['recalculaEtiquetas']);
        $serviceMock->expects($this->once())->method('recalculaEtiquetas');
        app()->bind(EntregaServices::class, fn() => $serviceMock);

        // Swap do DatabaseManager
        DB::swap($databaseManangerMock);
        Model::setConnectionResolver(app('db'));

        // Executa o código sob teste
        $idDeEntrega = EntregaServices::criaEntregaOuMesclaComEntregaExistente(1932, 440, 1, 1, []);

        $this->assertEquals($idDeEntrega, 42);
    }
    public function testAtualizarEntregaEnviandoProdutos(): void
    {
        // Mock do PDO
        $pdoMock = $this->createMock(PDO::class);
        app()->bind(PDO::class, fn() => $pdoMock);

        // Mock da conexão de banco de dados
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOne', 'selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOne')->willReturn([
            'id' => 440,
            'tipo_ponto' => 'PP',
        ]);
        $connectionMock->method('selectOneColumn')->willReturn(42);

        $entregasItemMock = $this->createPartialMock(EntregasFaturamentoItemService::class, ['cria']);
        app()->bind(EntregasFaturamentoItemService::class, fn() => $entregasItemMock);

        // Mock do DatabaseManager
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);

        // Mock do EntregaServices
        $serviceMock = $this->createPartialMock(EntregaServices::class, ['recalculaEtiquetas']);
        $serviceMock->expects($this->once())->method('recalculaEtiquetas');
        app()->bind(EntregaServices::class, fn() => $serviceMock);

        // Swap do DatabaseManager
        DB::swap($databaseManangerMock);
        Model::setConnectionResolver(app('db'));

        // Executa o código sob teste
        $idDeEntrega = EntregaServices::criaEntregaOuMesclaComEntregaExistente(1932, 440, 1, 1, [
            '1932_113745269265b951b5256e83.95106896',
            '1932_113745269265b951b5256e83.95106896',
        ]);

        $this->assertEquals($idDeEntrega, 42);
    }
}
