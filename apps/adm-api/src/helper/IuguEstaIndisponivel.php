<?php

namespace MobileStock\helper;

class IuguEstaIndisponivel extends \Exception
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct('O pagamento por este meio está temporariamente indisponivel, tente novamente em um minuto.', 503, $previous);
    }
//
//    public function retries(int $routeRetries)
//    {
//        return 3;
//    }
}