<?php

namespace MobileStock\database;

use MobileStock\database\exceptions\PDOExceptionDeadlock;
use MobileStock\database\exceptions\PDOExceptionLockWaitTimeout;
use MobileStock\helper\EntregaClienteSaldoNegativoException;
use MobileStock\helper\EstoqueNegativoRetentavel;
use MobileStock\helper\Pagamento\PagamentoTransacaoNaoExisteException;
use MobileStock\helper\Pagamento\PDOExceptionPagamentoSaqueDuplicado;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

trait PDOCallTrait
{
    public function call(callable $process, array $params)
    {
        try {
            return call_user_func_array($process, $params);
        } catch (\PDOException $pdoException) {
            switch (true) {
                case $pdoException->errorInfo[0] === '40001' && $pdoException->errorInfo[1] === 1213:
                    throw new PDOExceptionDeadlock();
                case $pdoException->errorInfo[1] === 1205:
                    throw new PDOExceptionLockWaitTimeout();
                case $pdoException->errorInfo[0] === '45020':
                    throw new PDOExceptionPagamentoSaqueDuplicado($pdoException);
                case $pdoException->errorInfo[0] === '45030':
                    throw new PreconditionFailedHttpException($pdoException->getMessage());
                case $pdoException->errorInfo[0] === '45040':
                    throw new PagamentoTransacaoNaoExisteException($pdoException);
                case $pdoException->errorInfo[0] === '45050':
                    throw new EntregaClienteSaldoNegativoException($pdoException);
                case $pdoException->errorInfo[0] === '45060':
                    throw new EstoqueNegativoRetentavel($pdoException);
                case $pdoException->errorInfo[0] === '45422':
                    throw new UnprocessableEntityHttpException($pdoException);
                default:
                    throw $pdoException;
            }
        }
    }
}
