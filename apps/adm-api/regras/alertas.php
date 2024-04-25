<?php
// mantêm a sessão criada por 4 anos se o cliente não fechar o navegador
require_once __DIR__ . '/../vendor/autoload.php';

@session_cache_limiter('none');
@session_start();

error_reporting(E_ERROR | E_WARNING | E_PARSE);

date_default_timezone_set('America/Sao_Paulo');

$enderecoSite = $_ENV['URL_AREA_CLIENTE'];
$enderecoSiteInterno = $_ENV['URL_MOBILE'];
//if ($_SERVER['SERVER_NAME'] == 'www.mobilestock.com.br') {
$versao = '?ver=' . rand();
//} else {
//  $versao = "?ver=" . rand();
//}

if (mb_strpos($_SERVER['HTTP_HOST'], 'mobilestock.com.br') === false) {
    define('PREFIXO_LOCAL', 'dev_');
} else {
    define('PREFIXO_LOCAL', 'pro_');
}

//cria sessão do numero do cliente
function registraClienteSessao($id_cliente)
{
    $_SESSION['id_cliente'] = $id_cliente;
}
function clienteSessao()
{
    return $_SESSION['id_cliente'];
}

// --Commented out by Inspection START (15/08/2022 15:24):
//function existeClienteSessao()
//{
//  return isset($_SESSION["id_cliente"]);
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)

// function limpaClienteSessao()
// {
//   unset($_SESSION['id_cliente'], $_SESSION['id_cliente_cript'], $_SESSION['Telefone_cliente'], $_SESSION['politica_empresa']);
// }

function usuarioEstaLogado()
{
    //    if (isset($_SESSION["usuario_logado"]) && isset($_SESSION["id_usuario"]) && $_SESSION["id_usuario"] && !isset($_SESSION['numero_de_compras'])) {
    //        $conexao = Conexao::criarConexao();
    //        $resp = $conexao->query(
    //            "SELECT COALESCE( COUNT(faturamento.id) ,0) faturamentos FROM faturamento WHERE faturamento.id_cliente = (SELECT usuarios.id_colaborador FROM usuarios WHERE usuarios.id = '{$_SESSION["id_usuario"]}') AND faturamento.situacao = 2"
    //        )->fetch(PDO::FETCH_ASSOC);
    //        $_SESSION['numero_de_compras'] = $resp['faturamentos'];
    //    }
    return isset($_SESSION['usuario_logado']);
}
function usuarioLogado()
{
    return $_SESSION['usuario_logado'];
}

// --Commented out by Inspection START (15/08/2022 15:24):
//function telefoneUsuario()
//{
//  if (!$_SESSION['Telefone_cliente']) {
//    $_SESSION['Telefone_cliente'] = buscaIdTelefoneCliente($_SESSION['id_cliente']);
//  }
//  return preg_replace("/[^0-9]/", "", $_SESSION['Telefone_cliente']);
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)

// function politica_empresa()
// {
//   return buscaSituacaoPoliticaEmpresa($_SESSION['id_cliente']);
// }

function idUsuarioLogado()
{
    return $_SESSION['id_usuario'];
}

// --Commented out by Inspection START (15/08/2022 15:24):
//function idColaboradorLogado()
//{
//  return $_SESSION["id_colaborador"];
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)

function nivelAcessoUsuario()
{
    return $_SESSION['nivel_acesso'];
}

// --Commented out by Inspection START (15/08/2022 15:24):
//function logaUsuario($id, $nome, $nivel, $logado=false)
//{
//  $conexao = Conexao::criarConexao();
//  $resp = $conexao->query("SELECT COALESCE( COUNT(faturamento.id) ,0) faturamentos FROM faturamento WHERE faturamento.id_cliente = (SELECT usuarios.id_colaborador FROM usuarios WHERE usuarios.id = '{$id}') AND faturamento.situacao = 2")->fetch(PDO::FETCH_ASSOC);
//  $idCliente = $conexao->query("SELECT usuarios.id_colaborador FROM usuarios WHERE usuarios.id = $id")->fetch(PDO::FETCH_ASSOC)['id_colaborador'];
//  $pendencia_documento = $conexao->query("SELECT usuarios.dados_zoop FROM usuarios WHERE usuarios.id = $id")->fetch(PDO::FETCH_ASSOC)['dados_zoop'];
//  $_SESSION["id_usuario"] = $id;
//  $_SESSION["usuario_logado"] = $nome;
//  $_SESSION["nivel_acesso"] = $nivel;
//  $_SESSION['numero_de_compras'] = $resp['faturamentos'];
//  $_SESSION['pendencia_documento'] = $pendencia_documento;
//
//  $_SESSION['id_cliente'] = $idCliente;
//  if (!$logado) {
//      Globals::geraToken();
//  }
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)

// --Commented out by Inspection START (15/08/2022 15:24):
//function pendenciaUsuario(){
//  /*
//    * Existe alguma documentação pendente pra esse usuário mediante a seu acesso?
//    * Caso exista redireciona-o para a tela de preenchimento desta documentação
//   */
//  if(nivelAcessoUsuario()>=30 && nivelAcessoUsuario() <= 39 && $_SESSION['pendencia_documento'] == 1){
//    $_SESSION['danger'] = "Falta pouco para liberarmos seu acesso! Preencha os dados solicitados para que possamos confirmar seu acesso.";
//    header('Location: fornecedores-produtos.php');
//  }
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)

function logout()
{
    session_destroy();
}

if (isset($_SESSION['danger'])) {
    mostraAlerta('danger');
}

if (isset($_SESSION['success'])) {
    mostraAlerta('success');
}

if (isset($_SESSION['dialog'])) {
    mostraAlerta('default');
}

function mostraAlerta($tipo)
{
    //função depreciada
    //Migrado para o cabeçalho
    return;
}

function verificaSeEstaEmUso($usuario, $id_cliente)
{
    $em_uso = buscaClienteBloqueado($id_cliente);
    if ($em_uso != $usuario && $em_uso != 0) {
        $_SESSION['danger'] = 'Este cliente está bloqueado com o usuário ' . buscaNomeUsuarioClienteBloqueado($em_uso);
        header('Location:pedido-painel.php');
        die();
    }
}

function acessoUsuarioFornecedor()
{
    if (usuarioEstaLogado()) {
        if (
            nivelAcessoUsuario() >= 1 &&
            nivelAcessoUsuario() <= 29 &&
            nivelAcessoUsuario() != 57 &&
            nivelAcessoUsuario() != 52
        ) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() >= 40 && nivelAcessoUsuario() != 57 && nivelAcessoUsuario() != 52) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

function acessoUsuarioVendedor()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() >= 1 && nivelAcessoUsuario() <= 29) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() >= 30 && nivelAcessoUsuario() <= 50 && nivelAcessoUsuario() != 32) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        } elseif (nivelAcessoUsuario() >= 60) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

function acessoUsuarioGerente()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() >= 1 && nivelAcessoUsuario() <= 29) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() >= 30 && nivelAcessoUsuario() <= 54) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        } elseif (nivelAcessoUsuario() >= 60) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

function acessoUsuarioFinanceiro()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() >= 1 && nivelAcessoUsuario() <= 29) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() >= 30 && nivelAcessoUsuario() <= 55) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        } elseif (nivelAcessoUsuario() >= 60) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

function acessoUsuarioAdministrador()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() >= 1 && nivelAcessoUsuario() <= 29) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() >= 30 && nivelAcessoUsuario() <= 56) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        } elseif (nivelAcessoUsuario() >= 60) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

function acessoUsuarioConferenteInternoOuAdm()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() >= 1 && nivelAcessoUsuario() <= 29) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        } elseif (nivelAcessoUsuario() != 57 && nivelAcessoUsuario() != 32 && nivelAcessoUsuario() != 55) {
            $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
            header('Location: menu-sistema.php');
            die();
        }
    } else {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

// function acessoUsuarioCliente()
// {
//   $local =  "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//   if (usuarioEstaLogado()) {
//     if (nivelAcessoUsuario() != 10) {
//       $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
//       header('Location: menu-sistema.php');
//       die();
//     }
//   } else {
//     $_SESSION['danger'] = 'É necessário entrar com seu usuário no sistema antes.';
//     header('Location:cliente-login.php?local=' . $local);
//     die();
//   }
// }

function acessoUsuarioGeral()
{
    if (usuarioEstaLogado()) {
        if (nivelAcessoUsuario() === 10) {
            header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
            die();
        }
    }
}

function acessoUsuarioFornecedorEInterno()
{
    if (!usuarioEstaLogado()) {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }

    if (nivelAcessoUsuario() < 29) {
        $_SESSION['danger'] = 'Você não tem permissão para acessar essa página';
        header('Location: ' . $_ENV['URL_MOBILE']); // SEMPRE COLOCAR O LINK CENTRALIZADO
        die();
    }
}

// --Commented out by Inspection START (15/08/2022 15:24):
//function buscaColaboradorUsuario(PDO $conexao, int $usuario)
//{
//  unset($_SESSION['id_colaborador']);
//  $query = "SELECT c.razao_social colaborador,
//    c.id id_colaborador,
//    c.endereco,
//    c.numero,
//    c.bairro,
//    c.cep,
//    c.cidade,
//    c.uf,
//    c.email,
//    c.telefone,
//    c.regime,
//    c.cpf,
//    c.cnpj
//    FROM usuarios u
//    INNER JOIN colaboradores c ON (c.id=u.id_colaborador)
//    LEFT OUTER JOIN pedido p ON (p.id_cliente=c.id)
//    WHERE u.id={$usuario}";
//  $resultado = $conexao->query($query);
//  $linha = $resultado->fetch(PDO::FETCH_ASSOC);
//  $_SESSION['id_colaborador'] = $linha['id_colaborador'];
//  return $linha;
//}
// --Commented out by Inspection STOP (15/08/2022 15:24)
