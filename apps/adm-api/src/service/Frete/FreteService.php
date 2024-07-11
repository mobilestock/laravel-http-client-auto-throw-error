<?php

namespace MobileStock\service\Frete;

use MobileStock\model\PedidoItem;

class FreteService
{
   public static function calculaValorFrete(int $qtdItensNaoExpedidos, int $qtdProdutos, float $valorFrete, float $valorAdicional): float
   {
        $qtdMaximaProdutos = PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE;

        $qtdTotalProdutos = $qtdItensNaoExpedidos + $qtdProdutos;
        $qtdFreteAdicional = max(0, $qtdTotalProdutos - $qtdMaximaProdutos);
        $valorFrete += ($qtdItensNaoExpedidos >= $qtdMaximaProdutos ? $qtdProdutos : $qtdFreteAdicional) * $valorAdicional;

        return $valorFrete;
   }
}
