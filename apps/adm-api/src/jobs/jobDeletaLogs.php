<?php

use Illuminate\Support\Facades\DB;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\Fila\FilaRespostasService;
use MobileStock\service\Separacao\separacaoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(FilaRespostasService $filaRespostas)
    {
        DB::beginTransaction();
        TransacaoConsultasService::insereEDeletaBackupLogTentativaTransacao();
        $filaRespostas->removeRespostas();
        separacaoService::deletaLogsSeparacao();
        EntregaServices::deletaLogEntregasFaturamentoItem();
        EntregaServices::deletaLogEntregas();
        ColaboradorEndereco::deletaLogsAlteracaoEndereco();
        DB::commit();
    }
};
