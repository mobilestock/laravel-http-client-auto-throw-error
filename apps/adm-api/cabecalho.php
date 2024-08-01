<?php
ob_start();
require_once 'regras/alertas.php';
require_once 'classes/categorias.php';
require_once 'classes/pedidos.php';
require_once 'classes/usuarios.php';
require_once 'classes/configuracoes.php';
require_once 'classes/notificacao.php';
require_once __DIR__ . '/vendor/autoload.php';

use MobileStock\database\Conexao;
use MobileStock\repository\UsuariosRepository;

$filtro = 0;
$conexao = Conexao::criarConexao();

$configuracoes = buscaConfiguracoes();

/**
 * @see issue: https://github.com/mobilestock/backend/issues/114
 */
$usuarioRepository = new UsuariosRepository();
if (!empty($_SESSION['id_usuario'])) {
    $usuario = $usuarioRepository->buscaUsuarioPorId($_SESSION['id_usuario']);
    $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
}
/* Função carrega os produtos que foram faturados do cliente, aparece no modal. */
if (isset($_SESSION['id_usuario'])) {
    $notificacoes_troca_pendente = buscaNotificacoesTroca($_SESSION['id_usuario']);
    if ($notificacoes_troca_pendente) {
        $produtos_trocados = exibeProdutosTroca($_SESSION['id_usuario']);
    }

    $notificacao_entrega_produto = buscaNotificacoesEntrega($_SESSION['id_usuario']);
    if ($notificacao_entrega_produto) {
        $tipo_entrega = $notificacao_entrega_produto['tipo_frete'];
    }

    $busca_corrigido_pago = false; //buscaNotificacoesPedidoPago($_SESSION["id_usuario"]);
    $notifica_pago = false; //$busca_corrigido_pago['id'];

    if ($notificacao_fiscal = buscaNotificacaoFiscal($_SESSION['id_cliente'] ?? 0)) {
        $link_fiscal = $notificacao_fiscal['mensagem'];
    } else {
        $link_fiscal = false;
    }

    if (clienteSessao()) {
        //DEBITOS E CRÉDITOS
        $total_creditos = false; //buscaTotalCreditoCliente(clienteSessao());
        $total_debitos = false; //buscaTotalLancamentosVencidosEmAbertoCliente(clienteSessao());
    }
} else {
    $lista_produtos = null;
    $total_atendimento_pendente = 0;
}
?>

<!DOCTYPE html>

<html lang="pt-br" style="height: auto;" onclick="menu()">
<!-- $('body').addClass('sidebar-collapse'); -->

<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="theme-color" content="#C5273D">
	<meta name="apple-mobile-web-app-status-bar-style" content="#C5273D">
	<meta name="msapplication-navbutton-color" content="#C5273D">

	<!-- JOSE -->
	<meta name='description' content='Mobile Stock'>
	<meta name='keywords' content='Mobile Stock Calçados Atacado Marketplace'>
	<meta name='title' content='Mobile Stock'>
	<meta name='apple-mobile-web-app-title' content='Mobile Stock'>
	<meta name='mobile-web-app-capable' content='yes'>
	<meta name="image" content="https://www.adm.mobilestock.com.br/images/logo.jpg">
	<meta name="image:type" content="image/jpeg">

	<meta property='og:site_name' content='https://www.adm.mobilestock.com.br'>
	<meta property='og:image' content='https://www.adm.mobilestock.com.br/images/logo.jpg'>
	<meta property='og:type' content='website'>
	<meta property='og:title' content='Mobile Stock'>
	<meta property='og:url' content='https://www.adm.mobilestock.com.br'>
	<meta property='og:description' content='Mobile stock, atacado de calçados'>
	<meta property="og:image:type" content="image/jpeg">
	<!-- FIm -->

	<title>Mobile Stock - O seu Estoque Digital</title>
	<meta name="description" content="Estoque digital para compras online de produtos no atacado">

	<link rel="shortcut icon" href="images/logo.ico" />
    <!-- @issue https://github.com/mobilestock/backend/issues/402 -->
	<!-- Theme style -->
	<?php if (
     basename($_SERVER['PHP_SELF']) == 'reposicoes-fulfillment.php' ||
     basename($_SERVER['PHP_SELF']) == 'cadastrar-reposicao.php' ||
     basename($_SERVER['PHP_SELF']) == 'dashboard-fornecedores.php' ||
     basename($_SERVER['PHP_SELF']) == 'fornecedores-produtos.php' ||
     basename($_SERVER['PHP_SELF']) == 'categorias.php' ||
     basename($_SERVER['PHP_SELF']) == 'promocoes.php' ||
     basename($_SERVER['PHP_SELF']) == 'configuracoes-sistema.php'
 ) {
     echo '<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">';
 } ?>
	<link rel="stylesheet" href="css/adminlte.min.css">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400&display=swap" rel="stylesheet"><!-- Google Font: Source Sans Pro -->
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
	<!-- Player Video -->
	<link rel="stylesheet" href="https://cdn.plyr.io/3.6.1/plyr.css" />

	<!-- Importações do cabecalho antigo -->
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<!-- <link rel="stylesheet" href="/resources/demos/style.css"> -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<link href="https://cdn.jsdelivr.net/npm/vuesax@4.0.1-alpha.25/dist/vuesax.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css">
	<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Fugaz+One" rel="stylesheet">
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Lobster&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Audiowide&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
	<link href="css/all.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="css/modal.css">
	<link rel="stylesheet" href="css/mobile.css<?= $versao ?>">
	<link rel="stylesheet" href="css/cliente-catalogo.css<?= $versao ?>">
	<link rel="stylesheet" href="css/principal.css<?= $versao ?>">
	<link rel="stylesheet" href="css/cabecalho.css<?= $versao ?>">
	<link rel="stylesheet" href="css/jquery-confirm.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Titillium+Web&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="css/layoutJS.css">
</head>

<body class="layout-fixed layout-navbar-fixed layout-footer-fixed sidebar-closed sidebar-collapse" style="max-width:100vw;">
	<input type="hidden" name="debitos" value=<?= $total_debitos ?>>
	<input type="hidden" name="creditos" value=<?= $total_creditos ?>>
	<div cabecalho class="wrapper d-none" style="max-width: 100vw;" id="cabecalhoVue">
		<input type="hidden" name="userID" :value="<?= idUsuarioLogado($filtro) ? idUsuarioLogado($filtro) : 0 ?>">
		<input type="hidden" name="userToken" value="<?= $_SESSION['token'] ?>">
		<input type="hidden" name="nomeUsuarioLogado" value="<?= usuarioLogado() ?>">

		<input type="hidden" name="userIDCliente" value="<?= $_SESSION['id_cliente'] ?>">
		<input type="hidden" name="nivelAcesso" :value="<?= $_SESSION['nivel_acesso'] ?? 0 ?>">

        <input type="hidden" name="url-gerador-qrcode" value="<?= $_ENV['URL_GERADOR_QRCODE'] ?>">

		<!-- Navbar -->
		<nav class="main-header navbar navbar-expand navbar-dark navbar-danger" style="max-width: 100vw;">
			<!-- Left navbar links -->
			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars fa-lg"></i> Menu </a>
				</li>
			</ul>

			<!-- Right navbar links -->
			<ul class="navbar-nav ml-auto" id="nav-notificacoes">
				<!-- Notifications Dropdown Menu -->

				<li @click="verNotificacoes" class="nav-item dropdown">
					<a v-if="user.nivelAcesso != 0" class="nav-link" data-toggle="dropdown" href="#">
						<i class="far fa-bell fa-lg"></i>
						<span class="badge badge-warning navbar-badge">{{quantidadeTotalNotificacoes}}</span>
					</a>

				</li>

				<li class="nav-item">
					<a class="nav-link" href="controle/usuario-logout.php" v-if="user.id != 0"><i class="fas fa-sign-in-alt"></i> Sair</a>
					<a class="nav-link" href="cliente-login.php" v-else><i class="fa fa-door-open"></i> Entrar</a>
				</li>
			</ul>
		</nav>
		<!-- /.navbar -->

		<!-- Main Sidebar Container -->
		<aside class="main-sidebar sidebar-light-danger elevation-4">
			<!-- Brand Logo -->
			<a href="index.php" class="brand-link navbar-danger">
				<img src="images/logo.svg" class="brand-image img-circle elevation-3" style="padding-top:0; background-color: #fff"></img>
				<span class="brand-text font-weight-light logo" style="color: #fff">Mobile Stock</span>
			</a>

			<!-- Menu Lateral Esquerdo -->
			<div class="sidebar">
				<!-- Itens Menu -->
				<nav class="mt-2">
					<div class="user-panel mt-3 pb-3 mb-3">
						<div class="image" style='display:flex; align-items:center; justify-content:space-between;'>
							<div class="d-block">
								<i class="fas fa-user-circle fa-lg"></i> <?= usuarioLogado() ?>
							</div>

							<?php if ($_SESSION['nivel_acesso'] >= 30 && $_SESSION['nivel_acesso'] < 40) { ?>

							<?php } ?>
						</div>

						<div id="lista-acesso-menu" class="row mt-3 ml-2 pb-3 mb-3" style='display:flex; align-items:center;'>
							<div class="form-group">
								<label style="font-size:small" for="permissao_cabecalho">Acesso</label><br>

								<select
									class="form-control-sm"
									name="permissao_cabecalho"
									v-show="listaPermissoes.length > 0"
									@change="(evento) => mudaAcessoPrincipal(evento.target.value)"
								>
									<option
										:key="index"
										:selected="permissao.nivel_value == user.nivelAcesso"
										:value="permissao.nivel_value"
										v-for="(permissao, index) in listaPermissoes"
									>{{ permissao.nome }}</option>
								</select>
							</div>
						</div>
					</div>
					<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
						<template v-for="(item, index) in listaItemsMenu">
							<li class="nav-header" v-if="item.header && exibeMenu(item.nivelNecessario)">{{item.header}}</li>

							<li class="nav-item" :id="index" :key="`item-${index}`" v-else v-show="exibeMenu(item.nivelNecessario)">
								<a :href="item.link" class="nav-link" :class="{active:index == menuAtivo}" @click="ativaMenu(index)">
									<i :class="item.icone"></i>
									<p>{{item.nome}}<span class="right badge badge-warning" v-if="item.notificacao && tipoNotificaoMenuLateral(item.notificacao) != 0">{{tipoNotificaoMenuLateral(item.notificacao)}}</span></p>
								</a>
							</li>

						</template>
					</ul>
				</nav> <!-- /.Items-menu -->
			</div>
			<!-- /.Menu Lateral Esquerdo -->
		</aside>
	</div>
	<?php if (isset($_SESSION['success']) || isset($_SESSION['danger'])) { ?>
		<div class="row p-0 m-0 mb-2">
			<div class="col-sm-12" style="text-align: center">
				<?php
    if (isset($_SESSION['success'])) { ?>
					<p class="alert alert-success"><?= $_SESSION['success'] ?></p>
			<?php } elseif (isset($_SESSION['danger'])) { ?>
				<p class="alert alert-danger"><?= $_SESSION['danger'] ?></p>
				<audio preload="auto" autoplay>
					<source src="audio/erro.wav" type="audio/wav">
				</audio>
				<?php }
    unset($_SESSION['success'], $_SESSION['danger']);
    ?>
		</div>
	</div>
	<?php } ?>

	<!-- REQUIRED SCRIPTS -->
	<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
	<!-- Font Awesome -->
	<script src="https://kit.fontawesome.com/9e8aef2f91.js" crossorigin="anonymous"></script>
	<!-- jQuery -->
	<script src="js/jquery-3.4.1.min.js"></script>
	<!-- DatePicker -->
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<!-- AJAX -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<!-- Bootstrap 4 -->
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<!-- AdminLTE App -->
	<script src="js/adminlte.min.js"></script>
	<!-- Biblioteca para impressão -->
	<script src="js/printThis.js"></script>
	<!-- Player de Video -->
	<script src="https://cdn.plyr.io/3.6.1/plyr.js"></script>
	<!-- VUE.js -->
	<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
	<!-- troque para arquivo de producao -->
	<script>
		window.localStorage.setItem('idUsuarioLogado', parseInt(JSON.parse('<?php echo json_encode(idUsuarioLogado()); ?>')))
	</script>
	<script src="js/api.js"></script>
	<script src="js/MobileStockApi.js"></script>
	<script src="js/cabecalho.js<?= $versao ?>"></script>
	<script src="js/layoutJS.js<?= $versao ?>"></script>
	<!-- <script src="js/gerencia-acesso.js<?= $versao ?>"></script> -->
	<script src="js/jquery-confirm.min.js"></script>
	<script src="js/clipboard.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw==" crossorigin="anonymous"></script>

	<script src="js/lazysizes.min.js" async=""></script>
	<script>
		$(function() {
			$('[data-toggle="popover"]').popover()
		})

		function menu(event) {
			var opened = $('.navbar-collapse').hasClass('collapse in');
			if (opened === true) {
				$('body').addClass('sidebar-collapse');
			}

		}
	</script>
	<script src="https://cdn.jsdelivr.net/npm/vuesax@4.0.1-alpha.25/dist/vuesax.min.js"></script>
	<input type="hidden" name="url-mobile" value="<?= $_ENV['URL_MOBILE'] ?>">
	<input type="hidden" name="url-meulook" value="<?= $_ENV['URL_MEULOOK'] ?>">
	<input type="hidden" name="url-area-cliente" value="<?= $_ENV['URL_AREA_CLIENTE'] ?>">

<?php unset($conexao); ?>
