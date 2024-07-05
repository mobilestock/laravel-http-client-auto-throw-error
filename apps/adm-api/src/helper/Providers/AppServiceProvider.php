<?php

namespace MobileStock\helper\Providers;

use Closure;
use DomainException;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\Logger as IlluminateLogger;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use MobileStock\service\DiaUtilService;
use Monolog\Logger;
use PDO;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Log::withContext([
            'id_container_docker' => gethostname(),
        ]);

        App::bind('log_level', function () {
            return env('APP_LOG_LEVEL') ?? app()['config']['logging.level'];
        });

        # -----------------------------------------[PHP8.0 POLYFILL (depreciado)]------------------------------------- #

        App::alias(LoggerInterface::class, Logger::class);
        App::alias(LoggerInterface::class, IlluminateLogger::class);

        # ----------------------------------------------[PHP8.0 POLYFILL]--------------------------------------------- #

        # --------------------------------------------------[EVENTS]-------------------------------------------------- #

        Event::listen(function (RouteMatched $matched) {
            Log::withContext([
                'url' => $matched->request->url(),
                'ip' => $matched->request->ip(),
                'http_method' => $matched->request->method(),
                'referrer' => $matched->request->headers->get('referer'),
                'route' => $matched->route->getAction('controller'),
                'title' => class_basename($matched->route->getController()) . ' ' . $matched->route->getActionMethod(),
                'request' => [
                    'headers' => $matched->request->headers->all(),
                    'request' => $matched->request->all(),
                ],
            ]);
        });

        Event::listen(function (JobProcessing $event) {
            $context = [];
            if ($command = data_get($event->job->payload(), 'data.command')) {
                try {
                    $job = unserialize($command);
                    if ($job === false) {
                        throw new DomainException('');
                    }
                } catch (\Throwable $exception) {
                    Log::withContext($event->job->payload());
                    $event->job->delete();
                    throw new DomainException('Foi enviado para o queue:work um job inserializável.');
                }

                Closure::bind(
                    function () use ($job, &$context) {
                        $properties = get_object_vars($job);

                        $parent = get_parent_class($job);
                        $traits = class_uses_recursive($job);

                        foreach ($properties as $property => $val) {
                            if (
                                ($parent && property_exists($parent, $property)) ||
                                collect($traits)->some(fn($trait) => property_exists($trait, $property))
                            ) {
                                continue;
                            }

                            $context['queue'][$property] = $val;
                        }
                    },
                    $job,
                    $job
                )();
            }
            $context['queue']['uuid'] = $event->job->getJobId();
            $context['queue']['attempts'] = $event->job->attempts();

            Log::withContext(
                array_merge($context, [
                    'title' => mb_substr($event->job->resolveName(), 12),
                ])
            );
        });

        Event::listen(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_ASSOC);
        });

        # --------------------------------------------------[EVENTS]-------------------------------------------------- #

        # -----------------------------------------------[SINGLETON'S]------------------------------------------------ #

        App::singleton(DiaUtilService::class);

        # ----------------------------------------------[HTTP CLIENTS]------------------------------------------------ #

        Http::macro('googleMaps', function (): PendingRequest {
            return Http::baseUrl('https://maps.googleapis.com/maps/api/geocode')->withOptions([
                'query' => [
                    'key' => env('GOOGLE_TOKEN_GEOLOCALIZACAO'),
                    'language' => 'PT-BR',
                    'location_type' => 'ROOFTOP',
                    'result_type' => 'street_address',
                ],
            ]);
        });

        # ----------------------------------------------[HTTP CLIENTS]------------------------------------------------ #
    }
}
