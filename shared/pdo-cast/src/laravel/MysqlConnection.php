<?php

namespace MobileStock\PdoCast\laravel;

use Closure;
use Exception;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use PDO;
use ReflectionClass;

class MysqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * @atencao Grande parte do código foi copiado do Laravel
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        } catch (Exception $e) {
            // If an exception occurs when attempting to run a query, we'll format the error
            // message to include the bindings with SQL, which will make this exception a
            // lot more helpful to the developer instead of just the database's errors.
            $reflectionClass = new ReflectionClass($e);

            if ($reflectionClass->isInternal()) {
                throw new QueryException($query, $this->prepareBindings($bindings), $e);
            }

            throw $e;
        }
    }

    public function selectOneColumn($query, $bindings = [], $useReadPdo = true)
    {
        Event::listenOnce(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_COLUMN, 0);
        });

        return self::selectOne($query, $bindings, $useReadPdo);
    }

    public function selectColumns($query, $bindings = [], $useReadPdo = true): array
    {
        Event::listenOnce(function (StatementPrepared $event) {
            $event->statement->setFetchMode(PDO::FETCH_COLUMN, 0);
        });

        return self::select($query, $bindings, $useReadPdo);
    }
}
