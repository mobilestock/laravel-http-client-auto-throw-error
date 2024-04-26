<?php

require_once  __DIR__."../.env.php";
class Conexao
{

  public static function criarConexao()
  {
    $conexao = null;
    try {
      $conexao = new PDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD']);
      return $conexao;
    } catch (PDOException $error) {
      echo $error->getMessage();
    }
  }
}
