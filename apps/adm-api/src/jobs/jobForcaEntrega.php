<?php

namespace Mobilestock\jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MobileStock\helper\EntregaClienteSaldoNegativoException;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(MessageService $msgService)
    {
        $logisticaAtrasandoPagamentoSeller = EntregasFaturamentoItemService::buscaInfosProdutosEntregasAtrasadas();
        $logisticaAtrasandoPagamentoSeller = array_filter(
            $logisticaAtrasandoPagamentoSeller,
            fn(array $produto): bool => !in_array($produto['situacao_atual'], [
                'Pacote na expedição',
                'Pacote em aberto',
            ])
        );
        foreach ($logisticaAtrasandoPagamentoSeller as $produto) {
            try {
                DB::beginTransaction();
                Log::withContext(['uuid_produto' => $produto['uuid_produto']]);
                EntregaServices::forcarEntregaDeProduto($produto['uuid_produto']);
                DB::commit();
            } catch (EntregaClienteSaldoNegativoException $e) {
                DB::rollBack();
                $mensagem = "O produto {$produto['id_produto']}, tamanho {$produto['nome_tamanho']}, ";
                $mensagem .= "do cliente {$produto['cliente']['nome']}, ";
                $mensagem .= "residente na cidade {$produto['cliente']['cidade']}, ";
                if (!empty($produto['cliente']['endereco'])) {
                    $mensagem .= "com endereço em {$produto['cliente']['endereco']}, ";
                }
                $mensagem .= "ainda está com a entrega atrasada do {$produto['transportador']['nome']}.";
                $mensagem .= PHP_EOL;
                $mensagem .= 'Também foi identificado que esse cliente possui uma devolução pendente. ';
                $mensagem .= 'Por favor, regularize para que esse débito não seja descontado do Ponto de Coleta.';
                $msgService->sendImageWhatsApp(
                    $produto['ponto_coleta']['telefone'],
                    $produto['foto_produto'],
                    $mensagem
                );
            }
        }
    }
};
