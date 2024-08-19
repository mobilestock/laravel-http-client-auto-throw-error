<?php

namespace MobileStock\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\RowsDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

class MonitorAlteracoesColaborador extends Command
{
    protected $signature = 'app:monitor-alteracoes-colaborador';
    protected $description = 'Monitora alterações de colaboradores';

    public function handle(MySQLReplicationFactory $replicacao): void
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
                /**
                 * TODO: Utilizar facades de CACHE do laravel pra salvar essas informações
                 */
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
        $replicacao->registerSubscriber($registro);

        $replicacao->run();
    }
}
