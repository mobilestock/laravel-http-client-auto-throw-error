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
                $binlogAtual = $evento->getEventInfo()->getBinLogCurrent();
                DB::insert(
                    "INSERT INTO ultima_alteracao_sincronizada (
                        ultima_alteracao_sincronizada.posicao,
                        ultima_alteracao_sincronizada.arquivo
                    ) VALUES (
                        :posicao,
                        :arquivo
                    );",
                    [':posicao' => $binlogAtual->getBinLogPosition(), ':arquivo' => $binlogAtual->getBinFileName()]
                );
            }
        };

        $data = new Carbon('NOW');
        $data->setTimezone('America/Sao_Paulo');
        var_dump("COMEÇOU {$data->format('Y-m-d H:i:s')}");
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
