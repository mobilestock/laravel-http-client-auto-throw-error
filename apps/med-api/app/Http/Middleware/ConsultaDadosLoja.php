<?php

namespace App\Http\Middleware;

use App\Models\Loja;
use Closure;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

class ConsultaDadosLoja
{
    public function handle(Request $request, Closure $next): Response
    {
        $urlLoja = $request->header('Loja');
        Loja::consultaLoja($urlLoja);

        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $response = $event->response;
            $response->headers->set('Med-Loja', app(Loja::class)->toJson());
        });

        return $next($request);
    }
}
