<?php

namespace MobileStock\database;

class PdoCallMiddleware
{
    use PDOCallTrait;

    public function handle($request, $next)
    {
        return $this->call($next, [$request]);
    }
}
