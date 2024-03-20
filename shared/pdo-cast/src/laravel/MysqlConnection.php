<?php

namespace MobileStock\PdoCast\laravel;

use Closure;
use Exception;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PDO;
use ReflectionClass;
use RuntimeException;

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

            if ($reflectionClass->isInternal() && version_compare(PHP_VERSION, '8', '>=')) {
                throw new QueryException('mysql', $query, $this->prepareBindings($bindings), $e);
            } elseif($reflectionClass->isInternal()) {
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
    public function getLock(...$identificadores): void
    {
        if (DB::getPdo()->inTransaction()) {
            throw new RuntimeException('Não é possível executar GET_LOCK dentro de uma transação');
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4);
        $camadaMaisProfunda = $backtrace[3];
        $camadaMenosProfunda = $backtrace[2];
        $camada = [
            'file' => $camadaMenosProfunda['file'],
            'class' => $camadaMaisProfunda['class'] ?? '',
            'type' => $camadaMaisProfunda['type'] ?? '',
            'function' => $camadaMaisProfunda['function'],
            'args' => json_encode($camadaMenosProfunda['args']),
            'identifier' => json_encode($identificadores),
        ];
        $rota = "{$camada['file']}::{$camada['class']}{$camada['type']}{$camada['function']}({$camada['args']})->getLock({$camada['identifier']})";
        $hashBacktrace = sha1($rota);

        $this->selectOneColumn('SELECT GET_LOCK(:lock_id, 99999);', ['lock_id' => $hashBacktrace]);
    }
}
