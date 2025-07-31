<?php

namespace MobileStock\LaravelHttpClientAutoThrowError;

use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class HttpClientAutoThrowErrorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(fn(ResponseReceived $event): Response => $event->response->throw());
        RequestException::dontTruncate();
    }
}
