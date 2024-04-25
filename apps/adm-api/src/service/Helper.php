<?php
/*
namespace MobileStock\service;

use PDO;
use MobileStock\database\Conexao;


class Helper
{
  public static function clonaTabela(INT $idOrigem, INT $idDestino, STRING $tabela, STRING $coluna, $auto_increment = false, array $skip = [])
  {
    $conexao = Conexao::criarConexao();
    $sql = "CREATE TEMPORARY TABLE tmp SELECT * FROM {$tabela} WHERE {$coluna} = {$idOrigem};

            UPDATE tmp
            SET    {$coluna} = {$idDestino}
            WHERE  {$coluna} = {$idOrigem};";

    $auto_increment ? $sql .= "UPDATE tmp SET id = 0 WHERE {$coluna} = {$idDestino};" : '';

    if (is_array($skip) && sizeof($skip) > 0) {
      foreach ($skip as $col => $val) {
        $sql .= "UPDATE tmp SET {$col} = {$val} WHERE {$coluna} = {$idDestino};";
      }
    }


    $sql .= " INSERT INTO {$tabela}
              SELECT *
              FROM   tmp
              WHERE  {$coluna} = {$idDestino};";
    $stmt = $conexao->prepare($sql);
    $retorno = $stmt->execute();
    return $retorno;
  }
}
*/