<?php

namespace MobileStock\Shared\PdoInterceptor\Laravel;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Event;
use PDO;

class PdoInterceptorServiceProvider extends DatabaseServiceProvider
{
    protected function registerConnectionServices()
    {
        $this->app->alias(ConnectionFactory::class, 'db.factory');

        $this->app->singleton('db', function ($app): DatabaseManager {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app): Connection {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app): Builder {
            return $app['db']->connection()->getSchemaBuilder();
        });

        $this->app->singleton('db.transactions', function (): DatabaseTransactionsManager {
            return new DatabaseTransactionsManager();
        });

        Connection::resolverFor('mysql', function (
            $connection,
            string $database,
            string $prefix,
            array $config
        ): MysqlConnection {
            return new MysqlConnection($connection, $database, $prefix, $config);
        });

        Event::listen(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_ASSOC);
        });
    }
}
