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
        $bancosMonitorados = [env('DB_DATABASE_ADM_API'), env('DB_DATABASE_LOOKPAY'), env('DB_DATABASE_MED')];
        $tabelasMonitoradas = array_merge(
            ...array_map(
                fn(string $nomeBanco, array $tabelas) => array_map(
                    fn(string $tabela) => "$nomeBanco.$tabela",
                    $tabelas
                ),
                $bancosMonitorados,
                [['colaboradores', 'usuarios'], ['establishments'], ['usuarios', 'lojas']]
            )
        );

        $configuracoes = (new ConfigBuilder())
            ->withUser(env('DB_USERNAME'))
            ->withPassword(env('DB_PASSWORD'))
            ->withHost(env('DB_HOST'))
            ->withPort(env('DB_PORT'))
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V2,
                ConstEventType::WRITE_ROWS_EVENT_V2,
                ConstEventType::DELETE_ROWS_EVENT_V2,
            ])
            ->withSlaveId(9999)
            ->withDatabasesOnly($bancosMonitorados)
            ->withTablesOnly($tabelasMonitoradas)
            ->build();

        App::singleton(MySQLReplicationFactory::class, fn() => new MySQLReplicationFactory($configuracoes));
    }

    public function boot(): void
    {
    }
}
