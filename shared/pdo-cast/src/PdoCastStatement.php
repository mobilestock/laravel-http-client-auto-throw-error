<?php

namespace MobileStock\PdoCast;

use PDOStatement;
use PDO;

class PdoCastStatement extends PDOStatement
{
    use StatementCoreTrait;

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->call('fetchAll', func_get_args());
    }

    public function execute(?array $params = null): bool
    {
        $this->call('execute', func_get_args());
    }

    public function nextRowset(): bool
    {
        $this->call('nextRowset', func_get_args());
    }
}
