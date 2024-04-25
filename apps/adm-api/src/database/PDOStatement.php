<?php

namespace MobileStock\database;

class PDOStatement extends \PDOStatement
{
    use PDOCallTrait;

    public function execute($params = null)
    {
        return $this->call([self::class, 'parent::execute'], func_get_args());
    }

    public function nextRowset()
    {
        return $this->call([self::class, 'parent::nextRowset'], func_get_args());
    }
}
