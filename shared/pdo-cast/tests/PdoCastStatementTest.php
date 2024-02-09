<?php

use Illuminate\Pipeline\Pipeline;
use MobileStock\PdoCast\PdoCastStatement;
use PHPUnit\Framework\TestCase;

class PdoCastStatementTest extends
    TestCase
{
    public function testPipelineDeveSerExecutada()
    {
        $pipeline = new Pipeline();
        $pipeline->through(function () {
            $this->assertEquals(1, 1);
            return 'teste';
        });

        $pdoCastStatement = new PdoCastStatement($pipeline);

        $this->assertEquals('teste', $pdoCastStatement->fetchAll());
    }

    public function testPipelineDeveFornecederDadosCorretosVindosDoPdo()
    {
        $stmt = new class {
            public function fetchAll()
            {
                return 'teste';
            }
        };

        $pipeline = new Pipeline();
        $pipeline->through(function (array $dados) use ($stmt) {
            $this->assertEquals('fetchAll', $dados['stmt_method']);
            $this->assertEquals('teste', $dados['result']);
            $this->assertEquals($stmt, $dados['stmt']);
        });

        $pdoCastStatement = new PdoCastStatement($pipeline);
        $pdoCastStatement->parent = $stmt;

        $pdoCastStatement->fetchAll();
    }
}
