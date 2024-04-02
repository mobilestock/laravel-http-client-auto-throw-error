<?php

namespace MobileStock\PdoCast;

class StatementUtils
{
    public static function getStatementClass(): string
    {
        return version_compare(PHP_VERSION, '8', '>=')
            ? PdoCastStatement::class
            : PdoCastStatement74::class;
    }
}