<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\MySQLReplicationFactory;

class MysqlReplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configuracoes = (new ConfigBuilder())
            ->withUser(env('DB_USERNAME'))
            ->withPassword(env('DB_PASSWORD'))
            ->withHost(env('DB_HOST'))
            ->withPort(env('DB_PORT'))
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V1->value,
                ConstEventType::WRITE_ROWS_EVENT_V1->value,
                ConstEventType::DELETE_ROWS_EVENT_V1->value,
            ])
            ->withDatabasesOnly([env('DB_DATABASE_ADM_API'), env('DB_DATABASE_LOOKPAY'), env('DB_DATABASE_MED')])
            ->withTablesOnly(['colaboradores', 'establishments', 'lojas', 'usuarios'])
            ->build();

        App::singleton(MySQLReplicationFactory::class, fn() => new MySQLReplicationFactory($configuracoes));
    }

    public function boot(): void
    {
    }
}
