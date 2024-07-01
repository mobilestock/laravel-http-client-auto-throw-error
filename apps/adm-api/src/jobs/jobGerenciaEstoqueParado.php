<?php

namespace MobileStock\jobs;

use Illuminate\Support\Carbon;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\MessageService;
use MobileStock\service\ProdutoService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(MessageService $msgService)
    {
        $qtdDias = ConfiguracaoService::buscaQtdMaximaDiasEstoqueParadoFulfillment();
        $produtos = ProdutoService::buscaEstoqueFulfillmentParado();

        foreach ($produtos as $produto) {
            $dataUltimaVenda = empty($produto['data_ultima_venda'])
                ? null
                : Carbon::createFromFormat('d/m/Y H:i', $produto['data_ultima_venda']);
            $dataUltimaEntrada = Carbon::createFromFormat('d/m/Y H:i', $produto['data_ultima_entrada']);

            $mensagem = '*Mensagem automática:*';
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= "O produto {$produto['id_produto']} {$produto['nome_comercial']} ";
            $mensagem .= "está com {$produto['quantidade_estoque']} unidade";
            $mensagem .= $produto['quantidade_estoque'] > 1 ? 's ' : ' ';
            $mensagem .= 'no estoque fulfillment ';
            if (empty($dataUltimaVenda) || $dataUltimaEntrada > $dataUltimaVenda) {
                $mensagem .= "desde {$produto['data_ultima_entrada']}, sem ter tido nenhuma venda.";
            } else {
                $mensagem .= "desde {$produto['data_ultima_venda']}, sem ter tido nenhuma venda.";
            }

            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= "Produtos armazenados em nosso galpão logístico que permanecerem mais de $qtdDias dias sem venda ";
            $mensagem .= 'terão o preço reduzido automaticamente pelo sistema em 30% daqui à 30 dias.';

            $msgService->sendImageWhatsApp($produto['telefone'], $produto['foto_produto'], $mensagem);
        }
    }
};
