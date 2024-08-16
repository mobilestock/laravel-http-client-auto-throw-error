<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

class MonitorAlteracoesColaborador extends Command
{
    protected $signature = 'app:monitor-alteracoes-colaborador';
    protected $description = 'Monitora alterações de colaboradores';

    public function handle(MySQLReplicationFactory $replicacao): void
    {
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $evento): void
            {
                $arquivo = DB::selectOneColumn('SHOW MASTER STATUS;');
                echo "Arquivo: $arquivo" . PHP_EOL;
                echo $evento->getType() . PHP_EOL;
                echo $evento->getTableMap()->getDatabase();
                echo PHP_EOL;
                echo $evento;
                echo PHP_EOL . 'Uso de memória ' . round(memory_get_usage() / 1048576, 2);
            }
        };

        $data = new Carbon('NOW');
        $data->setTimezone('America/Sao_Paulo');
        var_dump("COMEÇOU {$data->format('Y-m-d H:i:s')}");
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
