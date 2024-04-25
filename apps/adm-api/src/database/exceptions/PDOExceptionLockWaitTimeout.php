<?php

namespace MobileStock\database\exceptions;

use MobileStock\helper\RetryableException;
use PDOException;
use Throwable;

class PDOExceptionLockWaitTimeout extends PDOException implements RetryableException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct("O tempo de conexão expirou, tente novamente em alguns minutos.", $code, $previous);
    }

    public function retries(int $routeRetries)
    {
        return $routeRetries;
    }
}