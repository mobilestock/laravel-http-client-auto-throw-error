<?php

namespace App\Http\Middleware;

use App\Attributes\DBTransaction;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class HandleResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $action = explode('@', $request->route()->getAction('controller'));
        $reflectionMethod = new \ReflectionMethod(...$action);
        $reflectionAttributes = $reflectionMethod->getAttributes();

        $isTransaction = ! empty(array_filter(
            $reflectionAttributes,
            fn ($attribute) => $attribute->getName() === DBTransaction::class
        ));

        if ($isTransaction) {
            $response = DB::transaction(function () use ($request, $next) {
                /** @var $response JsonResponse */
                $response = $next($request);

                if (!empty($response->exception)) {
                    throw $response->exception;
                }

                return $response;
            });
        } else {
            $response = $next($request);
        }

        return $response;
    }
}
