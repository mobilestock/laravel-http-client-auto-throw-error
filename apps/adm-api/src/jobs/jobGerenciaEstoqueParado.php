<?php

namespace MobileStock\jobs;

use Illuminate\Support\Carbon;
use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\ProdutoModel;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run(MessageService $msgService)
    {
        $configuracoes = ConfiguracaoService::buscaConfiguracoesJobGerenciaEstoqueParado();
        $porcentagemDesconto = $configuracoes['percentual_desconto'];

        $produtos = ProdutoModel::buscaEstoqueFulfillmentParado();

        foreach ($produtos as $produto) {
            if ($produto['deve_baixar_preco']) {
                $produtoAtualizar = new ProdutoModel();
                $produtoAtualizar->id = $produto['id_produto'];
                $produtoAtualizar->valor_custo_produto = max(
                    ($produto['valor_custo_produto'] * (100 - $porcentagemDesconto)) / 100,
                    1
                );
                $produtoAtualizar->exists = true;
                $produtoAtualizar->save();
                continue;
            }

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
            $mensagem .= "Produtos armazenados em nosso galpão logístico que permanecerem mais de {$configuracoes['qtd_maxima_dias']} dias sem venda ";
            $mensagem .= "terão o preço reduzido automaticamente pelo sistema em {$porcentagemDesconto}% daqui à {$configuracoes['dias_carencia']} dias.";

            $msgService->sendImageWhatsApp($produto['telefone'], $produto['foto_produto'], $mensagem);
        }
    }
};
