<?php

namespace MobileStock\helper;

use DateTimeZone;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class LoggerFactory
{
    public static function arquivo(string $arquivo): Logger
    {
        $logs = new Logger('LOGGER DEFAULT');
        $logs->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $handler = new StreamHandler(__DIR__ . '/../../log/' . $arquivo);
        $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n", 'd/m/Y H:i:s'));
        $logs->pushHandler($handler);

        return $logs;
    }
}