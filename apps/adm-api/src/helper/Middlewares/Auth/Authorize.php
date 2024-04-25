<?php

namespace MobileStock\helper\Middlewares\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;

class Authorize
{
    protected Gate $gate;

    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * @throws AuthorizationException
     */
    public function handle($request, $next, ...$abities)
    {
        $response = $this->gate->any($abities) ? Response::allow() : Response::deny();

        $response->authorize();

        return $next($request);
    }
}