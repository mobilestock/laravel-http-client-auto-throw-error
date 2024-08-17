<?php

namespace MobileStock\helper\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\MySQLReplicationFactory;

class MysqlReplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $builder = new ConfigBuilder();
        $builder
            ->withPort(3306)
            ->withHost(env('MYSQL_HOST'))
            ->withUser(env('MYSQL_USER_COLABORADOR_CENTRAL'))
            ->withPassword(env('MYSQL_PASSWORD_COLABORADOR_CENTRAL'))
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V1,
                ConstEventType::WRITE_ROWS_EVENT_V1,
                ConstEventType::DELETE_ROWS_EVENT_V1,
            ])
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), env('MYSQL_DB_NAME_LOOKPAY'), env('MYSQL_DB_NAME_MED')])
            ->withTablesOnly(['colaboradores', 'usuarios', 'lojas', 'establishments']);

        /**
         * TODO: Mudar pra salvar nos Redis
         */
        $ultimaAlteracao = DB::selectOne(
            "SELECT
                ultima_alteracao_sincronizada.posicao,
                ultima_alteracao_sincronizada.arquivo
            FROM ultima_alteracao_sincronizada
            ORDER BY ultima_alteracao_sincronizada.id DESC
            LIMIT 1;"
        );
        if (!empty($ultimaAlteracao)) {
            $builder->withBinLogFileName($ultimaAlteracao['arquivo'])->withBinLogPosition($ultimaAlteracao['posicao']);
        }

        $configuracoes = $builder->build();
        App::singleton(MySQLReplicationFactory::class, fn() => new MySQLReplicationFactory($configuracoes));
    }

    public function boot(): void
    {
    }
}
