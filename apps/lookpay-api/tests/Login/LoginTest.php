<?php

use App\Http\Controllers\EstablishmentController;
use App\Models\Establishment;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoginTest extends TestCase
{
    public function testLogin(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => 'random_ID',
                'token' => 'top_10_token',
                'name' => 'test',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA',
            ],
        ]);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        $user = Establishment::authentication('6dc259f9-c505-11ee-94f1-0242ac120002', 'teste');
        $this->assertEquals($user, [
            'id' => 'random_ID',
            'token' => 'top_10_token',
            'name' => 'test',
            'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA',
        ]);
    }

    public function testErrorLogin(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'token' => 'top_10_token',
                'name' => 'teste',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA',
            ],
        ]);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        $user = Establishment::authentication('6dc259f9-c505-11ee-94f1-0242ac120002', 'INCORRECT PASSWORD');
        $this->assertEmpty($user);
    }

    public function testNoUserFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([]);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        $establishmentController = new EstablishmentController();
        $establishmentController->getEstablishmentsByPhoneNumber();
    }

    public function testRemoveAnyNoNumberCharacthers()
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock
            ->expects($this->once())
            ->method('select')
            ->with($this->anything(), ['phone_number' => '00000000000'])
            ->willReturn([['id' => 'random_ID', 'name' => 'test']]);
        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        Request::merge(['phone_number' => '000.000.000-00']);
        $establishmentController = new EstablishmentController();
        $establishmentController->getEstablishmentsByPhoneNumber();
    }
}
