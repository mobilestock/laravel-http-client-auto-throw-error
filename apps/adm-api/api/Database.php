<?php

require_once  __DIR__."../.env.php";
class Database
{

        public static $host = 'localhost';
        public static $dbName = 'mobile_stock';
        public static $username = 'mobilestock';
        public static $password = 'gnOkQGZ%1W%n';

        private $pdo;

        public function __construct()
        {
                $pdo = new PDO("mysql:host={$_ENV['MYSQL_HOST']};dbname={$_ENV['MYSQL_DB_NAME']};charset=utf8", $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSOWORD']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo = $pdo;
        }
        public function query($query, $params = array())
        {
                $statement = $this->pdo->prepare($query);
                $statement->execute($params);
                if (explode(' ', $query)[0] == 'SELECT') {
                        $data = $statement->fetchAll();
                        return $data;
                }
        }
}

