<?php

namespace MobileStock\helper;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteRegistrar;
use MobileStock\helper\Rotas\Router;

class RouterAdapter
{

    /**
     * @deprecated
     * https://github.com/mobilestock/web/wiki/Como-migrar-para-o-router-novo%3F
     */
    public Router $router;
    /**
     * @var RouteRegistrar&\Illuminate\Routing\Router
     */
    public \Illuminate\Routing\Router $routerLaravel;
    public Request $request;
    protected Kernel $httpKernel;

    public function __construct(
        Router $router,
        \Illuminate\Routing\Router $routerLaravel,
        Request $request,
        Kernel $httpKernel
    ) {
        $this->router = $router;
        $this->routerLaravel = $routerLaravel;
        $this->request = $request;
        $this->httpKernel = $httpKernel;
    }

    public function dispatch()
    {
        if (!$this->router->dispatch()) {
            foreach ($this->routerLaravel->getRoutes()->getRoutes() as $route) {
                $middleware = last(explode('/', $this->request->getBaseUrl()));
                $route->middleware($middleware);
            }
            $response = $this->httpKernel->handle($this->request)->send();

            $this->httpKernel->terminate($this->request, $response);
        }
    }
}