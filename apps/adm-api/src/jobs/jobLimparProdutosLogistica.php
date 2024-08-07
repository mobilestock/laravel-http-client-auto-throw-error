<?php

namespace MobileStock\jobs;

use MobileStock\jobs\config\AbstractJob;
use MobileStock\model\LogisticaItemModel;
use MobileStock\model\ProdutoLogistica;

require_once __DIR__ . '/../../vendor/autoload.php';

return new class extends AbstractJob {
    public function run()
    {
        $produtos = LogisticaItemModel::listarProdutosLogisticasLimpar();

        foreach ($produtos as $produto) {
            $produtoLogistica = (new ProdutoLogistica())->newFromBuilder($produto);

            $produtoLogistica->deleteOrFail();
        }
    }
};
