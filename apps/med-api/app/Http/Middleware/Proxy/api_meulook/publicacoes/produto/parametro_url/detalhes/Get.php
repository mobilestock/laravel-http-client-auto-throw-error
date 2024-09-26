<?php

namespace App\Http\Middleware\Proxy\api_meulook\publicacoes\produto\parametro_url\detalhes;

use App\Helpers\CustomProxyHelper;
use App\Http\Middleware\Proxy\ProxyAbstract;
use App\Models\Loja;
use Symfony\Component\HttpFoundation\Response;

/**
 * Get: api_meulook/publicacoes/produto/{id_produto}/detalhes
 */
class Get extends ProxyAbstract
{
    public function preRequisicao(): void
    {
        CustomProxyHelper::adicionaOrigem();
    }

    public function posRequisicao(Response $response): void
    {
        $detalhesProduto = json_decode($response->getContent(), true);
        $detalhesProduto['valor'] = app(Loja::class)->aplicaRemarcacao($detalhesProduto['valor']);
        $response->setContent(json_encode($detalhesProduto));
    }
}
