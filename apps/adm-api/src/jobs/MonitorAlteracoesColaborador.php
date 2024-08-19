<?php

namespace MobileStock\jobs;

use Illuminate\Support\Carbon;
use MobileStock\jobs\config\AbstractJob;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $configuracoes = (new ConfigBuilder())
            ->withPort(3306)
            ->withHost(env('MYSQL_HOST'))
            ->withUser('COLABORADOR_CENTRAL')
            ->withPassword('COLABORADOR_CENTRAL')
            ->withEventsOnly([
                ConstEventType::UPDATE_ROWS_EVENT_V1,
                ConstEventType::WRITE_ROWS_EVENT_V1,
                ConstEventType::DELETE_ROWS_EVENT_V1,
            ])
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), 'lookpay-api', 'med'])
            ->withTablesOnly(['colaboradores', 'establishments', 'lojas', 'usuarios'])
            ->build();

        $replicacao = new MySQLReplicationFactory($configuracoes);
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $evento): void
            {
                echo 'MAPA TABELAS: ' . json_encode($evento->getTableMap(), JSON_PRETTY_PRINT) . PHP_EOL;
                echo $evento;
                echo PHP_EOL;
                echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
            }
        };

        var_dump('COMEÇOU ' . (new Carbon('NOW'))->format('Y-m-d H:i:s'));
        $replicacao->registerSubscriber($registro);
        $replicacao->run();
    }
};
