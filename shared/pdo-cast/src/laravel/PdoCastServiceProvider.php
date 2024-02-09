<?php

namespace MobileStock\PdoCast\laravel;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class PdoCastServiceProvider extends ServiceProvider
{
    public function boot()
    {
        App::singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        Connection::resolverFor('mysql', function ($connection, string $database, string $prefix, array $config) {
            return new MysqlConnection($connection, $database, $prefix, $config);
        });
    }
}
