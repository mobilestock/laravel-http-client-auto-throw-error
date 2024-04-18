<?php

namespace MobileStock\Shared\PdoInterceptor;

use PDOStatement;
use PDO;

class PdoInterceptorStatement extends PDOStatement
{
    use StatementCoreTrait;

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->call('fetchAll', func_get_args());
    }

    public function execute(?array $params = null): bool
    {
        return $this->call('execute', func_get_args());
    }

    public function nextRowset(): bool
    {
        return $this->call('nextRowset', func_get_args());
    }
}
