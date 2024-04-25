<?php

namespace MobileStock\helper;

use MobileStock\helper\ClienteException;

class ValidacaoException extends ClienteException
{
    private $indiceInvalido;

    public function setIndiceInvalido(string $indiceInvalido): void
    {
        $this->indiceInvalido = $indiceInvalido;
    }

    public function getIndiceInvalido()
    {
        return $this->indiceInvalido;
    }
}