<?php

namespace MobileStock\helper;

use MobileStock\database\Conexao;
use PDO;

/**
 * @deprecated
 * Utilizar o class Conexao;
 */
abstract class DB
{
    /**
     * @param string $sql
     * @param array $values
     * @param $fetchMode
     * @param PDO|null $pdo
     */
    public static function exec(string $sql, array $values = [], PDO $pdo = null)
    {
        if (is_null($pdo)) {
            $pdo = Conexao::criarConexao();
        }
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public static function select(string $sql, array $value = [], PDO $pdo = null, string $fetchMode = 'fetchAll', $fetchStyle = PDO::FETCH_ASSOC)
    {
        if (is_null($pdo)) {
            $pdo = Conexao::criarConexao();
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($value);
        return $stmt->$fetchMode($fetchStyle);
    }

    public static function transaction(\Closure $closure, PDO $pdo = null)
    {
        if (is_null($pdo)) {
            $pdo = Conexao::criarConexao();
        }
        $pdo->beginTransaction();
        try {
            $response = $closure($pdo);
            $pdo->commit();

            if ($response)
                return $response;
        } catch (\Throwable $err) {
            $pdo->rollBack();
            throw $err;
        }
    }
}
