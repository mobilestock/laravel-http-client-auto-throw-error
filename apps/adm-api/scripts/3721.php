<?php

use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\config\AbstractJob;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public const TAXA_PRODUTO_BARATO = 2;
    public const CUSTO_MAXIMO_APLICAR_TAXA = 60;
    public const IDS_PRODUTOS_NAO_ATUALIZAR = [82044, 82042, 99265, 93923];

    public function run(): void
    {
        $startTime = microtime(true);

        [$sql, $binds] = ConversorArray::criaBindValues(self::IDS_PRODUTOS_NAO_ATUALIZAR);
        $binds['custo_max_aplicar_taxa'] = self::CUSTO_MAXIMO_APLICAR_TAXA;

        $produtosParaAtualizar = DB::cursor(
            "SELECT
                produtos.id,
                produtos.valor_venda_ms,
                produtos.valor_venda_ml
            WHERE produtos.valor_custo_produto < :custo_max_aplicar_taxa
            AND produtos.id NOT IN ($sql)",
            $binds
        );

        $qtdProdutosParaAtualizar = DB::selectOneColumn(
            "SELECT
                COUNT(produtos.id)
            FROM produtos
            WHERE produtos.valor_custo_produto < :custo_max_aplicar_taxa
            AND produtos.id NOT IN ($sql)",
            $binds
        );

        echo "Ok, achei $qtdProdutosParaAtualizar produtos para atualizar!" . PHP_EOL . PHP_EOL;

        $pdo = DB::getPdo();
        $sql = '';
        $linhasParaAtualizar = 0;

        DB::beginTransaction();

        foreach ($produtosParaAtualizar as $produto) {
            $sql .= "UPDATE produtos SET produtos.valor_custo_produto = produtos.valor_custo_produto WHERE produtos.id = {$produto['id']};";
            $linhasParaAtualizar++;
            $this->barraDeProgresso($qtdProdutosParaAtualizar, $linhasParaAtualizar, $startTime);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $linhasAtualizadas = 0;
        do {
            $linhasAtualizadas += $stmt->rowCount();
        } while ($stmt->nextRowset());

        if ($linhasAtualizadas !== $linhasParaAtualizar) {
            throw new Exception('Não foi possível atualizar os produtos');
        }

        DB::commit();

        echo PHP_EOL . PHP_EOL . 'Acabou!!' . PHP_EOL . PHP_EOL;
    }

    /**
     * @param int $total
     * @param int $atual
     * @param float|null $inicioTempo Defina: '$inicioTempo = microtime(true);' no inicio do loop
     * @return void
     */
    public function barraDeProgresso(int $total, int $atual, ?float $inicioTempo = null): void
    {
        $conversorHoras = function (float $tempo): array {
            $horas = floor($tempo / 3600);
            $minutos = floor(($tempo - $horas * 3600) / 60);
            $segundos = round($tempo - $horas * 3600 - $minutos * 60);

            return [$horas, $minutos, $segundos];
        };

        $percentual = floor(($atual / $total) * 100);
        $falta = 100 - $percentual;
        if (is_null($inicioTempo)) {
            $escrever = sprintf(
                "\033[0G\033[2K[%'={$percentual}s>%-{$falta}s] - $percentual%% - $atual/$total",
                '',
                ''
            );
        } else {
            $tempoDecorrido = microtime(true) - $inicioTempo;
            $tempoPrevisao = ($tempoDecorrido * ($total - $atual)) / $atual;

            [$horas, $minutos, $segundos] = $conversorHoras($tempoDecorrido);
            [$horasRestantes, $minutosRestantes, $segundosRestantes] = $conversorHoras($tempoPrevisao);
            $escrever = sprintf(
                "\033[0G\033[2K[%'={$percentual}s>%-{$falta}s] - $percentual%% - $atual/$total - Tempo: %02d:%02d:%02d - Falta: %02d:%02d:%02d",
                '',
                '',
                $horas,
                $minutos,
                $segundos,
                $horasRestantes,
                $minutosRestantes,
                $segundosRestantes
            );
        }

        fwrite(STDERR, $escrever);
    }
};
