<?php

use MobileStock\Shared\PdoInterceptor\PdoCastStatement;
use MobileStock\Shared\PdoInterceptor\PdoCastStatement74;
use MobileStock\Shared\PdoInterceptor\StatementUtils;

class TestCase extends PHPUnit\Framework\TestCase
{
    /**
     * @return PdoCastStatement74|PdoCastStatement
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
