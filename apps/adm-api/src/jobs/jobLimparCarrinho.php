<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\PedidoItem;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $produtos = PedidoItem::listarProdutosEsquecidosNoCarrinho();

        foreach ($produtos as $produto) {
            $produto->deleteOrFail();
        }
    }
};
