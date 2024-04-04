<?php

use Illuminate\Pipeline\Pipeline;

class PdoCastStatementTest extends TestCase
{
    // public function testPipelineDeveSerExecutada()
    // {
    //     $pipeline = new Pipeline();
    //     $pipeline->through(function () {
    //         $this->assertEquals(1, 1);
    //         return ['teste'];
    //     });

    //     $pdoCastStatement = parent::getStmt($pipeline);

    //     $this->assertEquals(['teste'], $pdoCastStatement->fetchAll());
    // }

    public function testPipelineDeveFornecederDadosCorretosVindosDoPdo()
    {
        $stmt = new class {
            public function fetchAll()
            {
                return ['teste'];
            }
        };

        $pipeline = new Pipeline();
        $pipeline->through(function (array $dados, Closure $next) {
            $this->assertEquals('fetchAll', $dados['stmt_method']);

            $resultado = $next($dados);

            $this->assertEquals(['teste'], $resultado);
            return $resultado;
        });

        $pdoCastStatement = parent::getStmt($pipeline);
        $pdoCastStatement->parent = $stmt;

        $pdoCastStatement->fetchAll();
    }
}
