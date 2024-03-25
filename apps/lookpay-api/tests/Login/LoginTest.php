<?php

use App\Http\Controllers\EstablishmentController;
use App\Models\Establishment;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{

    public function testLogin(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => 'random_ID',
                'token'=> 'top_10_token',
                'name' => 'test',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA'
            ]
        ]);

        $DatabaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $DatabaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($DatabaseManagerMock);

        $user = Establishment::authentication('6dc259f9-c505-11ee-94f1-0242ac120002', 'teste');
        $this->assertEquals($user, [
            'id' => 'random_ID',
            'token' => 'top_10_token',
            'name' => 'test',
            'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA'
        ]);
    }

    public function testErrorLogin(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'token'=> 'top_10_token',
                'name' => 'teste',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA'
            ]
        ]);

        $DatabaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $DatabaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($DatabaseManagerMock);

        $user = Establishment::authentication('6dc259f9-c505-11ee-94f1-0242ac120002', 'INCORRECT PASSWORD');
        $this->assertEmpty($user);
    }

    public function testAnyUserFound(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([]);

        $DatabaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $DatabaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($DatabaseManagerMock);

        $establishmentController = new EstablishmentController();
        $users = $establishmentController->searchUser('00000000000');

        $this->assertEquals($users, []);
    }

    public function testUserFound(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'token'=> 'top_10_token',
                'name' => 'teste',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA'
            ]
        ]);

        $DatabaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $DatabaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($DatabaseManagerMock);

        $establishmentController = new EstablishmentController();
        $users = $establishmentController->searchUser('37999715058');

        $this->assertEquals($users, [
            [
                'id' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'token'=> 'top_10_token',
                'name' => 'teste',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA'
            ]
        ]);
    }
}
