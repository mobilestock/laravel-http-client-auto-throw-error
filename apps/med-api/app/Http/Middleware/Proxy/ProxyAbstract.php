<?php

namespace App\Http\Middleware\Proxy;

use Symfony\Component\HttpFoundation\Response;

use Illuminate\Http\Request;
use Closure;

abstract class ProxyAbstract
{
    protected function preRequisicao(): void
    {
    }
    protected function posRequisicao(Response $response): void
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->preRequisicao();

        /** @var Response $response */
        $response = $next($request);

        if (!$response->isOk()) {
            return $response;
        }

        $this->posRequisicao($response);

        return $response;
    }
}
