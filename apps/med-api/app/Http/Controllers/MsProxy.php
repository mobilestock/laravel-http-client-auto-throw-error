<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class MsProxy extends Controller
{
    /**
     * @issue https://github.com/mobilestock/med-api/issues/34
     */
    public function __construct()
    {
        $caminho = Request::path();
        $caminhoModificado = preg_replace('/\d+/', 'parametro_url', $caminho);
        $middleware =
            '\\App\\Http\\Middleware\\Proxy\\' .
            Str::of($caminhoModificado)->replace('/', '\\')->toString() .
            '\\' .
            ucfirst(mb_strtolower(Request::method()));

        if (!class_exists($middleware)) {
            return;
        }

        $this->middleware($middleware);
    }

    public function __invoke(ServerRequestInterface $request, Client $client)
    {
        $admApi = env('ADM_API_URL');
        $uriOriginal = $request->getUri();

        // @issue: https://github.com/mobilestock/med-api/issues/39
        $uri = (new Uri($uriOriginal))
            ->withPath($uriOriginal->getPath())
            ->withQuery($uriOriginal->getQuery())
            ->withHost(parse_url($admApi, PHP_URL_HOST))
            ->withScheme(parse_url($admApi, PHP_URL_SCHEME))
            ->withPort(parse_url($admApi, PHP_URL_PORT));

        $request = $request->withUri($uri);

        try {
            $response = $client->send($request, Http::mobileStock()->getOptions());
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
        }
        $response = (new HttpFoundationFactory())->createResponse($response, empty($this->getMiddleware()));

        $response->headers->remove('Transfer-Encoding');
        $response->headers->remove('X-Forwarded-For');
        return $response;
    }
}
