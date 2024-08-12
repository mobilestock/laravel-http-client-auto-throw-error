<?php

use Illuminate\Support\Facades\Event;

class EventListenOnceMacroTest extends test\TestCase
{
    public function testListenOnce()
    {
        $contador = 0;
        Event::listenOnce('event', static function () use (&$contador) {
            $contador++;
        });

        Event::dispatch('event');
        Event::dispatch('event');

        $this->assertEquals(1, $contador);
    }

    public function testListenOnceWithDispatchHalt()
    {
        $contador = 0;
        Event::listenOnce('event', static function () use (&$contador) {
            $contador++;
            return 'response';
        });

        Event::until('event');
        Event::until('event');

        $this->assertEquals(1, $contador);
    }

    public function testListenOnceWithStopPropagation()
    {
        $contador = 0;
        Event::listenOnce('event', static function () use (&$contador) {
            $contador++;
            return false;
        });

        Event::dispatch('event');
        Event::dispatch('event');

        $this->assertEquals(1, $contador);
    }
}