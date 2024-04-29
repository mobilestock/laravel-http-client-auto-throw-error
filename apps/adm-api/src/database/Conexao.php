<?php

namespace MobileStock\database;

require_once  __DIR__ . "/../../.env.php";

use MobileStock\database\PDO as DatabasePDO;
use PDO;
use PDOException;

class Conexao
{
    public static function criarConexao(): PDO
    {
        $conexao = null;
        $conexao = new DatabasePDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD'], $_ENV['OPTIONS']);
        return $conexao;
    }

  public static function criarConexaoSuper(): PDO
  {
    $conexao = null;
    try {
      $conexao = new DatabasePDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER_SUPER'], $_ENV['MYSQL_PASSOWORD_SUPER'], $_ENV['OPTIONS']);
      return $conexao;
    } catch (PDOException $error) {
      echo $error->getMessage();
    }
  }
  public static function reiniciaConexao()
  {
    $conexao = null;
    $conexao = Conexao::criarConexao();
  }
}
