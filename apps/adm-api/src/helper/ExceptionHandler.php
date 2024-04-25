<?php

namespace MobileStock\helper;

use Illuminate\Foundation\Exceptions\Handler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class ExceptionHandler extends Handler
{
    /**
     * @inheritDoc
     */
    public function report(Throwable $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        try {
            $level = app('log_level') ?? LogLevel::ERROR;
            $logger = $this->container->make(LoggerInterface::class);
            $logger->{$level}($e->getMessage(), [
                'exception' => $e,
            ]);
        } catch (Throwable $e) {
            // Ignorar
        }
    }

    /**
     * @inheritDoc
     */
    public function shouldReport(Throwable $e): bool
    {
        return parent::shouldReport($e) && !$e instanceof ClienteException;
    }
}
