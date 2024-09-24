<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\RowsDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

class MonitorAlteracoesColaboradorLojas extends Command
{
    protected $signature = 'app:monitor-alteracoes-colaborador-lojas';
    protected $description = 'Monitora alterações de colaboradores e lojas';
    public const CACHE_ULTIMA_ALTERACAO = 'replicador.colaborador_loja.ultima_alteracao';

    public function handle(): void
    {
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $evento): void
            {
                $databaseAdmApi = env('MYSQL_DB_NAME');
                $databaseMedApi = env('MED_DB_DATABASE');

                /** @var RowsDTO $evento */
                $infosEstrutura = $evento->getTableMap();
                $tabela = $infosEstrutura->getTable();
                ['before' => $antes, 'after' => $depois] = current($evento->getValues());
                if ($depois['telefone'] === $antes['telefone']) {
                    return;
                }

                DB::statement('SET SESSION sql_log_bin = 0;');
                if ($tabela === 'colaboradores') {
                    DB::update(
                        "UPDATE $databaseMedApi.lojas
                        SET $databaseMedApi.lojas.telefone = :telefone
                        WHERE $databaseMedApi.lojas.id_revendedor = :id_colaborador;",
                        [
                            ':telefone' => $depois['telefone'],
                            ':id_colaborador' => $depois['id'],
                        ]
                    );
                } else {
                    DB::update(
                        "UPDATE $databaseAdmApi.colaboradores
                        SET $databaseAdmApi.colaboradores.telefone = :telefone
                        WHERE $databaseAdmApi.colaboradores.id = :id_colaborador;",
                        [
                            ':telefone' => $depois['telefone'],
                            ':id_colaborador' => $depois['id_revendedor'],
                        ]
                    );
                }

                $binlogAtual = $evento->getEventInfo()->getBinLogCurrent();
                Cache::put(
                    MonitorAlteracoesColaboradorLojas::CACHE_ULTIMA_ALTERACAO,
                    [
                        'posicao' => $binlogAtual->getBinLogPosition(),
                        'arquivo' => $binlogAtual->getBinFileName(),
                    ],
                    60 * 60 * 24
                );
            }
        };

        $builder = (new ConfigBuilder())
            ->withPort(3306)
            ->withHost(env('MYSQL_HOST'))
            ->withUser(env('REPLICADOR_DB_USERNAME'))
            ->withPassword(env('REPLICADOR_DB_PASSWORD'))
            ->withEventsOnly([ConstEventType::UPDATE_ROWS_EVENT_V1])
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), env('MED_DB_DATABASE')])
            ->withTablesOnly(['colaboradores', 'lojas']);

        $ultimaAlteracao = Cache::get(self::CACHE_ULTIMA_ALTERACAO);
        if (!empty($ultimaAlteracao)) {
            $builder->withBinLogFileName($ultimaAlteracao['arquivo'])->withBinLogPosition($ultimaAlteracao['posicao']);
        }

        $replicacao = new MySQLReplicationFactory($builder->build());
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
