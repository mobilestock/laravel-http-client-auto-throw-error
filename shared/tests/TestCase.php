<?php

use MobileStock\Shared\PdoInterceptor\PdoInterceptorStatement;
use MobileStock\Shared\PdoInterceptor\PdoInterceptorStatement74;
use MobileStock\Shared\PdoInterceptor\StatementUtils;

class TestCase extends PHPUnit\Framework\TestCase
{
    /**
     * @return PdoInterceptorStatement74|PdoInterceptorStatement
     */
    protected static function getStmt(...$args): PDOStatement
    {
        $reflectionClass = new ReflectionClass(StatementUtils::getStatementClass());
        $method = $reflectionClass->getConstructor();
        $method->setAccessible(true);
        $method->invoke($stmt = $reflectionClass->newInstanceWithoutConstructor(), ...$args);
        return $stmt;
    }
}
