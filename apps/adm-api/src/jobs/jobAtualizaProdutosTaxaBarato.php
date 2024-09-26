<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\ConversorArray;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\Produto;
use MobileStock\service\ConfiguracaoService;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @issue https://github.com/mobilestock/backend/issues/581
 */
return new class extends AbstractJob {
    public const ID_PRODUTO_ADICIONAR_ENTREGA = 93923;

    private ?Carbon $dataParametro = null;

    public function run(): void
    {
        $this->obterParametros();

        $produtosIgnorar = Produto::IDS_PRODUTOS_FRETE;
        $produtosIgnorar[] = self::ID_PRODUTO_ADICIONAR_ENTREGA;

        $comissoes = ConfiguracaoService::buscaComissoes();
        $custoMaximoAplicarTaxaML = $comissoes['custo_max_aplicar_taxa_ml'];
        $custoMaximoAplicarTaxaMS = $comissoes['custo_max_aplicar_taxa_ms'];

        $startTime = microtime(true);

        $dataInicio = $this->dataParametro ?? Carbon::createFromTimestamp($startTime);

        echo 'Script iniciado em: ' .
            Carbon::createFromTimestamp($startTime)->format('d-m-Y H:i:s') .
            PHP_EOL .
            PHP_EOL;

        [$sql, $binds] = ConversorArray::criaBindValues($produtosIgnorar);
        $binds['custo_max_aplicar_taxa_ml'] = $custoMaximoAplicarTaxaML;
        $binds['custo_max_aplicar_taxa_ms'] = $custoMaximoAplicarTaxaMS;
        $binds['data_inicio'] = $dataInicio->format('Y-m-d H:i:s');

        $produtosParaAtualizar = DB::cursor(
            "SELECT produtos.id
            FROM produtos
            WHERE produtos.valor_custo_produto < GREATEST(:custo_max_aplicar_taxa_ml, :custo_max_aplicar_taxa_ms)
                AND produtos.id NOT IN ($sql)
                AND produtos.data_qualquer_alteracao < :data_inicio",
            $binds
        );

        $qtdProdutosParaAtualizar = DB::selectOneColumn(
            "SELECT
                COUNT(produtos.id)
            FROM produtos
            WHERE produtos.valor_custo_produto < GREATEST(:custo_max_aplicar_taxa_ml, :custo_max_aplicar_taxa_ms)
                AND produtos.id NOT IN ($sql)
                AND produtos.data_qualquer_alteracao < :data_inicio",
            $binds
        );

        echo "Ok, achei $qtdProdutosParaAtualizar produtos para atualizar!" . PHP_EOL . PHP_EOL;

        foreach ($produtosParaAtualizar as $index => $produto) {
            DB::update(
                "UPDATE produtos
                SET produtos.valor_custo_produto = produtos.valor_custo_produto
                WHERE produtos.id = :idProduto",
                ['idProduto' => $produto['id']]
            );

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
    private function barraDeProgresso(int $total, int $atual, ?float $inicioTempo = null): void
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

    private function obterParametros(): void
    {
        $opcoes = getopt('', ['data::']);

        if (isset($opcoes['data'])) {
            try {
                $this->dataParametro = Carbon::createFromFormat('d-m-Y H:i:s', $opcoes['data']);
            } catch (Exception $e) {
                echo "Formato de data inválido. Use o formato 'd-m-Y H:i:s'." . PHP_EOL;
                exit(1);
            }
        }
    }
};
