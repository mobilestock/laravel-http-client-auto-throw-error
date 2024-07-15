<?php

namespace MobileStock\jobs;

use DateTime;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Globals;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\AcompanhamentoTempService;
use MobileStock\service\DiaUtilService;
use MobileStock\service\PontosColetaAgendaAcompanhamentoService;
use MobileStock\service\PrevisaoService;
use PDO;
use Throwable;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(
        PrevisaoService $previsao,
        PontosColetaAgendaAcompanhamentoService $agenda,
        AcompanhamentoTempService $acompanhamento
    ) {
        DB::beginTransaction();
        $dataAtual = new DateTime('NOW');
        $ehDiaUtil = DiaUtilService::ehDiaUtil($dataAtual->format('Y-m-d'));
        if (!$ehDiaUtil) {
            return;
        }

        $IDXSemana = (int) $dataAtual->format('N');
        $diasSemana = Globals::DIAS_SEMANA;
        $diaAtual = $diasSemana[$IDXSemana];
        $horarioSeparacao = $previsao->buscaHorarioSeparando();
        $pontosColeta = $agenda->buscaPontosColetaAgendados($diaAtual, $horarioSeparacao);
        if (empty($pontosColeta)) {
            return;
        }

        $produtos = $acompanhamento->buscaProdutosParaAdicionarNoAcompanhamentoPorPontosColeta($pontosColeta);
        if (empty($produtos)) {
            return;
        }

        $agenda->removeHorariosPontuais($diaAtual, $horarioSeparacao);

        DB::commit();
        dispatch(new GerenciarAcompanhamento($produtos, GerenciarAcompanhamento::CRIAR_ACOMPANHAMENTO));
    }
};
