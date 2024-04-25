<?php

namespace MobileStock\helper;

use Illuminate\Foundation\Http\Kernel;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\helper\Middlewares\TypedBindsToRoute;
use Psr\Log\LogLevel;

class HttpKernel extends Kernel
{
    protected $bootstrappers = Globals::BOOTSTRAPPERS;

    protected $middleware = [TypedBindsToRoute::class];

    protected $middlewareGroups = [
        'api_pagamento' => [SetLogLevel::class . ':' . LogLevel::CRITICAL],

        'api_meulook' => [],

        'api_cliente' => [],

        'api_administracao' => [],

        'api_estoque' => [],

        'api_webhooks' => [SetLogLevel::class . ':' . LogLevel::CRITICAL],
    ];

    protected $routeMiddleware = [
        'permissao' => \MobileStock\helper\Middlewares\Auth\Authorize::class,
    ];
}
