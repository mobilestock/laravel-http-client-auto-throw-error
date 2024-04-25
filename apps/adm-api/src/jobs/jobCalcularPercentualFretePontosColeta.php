<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\PontosColetaService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();

        $pontosColeta = PontosColetaService::listaPontosDeColeta();
        foreach ($pontosColeta as $pontoColeta) {
            if (!$pontoColeta['deve_recalcular_percentual']) {
                continue;
            }

            $calculado = PontosColetaService::calculaTarifaPontoColeta(
                $pontoColeta['afiliados'],
                $pontoColeta['valor_custo_frete'],
                $pontoColeta['porcentagem_frete']
            );

            if (
                $pontoColeta['porcentagem_frete'] !== $calculado['porcentagem_frete'] ||
                $pontoColeta['entregas'] !== $calculado['lista_id_entregas']
            ) {
                if ($pontoColeta['porcentagem_frete'] !== $calculado['porcentagem_frete']) {
                    PontosColetaService::atualizaTarifaPontoColeta(
                        DB::getPdo(),
                        $pontoColeta['id_colaborador'],
                        $pontoColeta['valor_custo_frete'],
                        $calculado['porcentagem_frete']
                    );
                }

                PontosColetaService::insereLogCalculoPercentualFretePontosColeta(
                    $pontoColeta['id_colaborador'],
                    $calculado['lista_id_entregas'],
                    $pontoColeta['valor_custo_frete'],
                    $calculado['porcentagem_frete']
                );
            }
        }

        DB::commit();
    }
};
