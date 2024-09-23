<?php

namespace App\Helpers;

use App\Models\Loja;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\App;
use Psr\Http\Message\ServerRequestInterface;

class CustomProxyHelper
{
    public static function adicionaOrigem(): void
    {
        $request = app(ServerRequestInterface::class);
        $origem = app(Loja::class)->base_produtos->value;
        $parametros = array_merge($request->getQueryParams(), ['origem' => $origem]);
        $requisicaoModificada = Utils::modifyRequest($request, ['query' => http_build_query($parametros)]);

        App::instance(ServerRequestInterface::class, $requisicaoModificada);
    }
}
