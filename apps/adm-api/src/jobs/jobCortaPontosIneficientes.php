<?php

namespace MobileStock\jobs;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\Globals;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\IBGEService;
use MobileStock\service\MessageService;
use MobileStock\service\PontosColetaService;
use MobileStock\service\TipoFreteService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        DB::beginTransaction();
        $msgService = new MessageService();

        $pontosIneficientes = PontosColetaService::buscaPontosIneficientes();
        foreach ($pontosIneficientes as $pontoIneficiente) {
            IBGEService::gerenciarPontoColeta(DB::getPdo(), $pontoIneficiente['id_colaborador'], false, 2);
            TipoFreteService::gerenciaSituacaoPonto($pontoIneficiente['id_colaborador'], false);
            TipoFreteService::rejeitaSolicitacaoPonto($pontoIneficiente['id_colaborador']);
            $tipoPonto = $pontoIneficiente['tipo_ponto'] == 'PP' ? 'Ponto Parado' : 'Entregador';
            $msgService->sendMessageWhatsApp(
                Globals::NUM_FABIO,
                "{$tipoPonto} desativado automaticamente\n\n" .
                    "ID: {$pontoIneficiente['id_colaborador']}\n" .
                    "Nome: {$pontoIneficiente['nome']}\n" .
                    "Entregas: {$pontoIneficiente['qtd_entregas']}\n" .
                    "Percentual: {$pontoIneficiente['porcentagem_frete']}%"
            );
            $msgService->sendMessageWhatsApp(
                $pontoIneficiente['telefone'],
                "Seu ponto de coleta do meulook foi encerrado automaticamente devido a insuficiência de vendas.\n\n" .
                    'Caso precise de ajuda, entre em contato com o suporte.'
            );
        }

        DB::commit();
    }
};
