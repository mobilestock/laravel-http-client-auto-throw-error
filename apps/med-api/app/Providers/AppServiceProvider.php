<?php

namespace App\Providers;

use App\Models\Loja;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Tymon\JWTAuth\Factory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->app->isProduction()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

            // Dependencias do telescope
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(ViewServiceProvider::class);
            $this->app->register(\Illuminate\Session\SessionServiceProvider::class);
            return;
        }

        // Habilitador de funções específicas do view
        $this->app->bind('view', function () {
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
        // Configurador responsável por definir as opções de resposta durante a conversão do JSON Web Token (JWT)
        $this->app->singleton('tymon.jwt.payload.factory', function ($app) {
            $factory = new Factory($app['tymon.jwt.claim.factory'], $app['tymon.jwt.validators.payload']);
            $factory->setDefaultClaims(config('jwt.required_claims'));

            return $factory;
        });

        $this->app->get(Request::class)->headers->set('Accept', 'application/json');

        Http::macro('mobileStock', function (): PendingRequest {
            $loja = app(Loja::class);
            $http = Http::baseUrl(env('MOBILE_STOCK_API_URL'));
            //            $http->withOptions([
            //                'timeout' => 0,
            //                'connect_timeout' => 0,
            //            ]);
            if (!empty($loja->token)) {
                $http->withHeaders([
                    'token' => $loja->token,
                    'auth' => $loja->auth,
                ]);
            }
            $http->withHeaders([
                'referer' => env('APP_URL'),
            ]);
            return $http;
        });

        // Definição padrão de formato de data e hora (timestamp) para o banco de dados
        Blueprint::macro('defaultTimestamps', function ($precision = 0) {
            /** @var Blueprint $this */
            $this->timestamp('data_criacao', $precision)->useCurrent();
            $this->timestamp('data_atualizacao', $precision)->useCurrent()->useCurrentOnUpdate();
        });

        DB::macro('tableMS', function (string $tableName, ?string $as = null) {
            /** @var DB $this */
            return $this->table(env('DB_DATABASE_MOBILE_STOCK') . ".$tableName", $as);
        });
    }
}
