<?php

use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use MobileStock\LaravelHttpClientAutoThrowError\HttpClientAutoThrowErrorServiceProvider;

describe('when calling boot() method', function () {
    beforeEach(function () {
        $this->eventSpy = Event::spy();
        $this->requestExceptionSpy = Mockery::spy('alias:' . RequestException::class);
        $serviceProvider = new HttpClientAutoThrowErrorServiceProvider(App::getFacadeRoot());

        $serviceProvider->boot();
    });

    it('should listen to a ResponseReceived event', function () {
        $this->eventSpy
            ->shouldHaveReceived('listen')
            ->withArgs(function (callable $callable) {
                $firstParameterType = (new ReflectionFunction($callable))->getParameters()[0]->getType()->getName();
                expect($firstParameterType)->toBe(ResponseReceived::class);

                return true;
            })
            ->once();
    });

    it('should call RequestException::dontTruncate', function () {
        $this->requestExceptionSpy->shouldHaveReceived('dontTruncate')->once();
    });

    afterEach(function () {
        Event::clearResolvedInstance('events');
    });
});
