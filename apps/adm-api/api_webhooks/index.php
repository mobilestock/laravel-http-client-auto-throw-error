<?php

// https://github.com/mobilestock/backend/issues/159
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if (mb_strpos($_SERVER['HTTP_HOST'], 'mobilestock.com.br') === true) {
    error_reporting(E_USER_NOTICE);
}
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use api_webhooks\Controller\FilaRecebiveis;
use api_webhooks\Controller\TransacoesIugu;
use MobileStock\helper\RouterAdapter;

$routerAdapter = app(RouterAdapter::class);

$rotas = $routerAdapter->router;
$router = $routerAdapter->routerLaravel;

$rotas->namespace('\\api_webhooks\Controller')->group(null);
$rotas->get('/', 'Erro');

/*Documentos*/
//$rotas->post("/documento", "Documentos:documentos");

/*Transacao*/
//$rotas->post("/transacao",  "Transacoes:transacoes");

/*Recebiveis*/
//$rotas->post("/recebiveis", "Recebiveis:recebiveis");

/*Comprador*/
//$rotas->post("/comprador", "Pagina:pagina");

/*Planos*/
//$rotas->post("/planos", "Pagina:pagina");

/*Assinaturas*/
//$rotas->post("/assinatura", "Pagina:pagina");

/*Faturas*/
//$rotas->post("/faturas", "Pagina:pagina");

/*Antecipação sob demanda*/
//$rotas->post("/antecipacao", "Pagina:pagina");

/* Fila de requisições**/
$router->post('/queue', [FilaRecebiveis::class, 'salva']);

$router->post('/api_iugu', [TransacoesIugu::class, 'confirmacaoSaque']);

$routerAdapter->dispatch();
