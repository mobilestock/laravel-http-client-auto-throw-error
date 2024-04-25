<?php

use Dotenv\Exception\ValidationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Model;
use MobileStock\Shared\PdoInterceptor\Laravel\MysqlConnection;
use test\TestCase;

class GerarUsuarioMeulookTest extends TestCase
{
    public function testBuscaUsuarioMeulook(): void
    {
        $colaborador = new ColaboradorModel();
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->method('selectOneColumn')->willReturn('carmendaianerocha.55442');
        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManagerMock);

        $colaborador->buscaOuGeraUsuarioMeulook(55442);
        $this->assertEquals('carmendaianerocha.55442', $colaborador->usuario_meulook);
    }
    public function testGerarUsuarioMeulook(): void
    {
        $colaborador = new ColaboradorModel();
        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn', 'select', 'update']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('selectOneColumn')->willReturn(null);
        $connectionMock->method('select')->willReturn([
            [
                'id' => 37291,
                'regime' => 3,
                'cnpj' => null,
                'cpf' => null,
                'razao_social' => 'Graziele Aparecida Costa Caetano',
                'telefone' => '12345678901',
                'email' => '',
                'foto_perfil' => '',
                'id_tipo_entrega_padrao' => 0,
                'usuario_meulook' => null,
                'bloqueado_repor_estoque' => 'T',
                'nome_instagram' => '',
            ],
        ]);
        $connectionMock->method('update')->willReturn(1);
        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $colaborador->buscaOuGeraUsuarioMeulook(37291);
        $this->assertEquals('grazieleaparecidacostacaetano.37291', $colaborador->usuario_meulook);
    }
    public function listaUsuariosValidar()
    {
        return [
            ['grazieleaparecidacostacaetano.37291', true],
            ['carmendaianerocha.55442', true],
            ['DROP DATABASE', false],
            ['maldição', false],
            ['bencao', true],
            ['ALTER', true],
            ['DROP', true],
        ];
    }
    /**
     * @dataProvider listaUsuariosValidar
     */
    public function testValidadorUsuarioMeuLook(string $usuarioMeuLook, bool $deveSerValido): void
    {
        if (!$deveSerValido) {
            $this->expectException(ValidationException::class);
        }

        $colaborador = new ColaboradorModel();
        $colaborador->validaNomeUsuarioMeuLook($usuarioMeuLook);
        $this->assertTrue(true);
    }
}
