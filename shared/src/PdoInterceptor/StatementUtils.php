<?php

namespace MobileStock\Shared\PdoInterceptor;

class StatementUtils
{
    /**
     * @issue https://github.com/mobilestock/web/issues/2776
     */
    public static function getStatementClass(): string
    {
        return version_compare(PHP_VERSION, '8', '>=')
            ? PdoInterceptorStatement::class
            : PdoInterceptorStatement74::class;
    }
}
