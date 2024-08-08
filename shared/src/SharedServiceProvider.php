<?php

namespace MobileStock\Shared;

use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use MobileStock\Shared\MacroService\Laravel\MacroServiceProvider;
use MobileStock\Shared\PdoInterceptor\Laravel\PdoInterceptorServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        App::register(MacroServiceProvider::class);
        App::register(PdoInterceptorServiceProvider::class);
        App::register(BusServiceProvider::class);
        App::register(HashServiceProvider::class);
        App::register(ConsoleSupportServiceProvider::class);
        App::register(CacheServiceProvider::class);
        App::register(FilesystemServiceProvider::class);
    }
}
