<?php

namespace MobileStock\helper;

use Symfony\Component\HttpKernel\Exception\HttpException;

class EntregaClienteSaldoNegativoException extends HttpException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct(
            400,
            'Para entregar esse produto é necessário entregar todas as trocas sinalizadas',
            $previous
        );
    }
}
