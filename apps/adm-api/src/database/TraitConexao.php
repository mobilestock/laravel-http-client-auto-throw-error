<?php
//
//namespace MobileStock\database;
//
//require_once  __DIR__."/../../.env.php";
//
//use PDO;
//use PDOException;
//
///**
// * Gera uma conexao para a sua classe
// */
//trait TraitConexao
//{
//    public function criarConexao(): \PDO
//    {
//        $conexao = null;
//        try {
//            $conexao = new PDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']}", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD'], $_ENV['OPTIONS']);
//            return $conexao;
//        } catch (PDOException $error) {
//            echo $error->getMessage();
//        }
//    }
//}
