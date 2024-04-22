<?php

use App\Http\Controllers\EstablishmentController;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function testShouldLogin(): void
    {
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => 'e97738f1-2f60-4da0-a0f0-63c245db70cb',
                'token' => 'top_10_token',
                'name' => 'test',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA',
            ],
        ]);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        Request::merge(['establishment_id' => 'e97738f1-2f60-4da0-a0f0-63c245db70cb', 'password' => 'teste']);
        $establishmentController = new EstablishmentController();
        $establishmentController->login();

        $this->assertTrue(true);
    }

    public function testShouldErrorLogin(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($this->createMock(PDO::class));
        $connectionMock->method('select')->willReturn([
            [
                'id' => 'e97738f1-2f60-4da0-a0f0-63c245db70cb',
                'token' => 'top_10_token',
                'name' => 'teste',
                'password' => '$argon2id$v=19$m=16,t=2,p=1$bHZ2WFViUk1SRUUwbmtzRw$uqJEIhuGqH0BGdJtfaFRWA',
            ],
        ]);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        DB::swap($databaseManagerMock);

        Request::merge([
            'establishment_id' => 'e97738f1-2f60-4da0-a0f0-63c245db70cb',
            'password' => 'INCORRECT PASSWORD',
        ]);

        $establishmentController = new EstablishmentController();
        $establishmentController->login();
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

        Request::merge([
            'phone_number' => '00000000000',
        ]);

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
