<?php

namespace MobileStock\Shared\MacroService;

use Closure;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Traits\ReflectsClosures;

class EventListenOnceMacro
{
    use ReflectsClosures;

    /**
     * Código totalmente copiado de Illuminate\Support\Facades\Event::listen
     * @param Closure|string|array  $events
     * @param Closure|string|array|null  $listener
     */
    public function __invoke($events, $listener = null)
    {
        if ($events instanceof Closure) {
            return collect($this->firstClosureParameterTypes($events))->each(function ($event) use ($events) {
                $this->listen($event, $events);
            });
        } elseif ($events instanceof QueuedClosure) {
            return collect($this->firstClosureParameterTypes($events->closure))->each(function ($event) use ($events) {
                $this->listen($event, $events->resolve());
            });
        } else {
            return $this->listen($events, $listener);
        }
    }

    public function listen($event, $listener = null): void
    {
        Event::listen($event, function (...$params) use ($event, $listener) {
            $resultado = $listener(...$params);

            $newThis = Event::getFacadeRoot();
            Closure::bind(
                function () use ($event) {
                    /** @var object $this */
                    array_pop($this->listeners[$event]);
                },
                $newThis,
                get_class($newThis)
            )();

            return $resultado;
        });
    }
}
