<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\ProdutoLogistica;

require_once __DIR__ . '/../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit('Execute esse comando apenas por terminal!');
}

return new class extends AbstractJob {
    public function run(): void
    {
        $startTime = microtime(true);

        echo 'E lá vamos nós de novo com o SKU...' . PHP_EOL;

        $produtosParaAtualizar = DB::cursor(
            "SELECT
                logistica_item.uuid_produto,
                logistica_item.id_produto,
                logistica_item.nome_tamanho
            FROM logistica_item
            INNER JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
            AND logistica_item_data_alteracao.situacao_nova = 'CO'
            AND logistica_item_data_alteracao.data_criacao >= CURDATE() - INTERVAL 2 YEAR
            WHERE
                logistica_item.sku IS NULL
            AND NOT EXISTS(
                SELECT 1
                FROM entregas_devolucoes_item
                WHERE entregas_devolucoes_item.uuid_produto = logistica_item.uuid_produto
                    AND entregas_devolucoes_item.tipo = 'DE'
                    AND entregas_devolucoes_item.situacao = 'CO'
            )
            ORDER BY logistica_item.id DESC;"
        );

        $qtdProdutosParaAtualizar = DB::selectOneColumn(
            "SELECT
                COUNT(logistica_item.id)
            FROM logistica_item
            INNER JOIN logistica_item_data_alteracao ON logistica_item_data_alteracao.uuid_produto = logistica_item.uuid_produto
            AND logistica_item_data_alteracao.situacao_nova = 'CO'
            AND logistica_item_data_alteracao.data_criacao >= CURDATE() - INTERVAL 2 YEAR
            WHERE
                logistica_item.sku IS NULL
            AND NOT EXISTS(
                SELECT 1
                FROM entregas_devolucoes_item
                WHERE
                    entregas_devolucoes_item.uuid_produto = logistica_item.uuid_produto
                    AND entregas_devolucoes_item.tipo = 'DE'
                    AND entregas_devolucoes_item.situacao = 'CO'
            )"
        );

        echo "Ok, achei $qtdProdutosParaAtualizar produtos que vão ganhar um SKU!" . PHP_EOL . PHP_EOL;

        foreach ($produtosParaAtualizar as $index => $produto) {
            DB::beginTransaction();
            $produtoSku = new ProdutoLogistica([
                'id_produto' => $produto['id_produto'],
                'nome_tamanho' => $produto['nome_tamanho'],
                'id_usuario' => 2,
            ]);

            $produtoSku->criarSkuPorTentativas();

            $logisticaItem = new LogisticaItemModel();
            $logisticaItem->exists = true;
            $logisticaItem->uuid_produto = $produto['uuid_produto'];
            $logisticaItem->sku = $produtoSku->sku;
            $logisticaItem->update();

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
