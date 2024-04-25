<?php

namespace MobileStock\database\exceptions;

use MobileStock\helper\RetryableException;

class PDOExceptionDeadlock extends \PDOException implements RetryableException
{
    public function __construct($message = 'Ocorreu um erro inesperado no sistema, tente novamente em alguns minutos.', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function retries(int $routeRetries)
    {
        return $routeRetries;
    }
}