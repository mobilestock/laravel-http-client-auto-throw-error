<?php

namespace App\Http\Middleware\Proxy\api_meulook\produtos\catalogo;

use App\Helpers\CustomProxyHelper;
use App\Http\Middleware\Proxy\ProxyAbstract;
use App\Models\Loja;
use Symfony\Component\HttpFoundation\Response;

/**
 * Get: api_meulook/produtos/catalogo
 */
class Get extends ProxyAbstract
{
    public function preRequisicao(): void
    {
        CustomProxyHelper::adicionaOrigem();
    }

    public function posRequisicao(Response $response): void
    {
        $produtos = json_decode($response->getContent(), true);

        if (is_array($produtos)) {
            $loja = app(Loja::class);
            $produtos = array_map(function (array $produto) use ($loja): array {
                $produto['preco_original'] = $loja->aplicaRemarcacao($produto['preco_original']);
                $produto['preco'] = $loja->aplicaRemarcacao($produto['preco']);
                unset($produto['valor_parcela'], $produto['parcelas']);

                return $produto;
            }, $produtos);
        }

        $response->setContent(json_encode($produtos));
    }
}
