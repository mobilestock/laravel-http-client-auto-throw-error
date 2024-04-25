<?php

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use MobileStock\database\PDO;
use PHPUnit\Framework\TestCase;

class PDOTest extends TestCase
{
    private PDO $pdo;
    private DispatcherContract $dispatcher;

    public function setUp(): void
    {
        $dispatcherMock = $this->createMock(Dispatcher::class);
        app()->singleton(DispatcherContract::class, fn() => $dispatcherMock);

        $pdoMock = $this->createPartialMock(
            PDO::class,
            ['__construct']
        );

        $this->pdo = $pdoMock;
        $this->dispatcher = $dispatcherMock;
    }

    public function testAfterCommitDeveDispararEvento(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('pdo.after_commit');

        $this->pdo->afterCommit();
    }

    public function testDepoisDispararEventoDeveLimparOsListeners(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('forget')
            ->with('pdo.after_commit');

        $this->pdo->afterCommit();
    }
}