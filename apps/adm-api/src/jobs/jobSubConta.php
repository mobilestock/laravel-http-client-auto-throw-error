<?php

use MobileStock\helper\Globals;
use MobileStock\helper\Retentador;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\repository\ContaBancariaRepository;
use MobileStock\service\Iugu\IuguHttpClient;
use MobileStock\service\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob
{
    public function run(\PDO $conexao)
    {
        $offSet = 0;
        $msgService = new MessageService();
        do {
            $iugu = new IuguHttpClient();
            $iugu->listaCodigosPermitidos = [200];
            Retentador::retentar(10, fn() => $iugu->get("marketplace?limit=1000&start=$offSet"));
            $totalContas = ($totalContas ?? $iugu->body['totalItems']) - 1000;
            $offSet += 1000;

            foreach($iugu->body['items'] as $subconta) {

                $retornoDadosSubconta = ContaBancariaRepository::dadosSubConta($conexao, $subconta['id']);

                if(!$retornoDadosSubconta) continue;
                $iugu->apiToken = $retornoDadosSubconta['iugu_token_live'];
                Retentador::retentar(10, fn() => $iugu->informacoesSubConta($subconta['id']));

                $valor = preg_replace('/[^0-9]/', '', $iugu->body["balance_available_for_withdraw"]);
                if($valor > 0 && $subconta['id']  !== '33CBAD37E5F7463392F2C1C451F0C903' && !$retornoDadosSubconta['tem_transferencia_em_aberto']) {
                    $iugu->post('transfers', [
                        'receiver_id' => $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'],
                        'amount_cents' => $valor
                    ]);
                    $valorFormatado = $valor / 100;
                    $msgService->sendMessageWhatsApp(
                        Globals::NUM_FABIO,
                        "AVISO!!!\n".
                        "Foi descontado do(a) cliente {$retornoDadosSubconta['nome_titular']}, o valor de R$" .
                        number_format($valorFormatado,2,',','.')
                    );
                }
            }
        } while ($totalContas > 0);
    }
};