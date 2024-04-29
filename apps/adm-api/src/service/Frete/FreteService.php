<?php

namespace MobileStock\service\Frete;

use MobileStock\model\Pedido\PedidoItem;

class FreteService
{
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/backend/issues/92
     */
    public const PRODUTO_FRETE = 82044;
    public static function calculaValorFrete(
        int $qtdItensNaoExpedidos,
        int $qtdProdutos,
        float $valorFrete,
        float $valorAdicional
    ): float {
        $qtdMaximaProdutos = PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE;

        $qtdTotalProdutos = $qtdItensNaoExpedidos + $qtdProdutos;
        $qtdFreteAdicional = max(0, $qtdTotalProdutos - $qtdMaximaProdutos);
        $valorFrete +=
            ($qtdItensNaoExpedidos >= $qtdMaximaProdutos ? $qtdProdutos : $qtdFreteAdicional) * $valorAdicional;

        return $valorFrete;
    }
}
