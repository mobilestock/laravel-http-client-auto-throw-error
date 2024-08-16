<?php

use Aws\Sqs\SqsClient;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use MobileStock\database\Conexao;
use MobileStock\database\PdoCallMiddleware;
use MobileStock\helper\Conversores\ConversorTelegram;
use MobileStock\helper\HttpKernel;
use MobileStock\helper\Monolog\Handlers\OpenSearchHandler;
use MobileStock\helper\Providers\AppServiceProvider;
use MobileStock\helper\Providers\AuthServiceProvider;
use MobileStock\helper\Providers\MacroServiceProvider;
use MobileStock\helper\Providers\MysqlReplicationServiceProvider;
use MobileStock\helper\Providers\QueueServiceProvider;
use MobileStock\service\Cache\CacheManager;
use MobileStock\service\DiaUtilService;
use MobileStock\Shared\PdoInterceptor\Middlewares\CastWithDatabaseColumns;
use MobileStock\Shared\SharedServiceProvider;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger;
use ShiftOneLabs\LaravelSqsFifoQueue\LaravelSqsFifoQueueServiceProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Contracts\Cache\CacheInterface;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application($_ENV['APP_BASE_PATH'] ?? __DIR__);

$app->bind(PDO::class, fn() => Conexao::criarConexao());
$app->bind(AbstractAdapter::class, function (Container $app) {
    if (in_array($_ENV['AMBIENTE'], ['producao', 'homologado'])) {
        return CacheManager::redis();
    }

    return new PdoAdapter($app->make(PDO::class));
});
$app->alias(AbstractAdapter::class, CacheInterface::class);
$app->singleton('config', function () {
    $telegramConfig = $telegramConfigDefault = [
        'driver' => 'monolog',
        'handler' => TelegramBotHandler::class,
        'handler_with' => [
            'apiKey' => env('TELEGRAM_LOG_TOKEN'),
            'channel' => env('TELEGRAM_LOG_CHAT_ID'),
            'parseMode' => 'MarkdownV2',
        ],
        'formatter' => ConversorTelegram::class,
    ];
    $telegramConfigDefault['handler_with']['level'] = Logger::CRITICAL;

    return new Repository([
        'logging' => [
            'default' => 'stack',
            'channels' => [
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['telegram_default', App::isProduction() ? 'opensearch' : 'file'],
                ],
                'opensearch' => [
                    'driver' => 'monolog',
                    'handler' => OpenSearchHandler::class,
                    'handler_with' => [
                        'bubble' => false,
                    ],
                    'formatter' => NormalizerFormatter::class,
                ],
                'file' => [
                    'driver' => 'single',
                    'path' => __DIR__ . '/log/laravel.log',
                ],
                'telegram_default' => $telegramConfigDefault,
                'telegram' => $telegramConfig,
                'emergency' => [
                    'path' => 'log/emergency.log',
                ],
            ],
        ],
        'app' => [
            'debug' => !App::isProduction(),
            'providers' => [
                SharedServiceProvider::class,
                LogServiceProvider::class,
                AuthServiceProvider::class,
                MacroServiceProvider::class,
                AppServiceProvider::class,
                QueueServiceProvider::class,
                MysqlReplicationServiceProvider::class,
                LaravelSqsFifoQueueServiceProvider::class,
            ],
        ],
        'auth' => [
            'defaults' => [
                'guard' => 'user',
            ],
            'guards' => [
                'user' => [
                    'driver' => 'token_adm',
                    'provider' => 'user',
                    'input_key' => 'token',
                ],
            ],
        ],
        'database' => [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => env('MYSQL_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => env('MYSQL_DB_NAME', 'forge'),
                    'username' => env('MYSQL_USER', 'forge'),
                    'password' => env('MYSQL_PASSOWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
                'mysql_read_only' => [
                    'driver' => 'mysql',
                    'host' => env('MYSQL_HOST'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => env('MYSQL_DB_NAME'),
                    'username' => env('MYSQL_USER_READ_ONLY'),
                    'password' => env('MYSQL_PASSWORD_READ_ONLY'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ],
        ],
        'queue' => [
            'default' => 'sqs',
            'connections' => [
                'sqs' => [
                    'driver' => 'sqs',
                    'prefix' => env('AWS_PREFIX'),
                    'queue' => 'padrao',
                    'region' => env('AWS_DEFAULT_REGION', 'sa-east-1'),
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ],
        ],
        'pdo-interceptor' => [
            'middlewares' => fn() => [
                PdoCallMiddleware::class,
                new CastWithDatabaseColumns([
                    'tem',
                    'eh',
                    'pode',
                    'falta',
                    'esta',
                    'ja',
                    'possui',
                    'permite',
                    'deve',
                    'existe',
                    'afetou',
                    'em',
                ]),
            ],
        ],
    ]);
});
$app->alias(MobileStock\helper\ExceptionHandler::class, ExceptionHandler::class);
$app->singleton(Request::class, function () {
    $request = Request::capture();
    $request->headers->set('Accept', 'application/json,text/plain');
    if ($request->getContentType() !== 'form') {
        $request->headers->set('Content-Type', 'application/json');
    }
    $request->query->remove('route');

    return $request;
});

$app->bind(
    SqsClient::class,
    fn() => new SqsClient([
        'region' => 'sa-east-1',
        'version' => 'latest',
    ])
);
$app->alias(HttpKernel::class, Kernel::class);
$app->bind('env', fn() => env('AMBIENTE') === 'producao' ? 'production' : 'local');
// https://github.com/mobilestock/backend/issues/154
Carbon::macro('acrescentaDiasUteis', function (int $qtdMinimaDia): Carbon {
    /** @var Carbon $this */
    $diaUtilService = app(DiaUtilService::class);
    $diasUteis = $diaUtilService->buscaCacheProximosDiasUteis();
    $pegaData = fn(string $data) => Carbon::createFromFormat('Y-m-d', $data)->setTime(0, 0, 0);
    $ultimaDataUtil = $pegaData(end($diasUteis));
    $index = 0;
    while ($qtdMinimaDia > 0) {
        if ($index > DiaUtilService::LIMITE_DIAS_CALCULOS * 3) {
            throw new DomainException('Não foi possível encontrar um dia útil');
        }
        $dataAtual = clone $pegaData($this->format('Y-m-d'));
        if ($dataAtual->gte($ultimaDataUtil)) {
            $proximosDiasUteis = $diaUtilService->buscaProximosDiasUteis($dataAtual);
            $diasUteis = array_merge($diasUteis, $proximosDiasUteis);
            $ultimaDataUtil = $pegaData(end($diasUteis));
        }

        $this->add(new DateInterval('P1D'));
        $ehDiaUtil = in_array($this->format('Y-m-d'), $diasUteis);
        if ($ehDiaUtil) {
            $qtdMinimaDia--;
        }

        $index++;
    }

    return $this;
});
Carbon::setLocale('pt_BR');
