<?php

namespace MobileStock\LaravelAssertiveHttpClient;

use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class HttpClientAutoThrowErrorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(fn(ResponseReceived $event) => $event->response->throw());
        RequestException::dontTruncate();
    }
}
