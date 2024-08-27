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
                $databaseLookPayApi = env('MYSQL_DB_NAME_LOOKPAY');
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
                $novosValores = $valores['after'];
                echo json_encode(
                    [
                        'tipo' => $evento->getType(),
                        'id_colaborador' => $novosValores['id'],
                    ],
                    JSON_PRETTY_PRINT
                ) . PHP_EOL;

                if ($evento->getType() === ConstEventsNames::UPDATE) {
                    $colunas = $this->configuracoes['atualizar'][$banco][$tabela] ?? [];
                    $valoresAlterados = array_diff_assoc(
                        Arr::only($novosValores, $colunas),
                        Arr::only($valores['before'], $colunas)
                    );
                    if (empty($valoresAlterados)) {
                        return;
                    }

                    $identificadorEquivalencia = function (array $objeto, string $chave): ?string {
                        $coluna = current(
                            array_filter(
                                $this->configuracoes['equivalencias'][$chave],
                                fn(string $coluna): bool => isset($objeto[$coluna])
                            )
                        );

                        return $objeto[$coluna] ?? null;
                    };

                    $valoresAlterados = [
                        'id' => $identificadorEquivalencia($novosValores, 'id'),
                        'nome' => $identificadorEquivalencia($valoresAlterados, 'nome'),
                        'telefone' => $identificadorEquivalencia($valoresAlterados, 'telefone'),
                        'senha' => $identificadorEquivalencia($valoresAlterados, 'senha'),
                    ];
                    $valoresAlterados = array_filter($valoresAlterados);

                    foreach ($this->configuracoes['atualizar'] as $database => $tables) {
                        foreach ($tables as $table => $columns) {
                            $colunaWhere = current(
                                array_intersect($columns, $this->configuracoes['equivalencias']['id'])
                            );
                            if (($banco === $database && $tabela === $table) || !$colunaWhere) {
                                continue;
                            }

                            $colunasSet = [];
                            $binds[':id'] = $valoresAlterados['id'];
                            foreach (Arr::except($valoresAlterados, 'id') as $chave => $valor) {
                                $columnSet = current(
                                    array_intersect($columns, $this->configuracoes['equivalencias'][$chave])
                                );
                                if ($columnSet) {
                                    $colunasSet[] = "$database.$table.$columnSet = :$chave";
                                    $binds[":$chave"] = $valor;
                                }
                            }

                            if (empty($colunasSet)) {
                                continue;
                            }

                            $set = implode(', ', $colunasSet);
                            $ligacaoTabela = "$database.$table";
                            $where = "$ligacaoTabela.$colunaWhere = :id";
                            $sql = "UPDATE $ligacaoTabela SET $set WHERE $where;";
                            if ($ligacaoTabela === "$databaseLookPayApi.establishments") {
                                /**
                                 * TODO: Verificar se `mobilestock_users.contributor_id` não pode ser passado pra tabela de establishments
                                 */
                                $sql = "UPDATE $ligacaoTabela ";
                                $sql .= "INNER JOIN $database.mobilestock_users ON ";
                                $sql .= "$database.mobilestock_users.establishment_id = $ligacaoTabela.id ";
                                $sql .= "SET $set WHERE $database.mobilestock_users.contributor_id = :id;";
                            }

                            DB::update($sql, $binds);
                        }
                    }
                } else {
                    echo 'EVENTO NÃO TRATADO: ' . $evento->getType() . PHP_EOL;
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
            ->withDatabasesOnly([env('MYSQL_DB_NAME'), env('MYSQL_DB_NAME_LOOKPAY'), env('MYSQL_DB_NAME_MED')])
            ->withTablesOnly(['colaboradores', 'usuarios', 'lojas', 'establishments']);

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
