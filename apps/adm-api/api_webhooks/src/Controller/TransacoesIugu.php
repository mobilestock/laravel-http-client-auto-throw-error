<?php

namespace api_webhooks\Controller;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\Lancamento;
use MobileStock\repository\ContaBancariaRepository;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\TransferenciasService;

class TransacoesIugu
{
    public function confirmacaoSaque()
    {
        DB::beginTransaction();
        $dadosJson = Request::input('data');
        Validador::validar($dadosJson, [
            'withdraw_request_id' => [Validador::OBRIGATORIO],
            'status' => [Validador::OBRIGATORIO, Validador::ENUM('rejected', 'processing', 'accepted')],
            'feedback' => [],
        ]);

        switch ($dadosJson['status']) {
            case 'rejected':
                $transferencia = TransferenciasService::consultaTransferencia($dadosJson['withdraw_request_id']);
                if ($transferencia['situacao'] !== 'EP') {
                    Log::driver('telegram')->info(
                        'Webhook está tentando rejeitar uma transferência que não está em processamento.',
                        [
                            'id_transferencia' => $dadosJson['withdraw_request_id'],
                            'situacao_atual' => $transferencia['situacao'],
                        ]
                    );
                }

                $lancamento = new Lancamento(
                    'P',
                    1,
                    'EP',
                    $transferencia['id_colaborador'],
                    null,
                    $transferencia['valor_pago'],
                    1,
                    7
                );
                $lancamento->observacao = "Uma tentativa de transferencia na conta de {$transferencia['nome_titular']}";
                $lancamento->observacao .= " Nº da conta: {$transferencia['conta']} falhou: ";
                $lancamento->observacao .= $dadosJson['feedback'];
                LancamentoCrud::salva(DB::getPdo(), $lancamento);

                TransferenciasService::atualizaSituacaoTransferencia($dadosJson['withdraw_request_id'], 'RE');
                ContaBancariaRepository::bloqueiaContaIugu($transferencia['iugu_token_live']);

                $iugu = new IuguHttpClient();
                $iugu->apiToken = $transferencia['iugu_token_live'];
                $iugu->listaCodigosPermitidos = [200];
                $iugu->post('transfers', [
                    'amount_cents' => round($transferencia['valor_pago'] * 100),
                    'custom_variables' => [['name' => 'tipo', 'value' => 'Transferencia manual mobile pay']],
                    'receiver_id' => env('DADOS_PAGAMENTO_IUGUCONTAMOBILE'),
                    'test' => !App::isProduction(),
                ]);
                break;
            case 'processing':
                TransferenciasService::atualizaSituacaoTransferencia($dadosJson['withdraw_request_id'], 'EP');
                break;
            case 'accepted':
                TransferenciasService::atualizaSituacaoTransferencia($dadosJson['withdraw_request_id'], 'PA');
                break;
        }

        DB::commit();
    }
}
