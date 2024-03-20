<?php

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use MobileStock\PdoCast\laravel\MysqlConnection;
use test\TestCase;

class GetLockTest extends TestCase
{
    public function provedorDeDadosGeral(): array
    {
        return [
            'Com identificador' => ['teste'],
            'Sem identificador' => [null],
        ];
    }
    /**
     * @dataProvider provedorDeDadosGeral
     */
    public function testErroPorEstarDentroDeUmaTransaction(?string $identificador): void
    {
        $this->expectException(RuntimeException::class);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        DB::setPdo($pdoMock);

        if ($identificador) {
            DB::getLock($identificador);
        } else {
            DB::getLock();
        }
    }
    /**
     * @dataProvider provedorDeDadosGeral
     */
    public function testUsarGetLockNormalmente(?string $identificador): void
    {
        $identificadores = json_encode($identificador ? [$identificador] : []);
        $rota = __DIR__ . '/GetLockTest.php::GetLockTest->testUsarGetLockNormalmente(["getLock",';
        $rota .= "$identificadores])->getLock($identificadores)";
        $hashBacktrace = sha1($rota);

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock
            ->method('selectOneColumn')
            ->with('SELECT GET_LOCK(:lock_id, 99999);', ['lock_id' => $hashBacktrace])
            ->willReturn(1);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        if ($identificador) {
            DB::getLock($identificador);
        } else {
            DB::getLock();
        }

        $this->assertTrue(true);
    }
    /**
     * @dataProvider provedorDeDadosGeral
     */
    public function testUsarGetLockDentroDaFuncao(?string $identificador): void
    {
        $identificadores = json_encode($identificador ? [$identificador] : []);
        $rota = __DIR__ . '/GetLockTest.php::funcao(["getLock",';
        $rota .= "$identificadores])->getLock($identificadores)";
        $hashBacktrace = sha1($rota);

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock
            ->method('selectOneColumn')
            ->with('SELECT GET_LOCK(:lock_id, 99999);', ['lock_id' => $hashBacktrace])
            ->willReturn(1);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);
        if (!function_exists('funcao')) {
            function funcao(?string $identificador): void
            {
                if ($identificador) {
                    DB::getLock($identificador);
                } else {
                    DB::getLock();
                }
            }
        }

        funcao($identificador);
        $this->assertTrue(true);
    }
    /**
     * @dataProvider provedorDeDadosGeral
     */
    public function testUsarGetLockDentroDaFuncaoAnonima(?string $identificador): void
    {
        $identificadores = json_encode($identificador ? [$identificador] : []);
        $rota = __DIR__ . '/GetLockTest.php::GetLockTest->{closure}(["getLock",';
        $rota .= "$identificadores])->getLock($identificadores)";
        $hashBacktrace = sha1($rota);

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock
            ->method('selectOneColumn')
            ->with('SELECT GET_LOCK(:lock_id, 99999);', ['lock_id' => $hashBacktrace])
            ->willReturn(1);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);
        $funcaoAnonima = function (?string $identificador): void {
            if ($identificador) {
                DB::getLock($identificador);
            } else {
                DB::getLock();
            }
        };

        $funcaoAnonima($identificador);
        $this->assertTrue(true);
    }
    /**
     * @dataProvider provedorDeDadosGeral
     */
    public function testUsarGetLockDentroDaClasseAnonima(?string $identificador): void
    {
        $novaClasse = new class {
            public function funcao(?string $identificador): void
            {
                if ($identificador) {
                    DB::getLock($identificador);
                } else {
                    DB::getLock();
                }
            }
        };
        $identificadores = json_encode($identificador ? [$identificador] : []);
        $nomeClasse = get_class($novaClasse);
        $rota = __DIR__ . "/GetLockTest.php::$nomeClasse";
        $rota .= '->funcao(["getLock",';
        $rota .= "$identificadores])->getLock($identificadores)";
        $hashBacktrace = sha1($rota);

        $connectionMock = $this->createPartialMock(MysqlConnection::class, ['selectOneColumn']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock
            ->method('selectOneColumn')
            ->with('SELECT GET_LOCK(:lock_id, 99999);', ['lock_id' => $hashBacktrace])
            ->willReturn(1);
        $databaseManangerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManangerMock->method('connection')->willReturn($connectionMock);
        DB::swap($databaseManangerMock);

        $novaClasse->funcao($identificador);
        $this->assertTrue(true);
    }
}
