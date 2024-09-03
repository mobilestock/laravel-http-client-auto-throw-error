<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DefineDefaultAuthGuard
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        Auth::setDefaultDriver($guard);

        return $next($request);
    }
}
