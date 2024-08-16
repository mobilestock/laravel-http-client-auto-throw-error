<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

class MonitorAlteracoesColaborador extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-alteracoes-colaborador';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor de alterações de colaborador';

    public function handle(MySQLReplicationFactory $replicacao): void
    {
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $evento): void
            {
                echo $evento->tableMap->database;
                echo PHP_EOL;
                echo $evento;
                echo PHP_EOL . 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
            }
        };
        var_dump('COMEÇOU ' . (new Carbon('NOW'))->format('Y-m-d H:i:s'));
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
