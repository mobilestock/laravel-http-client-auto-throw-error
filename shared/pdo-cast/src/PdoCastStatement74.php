<?php

namespace MobileStock\PdoCast;

use PDOStatement;

class PdoCastStatement74 extends PDOStatement
{
    use StatementCoreTrait;

    public function fetchAll($how = null, $class_name = null, $ctor_args = null)
    {
        return $this->call('fetchAll', func_get_args());
    }

    public function execute($params = null)
    {
        return $this->call('execute', func_get_args());
    }

    public function nextRowset()
    {
        return $this->call('nextRowset', func_get_args());
    }
}
