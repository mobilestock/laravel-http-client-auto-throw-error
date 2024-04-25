<?php

namespace MobileStock\database;

use Illuminate\Contracts\Events\Dispatcher;

class PDO extends \PDO
{
    use PDOCallTrait;

    public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
    {
        return $this->call([$this, 'parent::query'], func_get_args());
    }

    public function exec($statement)
    {
        return $this->call([$this, 'parent::exec'], func_get_args());
    }

    public function commit()
    {
        $commit = parent::commit();
        $this->afterCommit();
        return $commit;
    }

    public function afterCommit(): void
    {
        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch('pdo.after_commit', (object) ['conexao' => $this]);
        $dispatcher->forget('pdo.after_commit');

        app()->forgetInstance(\PDO::class);
    }

    public function beginTransaction()
    {
        parent::beginTransaction();
        app()->instance(\PDO::class, $this);
    }

    public function rollBack()
    {
        parent::rollBack();
        app()->forgetInstance(\PDO::class);
    }
}
