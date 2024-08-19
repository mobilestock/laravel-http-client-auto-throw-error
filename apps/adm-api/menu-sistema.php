<?php
require_once 'cabecalho.php';
require_once 'classes/colaboradores.php';

//nivel
//1-cliente
//2-fornecedor
//3-transportadora
//4-vendedor
//5-estoquista
//6-livre
//7-financeiro
//8-gerente
//9-admin
//10-estoquista(ta dando erro de verificaçao em alguns lugares)
$menus = [
    [
        'tipo' => 'Comercial',
        'menu' => 'pedido-painel.php',
        'nivel' => [52, 53, 54, 55, 56, 57],
        'icone' => "<i class='fas fa-shopping-cart'></i>",
        'titulo' => 'Pedidos',
        'cor' => 'blue',
    ],
    [
        'tipo' => 'Produção',
        'menu' => 'produtos-lista.php',
        'nivel' => [52, 53, 54, 55, 56, 57],
        'icone' => "<i class='fas fa-tshirt'></i>",
        'titulo' => 'Produtos',
        'cor' => 'yel',
    ],
    [
        'tipo' => 'Produção',
        'menu' => 'reposicoes-fulfillment.php',
        'nivel' => [53, 54, 55, 56, 57],
        'icone' => "<i class='fas fa-shopping-basket'></i>",
        'titulo' => 'Reposicões',
        'cor' => 'blue',
    ],
    [
        'tipo' => 'Financeiro',
        'menu' => 'marketplace.php',
        'nivel' => [55, 56, 57],
        'icone' => "<i class='fas fa-shopping-bag'></i>",
        'titulo' => 'MarketPlace',
        'cor' => 'red',
    ],
    [
        'tipo' => 'Gerencial',
        'menu' => 'usuarios-lista.php',
        'nivel' => [56, 57],
        'icone' => "<i class='fas fa-user'></i>",
        'titulo' => 'Usuários',
        'cor' => 'blue',
    ],
    [
        'tipo' => 'Gerencial',
        'menu' => 'configuracoes-sistema.php',
        'nivel' => [56, 57],
        'icone' => "<i class='fas fa-tools'></i>",
        'titulo' => 'Configurações',
        'cor' => 'yel',
    ],
    [
        'tipo' => 'Fiscal',
        'menu' => 'fiscal-gerenciar.php',
        'nivel' => [55, 56, 57],
        'icone' => "<i class='fas fa-balance-scale'></i>",
        'titulo' => 'Fiscal',
        'cor' => 'red',
    ],
];

$tipos = [];
foreach ($menus as $key => $me) {
    $tipos[$me['tipo']] = $me;
}
?>
<style>
  body {
    font-family: 'Titillium Web', sans-serif;
  }
  .card-menu{
    border: 1px solid #f1f1f1;
    background-color: #F5F5F5;
    border-radius: 5px;
  }
</style>

<?php
acessoUsuarioGeral();

function mostraMenu($nvl_acesso, $menus, $tipos)
{
    ?>
  <div>
    <h2><b>Menu</b></h2>
  </div>

  <?php foreach ($tipos as $key => $t) { ?>
      <div class="categoria-menu card-menu">
        <h4><?= $t['tipo'] ?></h4>
        <div class="row">
          <?php foreach ($menus as $key => $m) {
              if (in_array($nvl_acesso, $m['nivel']) && $m['tipo'] == $t['tipo']) { ?>
              <div class="col-6 col-sm-3 p-2">
                <a href="<?= $m['menu'] ?>" class="link-menu-principal">
                  <div class="card-menu-principal">
                    <div class="row">
                      <div class="col-sm-4 d-flex justify-content-center my-auto">
                        <div class="fab-menu-principal d-flex align-items-center justify-content-center fab-mp-<?= $m[
                            'cor'
                        ] ?>"><?= $m['icone'] ?></div>
                      </div>
                      <div class="col-sm-6 d-flex justify-content-center text-center my-auto"><?= $m['titulo'] ?></div>
                    </div>
                  </div>
                </a>
              </div>
            <?php }
          } ?>
        </div>
      </div>
      <?php }
}

if (usuarioEstaLogado()) { ?>
  <div class="container-fluid"><br>
    <?php if ($_SESSION['nivel_acesso'] >= 10 && $_SESSION['nivel_acesso'] <= 19) {
        mostraTelaCliente();
    } elseif ($_SESSION['nivel_acesso'] >= 30 && $_SESSION['nivel_acesso'] <= 39 && $_SESSION['nivel_acesso'] != 32) {
        mostrarMenuComprasFornecedor();
    } else {
        mostraMenu($_SESSION['nivel_acesso'], $menus, $tipos);
    } ?>
  </div>
<?php } else {header('Location:login.php');}

function mostrarMenuComprasFornecedor()
{
    header('Location:dashboard-fornecedores.php');
    die();
}

function mostraTelaCliente()
{
    header('Location:index.php');
    die();
}
?>


<?php
require_once 'rodape.php';
ob_end_flush();


?>
