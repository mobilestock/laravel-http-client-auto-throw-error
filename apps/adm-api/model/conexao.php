<?php

namespace MobileStock\model;

require_once  __DIR__."../.env.php";

use PDO;
use PDOException;

class Conexao
{

  public static function criarConexao()
  {
    $conexao = null;
    $options = array(
      PDO::ATTR_PERSISTENT => true,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );
    try {
      $conexao = new PDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD'], $_ENV['OPTIONS']);
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
