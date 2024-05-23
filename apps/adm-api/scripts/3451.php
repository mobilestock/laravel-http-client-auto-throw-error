<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;

require_once __DIR__ . '/../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        echo 'Atualizando metadados' . PHP_EOL;
        $metadados = DB::cursor(
            "SELECT
                transacao_financeiras_metadados.id,
                transacao_financeiras_metadados.valor
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            ORDER BY transacao_financeiras_metadados.id DESC;"
        );
        $qtdMetadados = DB::selectOneColumn(
            "SELECT COUNT(transacao_financeiras_metadados.id) AS `qtd_metadados`
            FROM transacao_financeiras_metadados
            WHERE transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'
            ORDER BY transacao_financeiras_metadados.id DESC;"
        );

        $inicioTempo = microtime(true);
        foreach ($metadados as $index => $metadado) {
            self::barraDeProgresso($qtdMetadados, $index + 1, $inicioTempo);

            $metadado['valor'] = json_decode($metadado['valor'], true);
            $metadado['valor']['logradouro'] = $metadado['valor']['endereco'];
            unset($metadado['valor']['endereco']);

            $metadadosModel = new TransacaoFinanceirasMetadadosService();
            $metadadosModel->id = $metadado['id'];
            $metadadosModel->chave = 'ENDERECO_CLIENTE_JSON';
            $metadadosModel->valor = $metadado['valor'];
            $metadadosModel->alterar(DB::getPdo());
        }

        echo PHP_EOL . 'Metadados atualizadas' . PHP_EOL;
        DB::commit();
    }
    /**
     * @param int $total
     * @param int $atual
     * @param float|null $inicioTempo Defina: '$inicioTempo = microtime(true);' no inicio do loop
     * @return void
     */
    private function barraDeProgresso(int $total, int $atual, ?float $inicioTempo = null): void
    {
        if ($total <= 0) {
            throw new InvalidArgumentException('O total deve ser maior que 0.');
        }

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
