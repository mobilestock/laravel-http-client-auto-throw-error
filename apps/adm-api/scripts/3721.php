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
            "SELECT produtos.id
            FROM produtos
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

        foreach ($produtosParaAtualizar as $index => $produto) {
            DB::beginTransaction();
            DB::update(
                "UPDATE produtos
                SET produtos.valor_custo_produto = produtos.valor_custo_produto
                WHERE produtos.id = :idProduto",
                ['idProduto' => $produto['id']]
            );
            DB::commit();

            $this->barraDeProgresso($qtdProdutosParaAtualizar, $index + 1, $startTime);
        }

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
