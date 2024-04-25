<?php

require_once __DIR__ . '/vendor/autoload.php';
use Symfony\Component\HttpFoundation\JsonResponse;

if($_ENV['AMBIENTE'] === 'producao') {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$json = new JsonResponse([
    'data' => [
        'query' => $_GET
    ],
    'headers' => getallheaders(),
    'body' => json_decode(file_get_contents('php://input'))
]);
$json->send();