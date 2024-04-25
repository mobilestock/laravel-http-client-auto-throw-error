<?php

namespace MobileStock\helper\Middlewares;

use Closure;

class SetLogLevel
{
    public function handle($input, Closure $next, string $level)
    {
        app()['config']['logging.level'] = $level;

        return $next($input);
    }
}
