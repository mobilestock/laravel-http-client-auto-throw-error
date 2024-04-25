<?php

namespace test;

use Closure;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use MobileStock\helper\ConsoleKernel;

class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * @inheritDoc
     */
    public function createApplication()
    {
        require __DIR__ . '/../bootstrap.php';
        $app = app();
        $app->bind('env', fn() => 'testing');
        $kernel = app(ConsoleKernel::class);
        Closure::bind(
            function () {
                array_splice($this->bootstrappers, array_search(HandleExceptions::class, $this->bootstrappers), 1);
            },
            $kernel,
            $kernel
        )();
        $kernel->bootstrap();

        return $app;
    }
}
