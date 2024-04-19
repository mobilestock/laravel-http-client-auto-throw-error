<?php

namespace App\Providers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Symfony\Component\HttpFoundation\ParameterBag;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!App::isProduction()) {
            App::register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

            // Dependencias do telescope
            App::register(\Laravel\Telescope\TelescopeServiceProvider::class);
            App::register(TelescopeServiceProvider::class);
            App::register(ViewServiceProvider::class);
            App::register(\Illuminate\Session\SessionServiceProvider::class);
            return;
        }

        // Habilitador de funções específicas do view
        App::bind('view', function () {
            return new class implements ViewFactory {
                public function exists($view)
                {
                }

                public function file($path, $data = [], $mergeData = [])
                {
                }

                public function make($view, $data = [], $mergeData = [])
                {
                }

                public function share($key, $value = null)
                {
                }

                public function composer($views, $callback)
                {
                }

                public function creator($views, $callback)
                {
                }

                public function addNamespace($namespace, $hints)
                {
                }

                public function replaceNamespace($namespace, $hints)
                {
                }
            };
        });
    }

    public function boot(): void
    {
        Request::instance()->headers->set('Accept', 'application/json');
        $contentLanguage = Request::instance()->headers->get('Content-Language', 'en-US');

        $convertRecursive = function (array $data, callable $trans) use (&$convertRecursive) {
            foreach ($data as $key => $value) {
                if (!is_numeric($key)) {
                    unset($data[$key]);
                    $data[($key = $trans($key))] = $value;
                }

                if (is_array($value)) {
                    $value = $convertRecursive($value, $trans);
                    $data[$key] = $value;
                }
            }
            return $data;
        };

        if ($contentLanguage !== 'en-US') {
            $replaceTrans = Lang::getLoader()->load($contentLanguage, '*', '*');
            Lang::setLoaded($replaceTrans);

            $translate = fn(string $key) => array_flip($replaceTrans)[$key];

            if (Request::isJson()) {
                Request::setJson(new ParameterBag($convertRecursive(Request::json()->all(), $translate)));
            }

            if (!empty(Request::instance()->query->all())) {
                Request::instance()->query->replace($convertRecursive(Request::instance()->query->all(), $translate));
            }

            Event::listen(function (RequestHandled $event) use ($convertRecursive, $contentLanguage) {
                if ($event->response->headers->get('Content-Type') !== 'application/json') {
                    return;
                }

                $content = json_decode($event->response->getContent(), true);

                $event->response->setContent(
                    json_encode($convertRecursive($content, fn(string $key) => Lang::get($key, [], $contentLanguage)))
                );
            });
        }

        // Definição padrão de formato de data e hora (timestamp) para o banco de dados
        Blueprint::macro('defaultTimestamps', function ($precision = 0) {
            /** @var Blueprint $this */
            $this->timestamp('created_at', $precision)->useCurrent();
            $this->timestamp('updated_at', $precision)->useCurrent()->useCurrentOnUpdate();
        });

        Blueprint::macro('uuidPrimary', function () {
            /** @var Blueprint $this */
            $this->uuid('id')->primary()->default(DB::raw('UUID()'));
        });

        Http::macro('iugu', function (): PendingRequest {
            $http = Http::baseUrl('https://api.iugu.com/v1/');
            return $http;
        });

        Http::macro('mobilestock', function (): PendingRequest {
            $http = Http::baseUrl(env('MOBILE_STOCK_API_URL'));
            $http->withHeaders([
                'Authorization' => 'Bearer ' . env('SECRET_MOBILE_STOCK_API_TOKEN'),
            ]);
            return $http;
        });
    }
}
