<?php

namespace MobileStock\helper\Middlewares;

use MobileStock\helper\Retentador;
use MobileStock\helper\RetryableException;
use Throwable;

class RetryMiddleware
{
    /**
     * @throws Throwable
     * @throws RetryableException
     */
    public function handle($input, $next, $quantidade = 5)
    {
        return Retentador::retentar($quantidade, fn() => $next($input));
    }
}