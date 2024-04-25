<?php

namespace MobileStock\helper;

use Exception;

class EstoqueNegativoRetentavel extends Exception implements RetryableException
{
    public function __construct($previous = null)
    {
        parent::__construct('Estoque está negativo', 0, $previous);
    }

    public function retries(int $qtdTentativasMax): int
    {
        return $qtdTentativasMax;
    }
}
