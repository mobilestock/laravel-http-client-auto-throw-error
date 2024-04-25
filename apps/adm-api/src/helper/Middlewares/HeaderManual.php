<?php

namespace MobileStock\helper\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class HeaderManual
{
    public function handle(Request $request, Closure $next, string $headerKey, string $secretKey)
    {
        $authorization = str_replace('Bearer ', '', $request->headers->get($headerKey));
        if ($authorization !== $_ENV[$secretKey]) {
            throw new UnauthorizedHttpException($headerKey, 'Erro de autenticação!');
        }
        return $next($request);
    }
}
