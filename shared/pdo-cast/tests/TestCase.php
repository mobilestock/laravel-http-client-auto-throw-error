<?php

class TestCase extends PHPUnit\Framework\TestCase
{
    protected static function getStmt(...$args): PDOStatement
    {
        $reflectionClass = new ReflectionClass(MobileStock\PdoCast\StatementUtils::getStatementClass());
        $method = $reflectionClass->getConstructor();
        $method->setAccessible(true);
        $method->invoke($stmt = $reflectionClass->newInstanceWithoutConstructor(), ...$args);
        return $stmt;
    }
}
