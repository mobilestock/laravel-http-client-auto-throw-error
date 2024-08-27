<?php

use MobileStock\model\ProdutoLogistica;
use test\TestCase;

class GerarEtiquetasSkuTest extends TestCase
{
    public function testVerificaGeracaoSku(): void
    {
        $produtoLogistica = new ProdutoLogistica();
        ProdutoLogistica::geraSku($produtoLogistica);

        $this->assertNotNull($produtoLogistica->sku);
        $this->assertMatchesRegularExpression('/^\d{12,}$/', $produtoLogistica->sku);
    }
}
