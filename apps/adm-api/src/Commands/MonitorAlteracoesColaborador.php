<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
                $binlogAtual = $evento->getEventInfo()->getBinLogCurrent();
                Cache::put(
                    MonitorAlteracoesColaborador::CACHE_ULTIMA_ALTERACAO,
                    json_encode([
                        'posicao' => $binlogAtual->getBinLogPosition(),
                        'arquivo' => $binlogAtual->getBinFileName(),
                    ]),
                    60 * 60 * 24
                );

                /**
                 * TODO: Implementar a lógica de replicação
                 * TODO: Quando se cadastrar em um sistema deve cadastrar nos outros, exceto o LOOKPAY
                 */

                if ($evento->getType() === ConstEventsNames::UPDATE) {
                    /** @var RowsDTO $evento  */
                    $infosEstrutura = $evento->getTableMap();
                    $banco = $infosEstrutura->getDatabase();
                    $tabela = $infosEstrutura->getTable();
                    $colunas = $this->configuracoes['atualizar'][$banco][$tabela] ?? [];

                    $valores = current($evento->getValues());
                    $novosValores = $valores['after'];
                    $valoresAlterados = array_diff_assoc(
                        Arr::only($novosValores, $colunas),
                        Arr::only($valores['before'], $colunas)
                    );
                    if (empty($valoresAlterados)) {
                        return;
                    }

                    $identificadorEquivalencia = function (array $objeto, string $chave): ?string {
                        $itens = array_filter(
                            $this->configuracoes['equivalencias'][$chave],
                            fn(string $coluna): bool => isset($objeto[$coluna])
                        );
                        $indice = current($itens);

                        return $objeto[$indice] ?? null;
                    };

                    $valoresAlterados = [
                        'id' => $identificadorEquivalencia($novosValores, 'id'),
                        'nome' => $identificadorEquivalencia($valoresAlterados, 'nome'),
                        'telefone' => $identificadorEquivalencia($valoresAlterados, 'telefone'),
                        'senha' => $identificadorEquivalencia($valoresAlterados, 'senha'),
                    ];
                    $valoresAlterados = array_filter($valoresAlterados);

                    $necessarioAtualizar = [];
                    foreach ($this->configuracoes['atualizar'] as $database => $tables) {
                        foreach ($tables as $table => $columns) {
                            if ($banco === $database && $tabela === $table) {
                                continue;
                            }

                            $colunaWhere = $this->configuracoes['equivalencias']['id'];
                            $column = current(array_intersect($columns, $colunaWhere));
                            $binds = array_merge(
                                ...array_map(
                                    fn(string $coluna, string $chave): array => [":$chave" => $coluna],
                                    $valoresAlterados,
                                    array_keys($valoresAlterados)
                                )
                            );

                            $colunasSet = [];
                            foreach (array_keys(Arr::except($valoresAlterados, 'id')) as $chave) {
                                $estruturaSet = $this->configuracoes['equivalencias'][$chave];
                                $columnSet = current(array_intersect($columns, $estruturaSet));
                                if (empty($columnSet)) {
                                    continue;
                                }

                                $colunasSet[] = "$database.$table.$columnSet = :$chave";
                            }

                            if (empty($colunasSet)) {
                                continue;
                            }

                            $set = implode(',' . PHP_EOL, $colunasSet);
                            $where = "$database.$table.$column = :id";

                            $necessarioAtualizar[] = [
                                'SQL' => "UPDATE $database.$table SET $set WHERE $where;",
                                'BINDS' => $binds,
                            ];
                        }
                    }

                    echo 'PRECISA ATUALIZAR: ' .
                        json_encode($necessarioAtualizar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                        PHP_EOL;
                }
            }
        };

        echo 'COMEÇOU ' . Carbon::now('America/Sao_Paulo')->format('Y-m-d H:i:s') . PHP_EOL;

        $builder = (new ConfigBuilder())
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
