<?php

use MobileStock\PdoCast\PdoCastStatement;
use MobileStock\PdoCast\PdoCastStatement74;

class TestCase extends PHPUnit\Framework\TestCase
{
    /**
     * @return PdoCastStatement74|PdoCastStatement
     */
    protected static function getStmt(...$args): PDOStatement
    {
        $reflectionClass = new ReflectionClass(MobileStock\PdoCast\StatementUtils::getStatementClass());
        $method = $reflectionClass->getConstructor();
        $method->setAccessible(true);
        $method->invoke($stmt = $reflectionClass->newInstanceWithoutConstructor(), ...$args);
        return $stmt;
    }
}
