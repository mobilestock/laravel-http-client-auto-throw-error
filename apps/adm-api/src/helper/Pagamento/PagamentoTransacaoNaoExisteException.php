<?php

namespace MobileStock\helper\Pagamento;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PagamentoTransacaoNaoExisteException extends HttpException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct(310, "Ocorreu um erro durante o pagamento, tente novamente.", $previous);
    }
}