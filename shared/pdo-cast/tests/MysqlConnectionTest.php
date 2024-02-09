<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use MobileStock\PdoCast\PDOExceptionDeadlock;

class MysqlConnectionTest extends \PHPUnit\Framework\TestCase
{
    public function testErroSyntaxDeveLancarErroDoLaravel()
    {
        $this->expectException(PDOExceptionDeadlock::class);
        $conexaoMock = $this->createMock(PDO::class);
        $conexaoMock->method('prepare')->willReturn(new class extends PDOStatement {
            public function execute($params = null)
            {
                throw new PDOException('SQLSTATE[HY000]: General error: 1 near "SQL": syntax error');
            }
        });
        $this->expectException(QueryException::class);
        App::bind(PDO::class, fn() => $conexaoMock);
        DB::select('SQL INVÁLIDO');
    }

    public function testErroDeadlockDeveLancarException()
    {
        $this->expectException(PDOExceptionDeadlock::class);
        $conexaoMock = $this->createMock(PDO::class);
        $conexaoMock->method('prepare')->willReturn(
            new class extends PDOStatement {
                public function execute($params = null)
                {
                    throw new PDOExceptionDeadlock();
                }
            }
        );
        App::bind(PDO::class, fn() => $conexaoMock);
        DB::insert('INSERT INTO teste (nome) VALUES (?)', ['teste']);
    }
}
