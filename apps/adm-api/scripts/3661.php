<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\PedidoItem;

require_once __DIR__ . '/../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit('Execute esse comando apenas por terminal!');
}

return new class extends AbstractJob {
    public function run(): void
    {
        $startTime = microtime(true);

        $produtosCarrinho = DB::cursor(
            "SELECT
                pedido_item.uuid
            FROM pedido_item
            WHERE
                pedido_item.data_criacao <= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND pedido_item.situacao = :situacao_em_aberto
            ORDER BY pedido_item.data_criacao DESC;",
            ['situacao_em_aberto' => PedidoItem::SITUACAO_EM_ABERTO]
        );

        $qtdProdutosCarrinho = DB::selectOneColumn(
            "SELECT COUNT(pedido_item.uuid) FROM pedido_item
            WHERE
                pedido_item.data_criacao <= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND pedido_item.situacao = :situacao_em_aberto;",
            ['situacao_em_aberto' => PedidoItem::SITUACAO_EM_ABERTO]
        );

        echo "Começando a limpar o carrinho de $qtdProdutosCarrinho clientes..." . PHP_EOL;

        foreach ($produtosCarrinho as $index => $carrinho) {
            DB::delete('DELETE FROM pedido_item WHERE pedido_item.uuid = :uuid', [
                'uuid' => $carrinho['uuid'],
            ]);

            $this->barraDeProgresso($qtdProdutosCarrinho, $index + 1, $startTime);
        }
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
