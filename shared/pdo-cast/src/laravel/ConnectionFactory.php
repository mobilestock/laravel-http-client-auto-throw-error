<?php

namespace MobileStock\PdoCast\laravel;

use Closure;
use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use MobileStock\PdoCast\PdoCastStatement;
use PDO;

class ConnectionFactory extends BaseConnectionFactory
{
    protected function createPdoResolver(array $config): Closure
    {
        return function () {
            $conexao = app(PDO::class);

            $conexao->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PdoCastStatement::class]);
            return $conexao;
        };
    }
}