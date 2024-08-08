<?php

namespace MobileStock\Shared\MacroService\Laravel;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use MobileStock\Shared\MacroService\EventListenOnceMacro;

class MacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::macro('listenOnce', function ($events, $listener = null) {
            app(EventListenOnceMacro::class)->__invoke($events, $listener);
        });
    }
}
