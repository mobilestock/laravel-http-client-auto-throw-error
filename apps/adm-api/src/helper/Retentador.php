<?php

namespace MobileStock\helper;

use Throwable;

abstract class Retentador
{
    /**
     * @throws RetryableException|Throwable
     * @return mixed
     */
    public static function retentar(int $qtdTentativasMax, callable $processo)
    {
        $retryableExceptions = [];

        for ($qtdTentativas = 1; $qtdTentativas <= $qtdTentativasMax; $qtdTentativas++) {
            try {
                return call_user_func($processo);
            } catch (RetryableException $exception) {
                if ($qtdTentativasMax !== $qtdTentativas) {
                    sleep(min($qtdTentativas, 15));

                    $exceptionName = get_class($exception);
                    if (!in_array($exceptionName, $retryableExceptions)) {
                        $retryableExceptions[] = $exceptionName;
                        $qtdTentativasMax = $exception->retries($qtdTentativasMax);
                    }
                    continue;
                }

                throw $exception;
            }
        }
    }
}
