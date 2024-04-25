<?php

namespace MobileStock\helper\Middlewares;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use ReflectionException;
use ReflectionMethod;

class TypedBindsToRoute
{
    protected Router $router;
    protected Application $app;

    public function __construct(Router $router, Application $app)
    {
        $this->router = $router;
        $this->app = $app;
    }

    /**
     * @throws ReflectionException
     */
    public function handle(Request $request, Closure $next)
    {
        $route = $this->router->getRoutes()->match($request);

        if (!$route->parameters) {
            return $next($request);
        }

        $action = explode('@', $route->getAction('controller'));
        $reflection = new ReflectionMethod(...$action);
        $reduceToRouterKey = 0;
        foreach ($reflection->getParameters() as $parameterKey => $parameter) {
            if (
                ($parameter->hasType() && $this->app->bound($parameter->getType()->getName())) ||
                ($parameter->hasType() && class_exists($parameter->getType()->getName()))
            ) {
                $reduceToRouterKey++;
                continue;
            }

            $parameterType = $parameter->hasType() ? $parameter->getType()->getName() : 'mixed';

            if ($parameter->hasType() && $parameter->getType()->allowsNull()) {
                $reduceToRouterKey++;
                continue;
            }
            $pathParam = array_keys($route->parameters)[$parameterKey - $reduceToRouterKey];

            if ($parameterType === 'int') {
                $route->where($pathParam, '[0-9]+');
                $route->compiled = null;
            } else {
                $reduceToRouterKey++;
            }
        }

        return $next($request);
    }
}
