<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\RowsDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

class MonitorAlteracoesColaborador extends Command
{
    protected $signature = 'app:monitor-alteracoes-colaborador';
    protected $description = 'Monitora alterações de colaboradores';
    public const CACHE_ULTIMA_ALTERACAO = 'ultima_alteracao_sincronizada';

    public function handle(): void
    {
        $registro = new class extends EventSubscribers {
            public function allEvents(EventDTO $evento): void
            {
                $databaseAdmApi = env('MYSQL_DB_NAME');
                $databaseMedApi = env('DB_DATABASE_MED');
                $binlogAtual = $evento->getEventInfo()->getBinLogCurrent();
                Cache::put(
                    MonitorAlteracoesColaborador::CACHE_ULTIMA_ALTERACAO,
                    json_encode([
                        'posicao' => $binlogAtual->getBinLogPosition(),
                        'arquivo' => $binlogAtual->getBinFileName(),
                    ]),
                    60 * 60 * 24
                );

                /** @var RowsDTO $evento */
                $infosEstrutura = $evento->getTableMap();
                $banco = $infosEstrutura->getDatabase();
                $tabela = $infosEstrutura->getTable();
                $valores = current($evento->getValues());
                $valoresAlterados = array_diff_assoc(
                    Arr::only($valores['after'], 'telefone'),
                    Arr::only($valores['before'], 'telefone')
                );
                if (empty($valoresAlterados)) {
                    return;
                }

                if ($banco === $databaseAdmApi && $tabela === 'colaboradores') {
                    DB::update(
                        "UPDATE $databaseMedApi.lojas
                        SET $databaseMedApi.lojas.telefone = :telefone
                        WHERE $databaseMedApi.lojas.id_revendedor = :id_colaborador;",
                        [
                            ':telefone' => $valoresAlterados['telefone'],
                            ':id_colaborador' => $valores['after']['id'],
                        ]
                    );
                } elseif ($banco === $databaseMedApi && $tabela === 'lojas') {
                    DB::update(
                        "UPDATE $databaseAdmApi.colaboradores
                        SET $databaseAdmApi.colaboradores.telefone = :telefone
                        WHERE $databaseAdmApi.colaboradores.id = :id_colaborador;",
                        [
                            ':telefone' => $valoresAlterados['telefone'],
                            ':id_colaborador' => $valores['after']['id_revendedor'],
                        ]
                    );
                }
            }
        };

        echo 'COMEÇOU ' . Carbon::now('America/Sao_Paulo')->format('Y-m-d H:i:s') . PHP_EOL;

        $builder = (new ConfigBuilder())
            ->withPort(3306)
            ->withHost(env('MYSQL_HOST'))
            ->withUser(env('DB_USERNAME_COLABORADOR_CENTRAL'))
            ->withPassword(env('DB_PASSWORD_COLABORADOR_CENTRAL'))
            ->withEventsOnly([ConstEventType::UPDATE_ROWS_EVENT_V1])
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), env('DB_DATABASE_MED')])
            ->withTablesOnly(['colaboradores', 'lojas']);

        $ultimaAlteracao = Cache::get(self::CACHE_ULTIMA_ALTERACAO);
        if (!empty($ultimaAlteracao)) {
            $ultimaAlteracao = json_decode($ultimaAlteracao, true);
            $builder->withBinLogFileName($ultimaAlteracao['arquivo'])->withBinLogPosition($ultimaAlteracao['posicao']);
        }

        $replicacao = new MySQLReplicationFactory($builder->build());
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
