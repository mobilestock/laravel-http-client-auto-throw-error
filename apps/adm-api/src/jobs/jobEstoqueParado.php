<?php

namespace MobileStock\jobs;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\MessageService;
use MobileStock\service\ProdutoService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(MessageService $msgService)
    {
        $qtdDias = ConfiguracaoService::buscaQtdDiasEstoqueParadoFulfillment();
        $produtos = ProdutoService::buscaEstoqueFulfillmentParado();

        foreach ($produtos as $produto) {
            $mensagem = "O produto {$produto['id_produto']} {$produto['nome_comercial']} ";
            $mensagem .= "está com {$produto['quantidade_estoque']} unidade";
            $mensagem .= $produto['quantidade_estoque'] > 1 ? 's ' : ' ';
            $mensagem .= 'no estoque fulfillment ';
            if (empty($produto['data_ultima_venda'])) {
                $mensagem .= "desde {$produto['data_primeira_movimentacao']}, sem ter tido nenhuma venda.";
            } else {
                $mensagem .= "e sua última venda foi dia {$produto['data_ultima_venda']}.";
            }

            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= "Produtos armazenados em nosso galpão logístico que permaneceram mais de $qtdDias dias sem venda ";
            $mensagem .= 'terão o preço reduzido automaticamente pelo sistema em 30% daqui à 30(trinta) dias.';

            $msgService->sendImageWhatsApp($produto['telefone'], $produto['foto_produto'], $mensagem);
        }
    }
};
