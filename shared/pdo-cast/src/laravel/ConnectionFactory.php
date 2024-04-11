<?php

namespace MobileStock\PdoCast\laravel;

use Closure;
use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use MobileStock\PdoCast\StatementUtils;
use PDO;

class ConnectionFactory extends BaseConnectionFactory
{
    protected function createPdoResolver(array $config): Closure
    {
        return function () use ($config) {
            if (App::bound(PDO::class)) {
                $conexao = app(PDO::class);
            } else {
                $conexao = parent::createPdoResolver($config)();
            }

            $pdoStatement = StatementUtils::getStatementClass();

            $conexao->setAttribute(PDO::ATTR_STATEMENT_CLASS, [
                $pdoStatement,
                [app(Pipeline::class)->through(app()['config']['pdo-cast.middlewares']())],
            ]);

            return $conexao;
        };
    }
}
