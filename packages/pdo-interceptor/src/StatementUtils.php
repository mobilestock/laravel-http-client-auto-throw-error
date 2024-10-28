<?php

namespace MobileStock\PdoInterceptor;

class StatementUtils
{
    /**
     * @issue https://github.com/mobilestock/backend/issues/168
     */
    public static function getStatementClass(): string
    {
        return version_compare(PHP_VERSION, '8', '>=')
            ? PdoInterceptorStatement::class
            : PdoInterceptorStatement74::class;
    }
}
