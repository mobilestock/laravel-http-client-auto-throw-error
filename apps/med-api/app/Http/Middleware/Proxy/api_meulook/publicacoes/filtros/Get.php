<?php

namespace App\Http\Middleware\Proxy\api_meulook\publicacoes\filtros;

use App\Helpers\CustomProxyHelper;
use App\Http\Middleware\Proxy\ProxyAbstract;

/**
 * Get: api_meulook/publicacoes/filtros
 */
class Get extends ProxyAbstract
{
    public function preRequisicao(): void
    {
        CustomProxyHelper::adicionaOrigem();
    }
}
