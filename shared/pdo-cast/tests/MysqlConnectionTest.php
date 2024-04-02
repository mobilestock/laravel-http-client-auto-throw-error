<?php

use Illuminate\Database\QueryException;
use MobileStock\PdoCast\laravel\MysqlConnection;

class MysqlConnectionTest extends PHPUnit\Framework\TestCase
{
    public function testErroSyntaxDeveLancarErroDoLaravel()
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->method('execute')
            ->willThrowException(
                new PDOException(
                    'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'SQL INVÁLIDO\' at line 1'
                )
            );
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->expectException(QueryException::class);
        $connection = new MysqlConnection($pdoMock);
        $connection->select('SQL INVÁLIDO');
    }

    public function testErroCustomizadoDeveLancarException()
    {
        $exceptionCustomizada = get_class(new class extends Exception {});

        $this->expectException($exceptionCustomizada);
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willThrowException(new $exceptionCustomizada());
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $connection = new MysqlConnection($pdoMock);
        $connection->select('INSERT INTO teste (nome) VALUES (?)', ['teste']);
    }
}
