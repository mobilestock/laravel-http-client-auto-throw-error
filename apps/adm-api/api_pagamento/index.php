<?php

// https://github.com/mobilestock/web/issues/2662
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('content-type: text/html; charset=utf-8');

error_reporting(E_USER_NOTICE);
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Credentials: true');
    header(
        'Access-Control-Request-Method:  username,token, password, Origin, X-Requested-With, Content-Type, Accept, Authorization'
    );
    header(
        'Access-Control-Allow-Headers: username, token, auth, password, Origin, X-Requested-With, Content-Type, Accept, Authorization'
    );
    header(
        'Access-Control-Allow-Methods: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, DELETE, PUT, POST, GET'
    );
    die();
}

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use api_pagamento\Controller\LinksPagamento;
use api_pagamento\Controller\LinksPagamentoCliente;
use api_pagamento\Controller\Pagamento;
use api_pagamento\Controller\TokenCartao;
use Illuminate\Routing\Router;
use MobileStock\helper\Middlewares\HeaderManual;

$routerAdapter = app(MobileStock\helper\RouterAdapter::class);
//$rotas->namespace('\\api_pagamento\Controller')->group(null);
//$rotas->get("/", "Erro");

$router = $routerAdapter->routerLaravel;

$router->get('/cartoes', [TokenCartao::class, 'buscaCartoes']);

$router->prefix('/transacao')->group(function (Router $router) {
    $router->post('/simula_calculo', [Pagamento::class, 'simulaCalculo']);
    $router->get('/{id}', [Pagamento::class, 'infoTransacao']);
    $router->delete('/em_aberto', [Pagamento::class, 'deletaTransacoesEmAberto']);
    $router->post('/credito', [Pagamento::class, 'criaTransacaoCredito']);
    $router->post('/produto', [Pagamento::class, 'criaTransacaoProduto']);
    $router->post('/produto/pago', [Pagamento::class, 'criaTransacaoPagamentoSaldo']);

    $router->put('/calcula/{id}', [Pagamento::class, 'calculaTransacao']);
    $router->post('/pagar/{id}', [Pagamento::class, 'PagamentoTransacao']);
    //    $rotas->get("/fila/{id_fila}", [Pagamento::class, 'infoFila']);
});

$router->prefix('/link_pagamento')->group(function (Router $router) {
    $router->post('/transacao', [LinksPagamento::class, 'linkPagamento']);
    $router->get('/fila/{id_fila}', [LinksPagamento::class, 'infoFila']);
    $router->get('/lista', [LinksPagamentoCliente::class, 'listaLinks']);
});

$router
    ->middleware(HeaderManual::class . ':Authorization,SECRET_MOBILE_STOCK_API_TOKEN')
    ->post('/saldo_lookpay', [LinksPagamento::class, 'atualizaSaldoLookpay']);

$routerAdapter->dispatch();
