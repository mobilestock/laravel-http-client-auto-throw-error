<?php
require_once  __DIR__."../../../.env.php";

define("DATA_LAYER_CONFIG", [
    "driver" => "mysql",
    "host" => $_ENV['MYSQL_HOST'],
    "port" => "3306",
    "dbname" => $_ENV['MYSQL_DB_NAME'],
    "username" => $_ENV['MYSQL_USER'],
    "passwd" => $_ENV['MYSQL_PASSOWORD'],
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => true
    ]
]);



define("URL_MOBILESTOCK","https://www.mobilestock.com.br/");
define("URL_API","https://www.mobilestock.com.br/api_estoque/");

define('VERSAO','?v=01');

?>
