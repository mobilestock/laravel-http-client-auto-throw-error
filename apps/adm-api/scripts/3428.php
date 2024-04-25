<?php

use MobileStock\jobs\config\AbstractJob;

require_once __DIR__ . '/../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit('Execute esse comando apenas por terminal!');
}

return new class extends AbstractJob {
    public function run()
    {
        $startTime = microtime(true);

        $colaboradores = Illuminate\Support\Facades\DB::cursor(
            'SELECT
                        colaboradores.id,
                        colaboradores.telefone,
                        colaboradores.razao_social
                    FROM colaboradores
                    WHERE colaboradores.telefone IS NOT NULL;'
        );
        $qtdColaboradores = Illuminate\Support\Facades\DB::selectOneColumn(
            'SELECT COUNT(colaboradores.id) FROM colaboradores WHERE colaboradores.telefone IS NOT NULL'
        );

        echo 'Começando a atualizar os endereços dos colaboradores...' . PHP_EOL;

        foreach ($colaboradores as $index => $colaborador) {
            Illuminate\Support\Facades\DB::update(
                'UPDATE colaboradores_enderecos SET
                    colaboradores_enderecos.telefone_destinatario = :telefone,
                    colaboradores_enderecos.nome_destinatario = :razao_social
                WHERE colaboradores_enderecos.id_colaborador = :id_colaborador',
                [
                    'telefone' => $colaborador['telefone'],
                    'id_colaborador' => $colaborador['id'],
                    'razao_social' => $colaborador['razao_social'],
                ]
            );

            $this->barraDeProgresso($qtdColaboradores, $index + 1, $startTime);
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
