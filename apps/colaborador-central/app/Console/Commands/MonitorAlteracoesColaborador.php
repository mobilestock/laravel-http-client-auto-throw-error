<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
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

    public function handle()
    {
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $event): void
            {
                echo PHP_EOL;
                echo $event;
                echo PHP_EOL;
                echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;

                $foo = $event->jsonSerialize();
                Route::get("http://192.168.0.159:8008/api_cliente/teste/{$foo}");
            }
        };
        var_dump('COMEÇOU ' . (new Carbon('NOW'))->format('Y-m-d H:i:s'));
        $replicacao = app(MySQLReplicationFactory::class);
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
