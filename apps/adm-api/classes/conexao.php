<?php
require_once  __DIR__."/../.env.php";

class Conexao{

    /**
     * @deprecated https://github.com/mobilestock/web/issues/2540
     */
  public static function criarConexao(){
    $conexao = null;
    try {
      $conexao = new PDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD'], $_ENV['OPTIONS']);
      return $conexao;
    }catch(PDOException $error){
      http_response_code(500);
      exit('Fazendo backup... Voltamos em 5 minutos');
    }
  }

  public static function reiniciaConexao(){
    $conexao=null;
    $conexao=Conexao::criarConexao();
  }
}
