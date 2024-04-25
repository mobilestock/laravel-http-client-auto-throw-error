<?php

namespace MobileStock\helper\Providers;

use Illuminate\Queue\QueueServiceProvider as QueueQueueServiceProvider;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;

class QueueServiceProvider extends QueueQueueServiceProvider
{
    protected function registerWorker()
    {
        App::singleton('queue.worker', function ($app) {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            $resetScope = function () use ($app) {
                if (method_exists($app['log']->driver(), 'withoutContext')) {
                    $app['log']->withoutContext();
                }

                DB::disconnect();
                Facade::clearResolvedInstance('auth');

                return $app->forgetScopedInstances();
            };

            return app(Worker::class, ['isDownForMaintenance' => $isDownForMaintenance, 'resetScope' => $resetScope]);
        });
    }
}
