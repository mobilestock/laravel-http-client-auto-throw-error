<?php
use MobileStock\helper\LoggerFactory;
require_once 'vendor/autoload.php';

session_set_cookie_params(4 * 60 * 60 * 24 * 365);
session_start();

if (isset($_SESSION) && isset($_SESSION['tempo'])) {
    echo ' tenho sessao. vai expirar em: ' . $_SESSION['tempo']->diff(new DateTime())->s;
    $logger = LoggerFactory::arquivo('logs_sessao.log');
    $logger->info(session_id() . ' acessou.');
} else {
    $logger = LoggerFactory::arquivo('logs_sessao.log');
    $logger->info('sessão encerrada' . ' id: ' . session_id());
    $_SESSION['tempo'] = (new DateTime())->add(DateInterval::createFromDateString('1 year'));
    echo 'crieri sessao';
}