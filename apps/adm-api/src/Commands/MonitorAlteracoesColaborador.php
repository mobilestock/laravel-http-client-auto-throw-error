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
        $registro = new class extends EventSubscribers {
            private array $configuracoes;
            public function __construct()
            {
                $this->configuracoes = app()['config']['replicador-dados'];
            }
            public function allEvents(EventDTO $evento): void
            {
                $binlogAtual = $evento->getEventInfo()->getBinLogCurrent();
                Cache::put(
                    MonitorAlteracoesColaborador::CACHE_ULTIMA_ALTERACAO,
                    [
                        'posicao' => $binlogAtual->getBinLogPosition(),
                        'arquivo' => $binlogAtual->getBinFileName(),
                    ],
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
                    $indiceAlterado = "{$banco}_{$tabela}";
                    foreach ($this->configuracoes['atualizar'] as $nomeBanco => $tabelasBanco) {
                        foreach ($tabelasBanco as $nomeTabela => $colunas) {
                            $indice = "{$nomeBanco}_{$nomeTabela}";
                            if ($indiceAlterado === $indice) {
                                continue;
                            }

                            /**
                             * TODO: Montar os UPDATE's
                             */
                            $necessarioAtualizar[] = [
                                'banco' => $nomeBanco,
                                'tabela' => $tabela,
                                'colunas' => $colunas,
                            ];
                        }
                    }

                    echo 'PRECISA ATUALIZAR: ' . json_encode($necessarioAtualizar, JSON_PRETTY_PRINT) . PHP_EOL;
                    echo 'NOVOS VALORES: ' . json_encode($valoresAlterados, JSON_PRETTY_PRINT) . PHP_EOL;
                } else {
                }
            }
        };

        $data = new Carbon('NOW');
        $data->setTimezone('America/Sao_Paulo');
        echo "COMEÇOU {$data->format('Y-m-d H:i:s')}" . PHP_EOL;

        $builder = new ConfigBuilder();
        $builder
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
        if (Cache::has(self::CACHE_ULTIMA_ALTERACAO)) {
            $ultimaAlteracao = Cache::get(self::CACHE_ULTIMA_ALTERACAO);
            $builder->withBinLogFileName($ultimaAlteracao['arquivo'])->withBinLogPosition($ultimaAlteracao['posicao']);
        }

        $replicacao = new MySQLReplicationFactory($builder->build());
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
