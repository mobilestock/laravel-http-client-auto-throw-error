<?php

namespace App\Providers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!App::isProduction()) {
            App::register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

            // Dependencias do telescope
            App::register(\Illuminate\View\ViewServiceProvider::class);
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
        // Definição padrão de formato de data e hora (timestamp) para o banco de dados
        Blueprint::macro('defaultTimestamps', function ($precision = 0) {
            /** @var Blueprint $this */
            $this->timestamp('created_at', $precision)->useCurrent();
            $this->timestamp('updated_at', $precision)->useCurrent()->useCurrentOnUpdate();
        });
    }
}
