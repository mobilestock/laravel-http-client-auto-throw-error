<?php

use MobileStock\repository\UsuariosRepository;

require_once 'cabecalho.php';
require_once './classes/usuarios-dao.php';
require_once './classes/usuarios.php';
require_once 'vendor/autoload.php';

$mobilepay_redirect = $_GET['mobilepay'];
if ($mobilepay_redirect) {
    echo '<strong>&nbsp;Redirecionando para o LookPay...</strong>';
    echo "<script>window.location.replace('" .
        $_ENV['URL_LOOKPAY'] .
        "home?xls=' + cabecalhoVue.user.token + '&amp;status=true')</script>";
    die();
}

if ($_GET['separacao']) {
    header('Location: ' . $_ENV['URL_AREA_CLIENTE'] . 'separacao');
    die();
}

$pages = [
    'troca' => 'cliente-painel-saldo.php',
];

$usuario = new UsuariosRepository();
!($token = $_GET['token'] ?? false);

if (!($dados = $usuario->buscaIDUsuariobyToken($token))) {
    header('Location: ' . $_ENV['URL_AREA_CLIENTE'] . 'login');
    exit();
}

/**
 * @issue: https://github.com/mobilestock/web/issues/3147
 */
session_start();
$_SESSION['id_usuario'] = $dados['id'];
$_SESSION['usuario_logado'] = $dados['nome'];
$_SESSION['nivel_acesso'] = $dados['nivel_acesso'];
$_SESSION['numero_de_compras'] = 0;
$_SESSION['cliente'] = $dados['id_colaborador'];
$_SESSION['token'] = $token;

registraClienteSessao($dados['id_colaborador']);
usuarioOnline($dados['id']);

if ($dados['nivel_acesso'] >= 50) {
    header('Location: menu-sistema.php');
    exit();
}

if ($dados['nivel_acesso'] == 32) {
    header('Location: menu-sistema.php');
    exit();
}

if ($dados['nivel_acesso'] == 30) {
    header('Location: dashboard-fornecedores.php');
    exit();
}

if ($dados['nivel_acesso'] >= 10 && $dados['nivel_acesso'] <= 19) {
    header('Location: index.php');
    exit();
}

header('Location: index.php');
