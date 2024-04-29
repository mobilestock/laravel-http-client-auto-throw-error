<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\MessageService;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $produtosAtrasados = LogisticaItemModel::buscaProdutosComConferenciaAtrasada();
        if (empty($produtosAtrasados)) {
            return;
        }

        $msgService = app(MessageService::class);
        foreach ($produtosAtrasados as $produto) {
            $mensagem = "Produto *{$produto['id_produto']}* - *{$produto['nome_tamanho']}* atrasado!";
            $mensagem .= PHP_EOL . PHP_EOL;
            $mensagem .= 'Caso não seja separado, sua venda será cancelada no próximo dia útil. ';
            $mensagem .= 'Caso não tenha o produto em estoque, você pode sugerir a substituição por outro produto ';
            $mensagem .= 'semelhante ao cliente.';
            $msgService->sendImageWhatsApp($produto['telefone'], $produto['foto_produto'], $mensagem);
        }
    }
};
