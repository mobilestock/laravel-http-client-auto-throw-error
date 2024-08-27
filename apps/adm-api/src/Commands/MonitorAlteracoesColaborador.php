<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventsNames;
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
        $configuracoes = app()['config']['replicador-dados'];

        $registro = new class ($configuracoes) extends EventSubscribers {
            private array $configuracoes;

            public function __construct(array $configuracoes)
            {
                $this->configuracoes = $configuracoes;
            }

            public function allEvents(EventDTO $evento): void
            {
                $databaseAdmApi = env('MYSQL_DB_NAME');
                $databaseMedApi = env('MYSQL_DB_NAME_MED');
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
                $novosValores = $valores['after'] ?? $valores;

                if ($evento->getType() === ConstEventsNames::WRITE) {
                    if ($banco !== $databaseAdmApi || $tabela !== 'colaboradores') {
                        return;
                    }

                    DB::insert(
                        "INSERT INTO $databaseMedApi.usuarios (
                            $databaseMedApi.usuarios.id,
                            $databaseMedApi.usuarios.telefone,
                            $databaseMedApi.usuarios.nome
                        ) VALUES (
                            :id_colaborador,
                            :telefone,
                            :razao_social
                        );",
                        [
                            ':id_colaborador' => $novosValores['id'],
                            ':telefone' => $novosValores['telefone'],
                            ':razao_social' => $novosValores['razao_social'],
                        ]
                    );
                } else {
                    [
                        'atualizar' => $bancosPodeAtualizar,
                        'equivalencias' => $equivalencias,
                    ] = $this->configuracoes;
                    $colunasPodeAtualizar = $bancosPodeAtualizar[$banco][$tabela] ?? [];
                    $valoresAlterados = array_diff_assoc(
                        Arr::only($novosValores, $colunasPodeAtualizar),
                        Arr::only($valores['before'], $colunasPodeAtualizar)
                    );
                    if (empty($valoresAlterados) || empty($colunasPodeAtualizar)) {
                        return;
                    }

                    $camposAlterar = [];
                    foreach (['id', 'nome', 'telefone'] as $chave) {
                        $equivalente = $equivalencias[$chave] ?? [];
                        $coluna = array_filter(
                            $equivalente,
                            fn(string $item): bool => isset($valoresAlterados[$item]) || isset($novosValores[$item])
                        );
                        $coluna = current($coluna);
                        if (empty($coluna)) {
                            continue;
                        }

                        $camposAlterar[$chave] = $valoresAlterados[$coluna] ?? ($novosValores[$coluna] ?? null);
                    }
                    $camposAlterar = array_filter($camposAlterar);

                    foreach ($bancosPodeAtualizar as $database => $tables) {
                        foreach ($tables as $table => $columns) {
                            $linkTabela = "$database.$table";

                            $binds = [];
                            $colunasSet = [];
                            $condicaoWhere = null;

                            foreach ($camposAlterar as $chave => $valor) {
                                $coluna = current(array_intersect($columns, $equivalencias[$chave] ?? []));

                                $sql = "$linkTabela.$coluna = :$chave";
                                if ($chave === 'id') {
                                    $condicaoWhere = $sql;
                                } else {
                                    $colunasSet[] = $sql;
                                }

                                $binds[":$chave"] = $valor;
                            }

                            if (
                                ($banco === $database && $tabela === $table) ||
                                empty($condicaoWhere) ||
                                empty($colunasSet) ||
                                empty($binds)
                            ) {
                                continue;
                            }

                            $set = implode(', ', $colunasSet);
                            $sql = "UPDATE $linkTabela SET $set WHERE $condicaoWhere;";

                            DB::update($sql, $binds);
                        }
                    }
                }
            }
        };

        echo 'COMEÇOU ' . Carbon::now('America/Sao_Paulo')->format('Y-m-d H:i:s') . PHP_EOL;

        $builder = (new ConfigBuilder())
            ->withPort(3306)
            ->withHost(env('MYSQL_HOST'))
            ->withUser(env('MYSQL_USER_COLABORADOR_CENTRAL'))
            ->withPassword(env('MYSQL_PASSWORD_COLABORADOR_CENTRAL'))
            ->withEventsOnly([ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V1])
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), env('MYSQL_DB_NAME_MED')])
            ->withTablesOnly(['colaboradores', 'usuarios', 'lojas']);

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
