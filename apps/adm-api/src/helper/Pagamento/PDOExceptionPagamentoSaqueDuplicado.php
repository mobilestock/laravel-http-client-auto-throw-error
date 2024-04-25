<?php

namespace MobileStock\helper\Pagamento;

use MobileStock\helper\RetryableException;

class PDOExceptionPagamentoSaqueDuplicado extends \PDOException implements RetryableException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct("Ocorreu um erro no seu pagamento. Tente novamente.", 301, $previous);
    }

    public function retries(int $routeRetries)
    {
        return $routeRetries;
    }
}