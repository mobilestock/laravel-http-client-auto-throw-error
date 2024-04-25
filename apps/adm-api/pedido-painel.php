<?php
require_once 'cabecalho.php';

require_once 'classes/colaboradores.php';
require_once 'classes/historico.php';
require_once 'classes/painel.php';

acessoUsuarioVendedor();
$fornecedores = listaFornecedores();
$usuario = idUsuarioLogado();

apagaHistoricoUsuarioAntigo();

desbloqueiaClientes($usuario);

$pagina = isset($_GET['p']) ? $_GET['p'] : 1;

//lista de clientes para cadastrar pedido
$filtro = " WHERE 1=1";
//filtra codigo de cliente
if (isset($_POST['cod_cliente'])) {
  if ($_POST['cod_cliente'] != "") {
    $filtro .= " AND c.id =" . $_POST['cod_cliente'];
    $_SESSION['cod_cliente'] = $_POST['cod_cliente'];
  } else {
    unset($_SESSION['cod_cliente']);
  }
} else if (isset($_SESSION['cod_cliente'])) {
  $filtro .= " AND c.id =" . $_SESSION['cod_cliente'];
}

//filtra nome de cliente
if (isset($_POST['razao_social'])) {
  if ($_POST['razao_social'] != "") {
    $filtro .= " AND LOWER(razao_social) LIKE LOWER('%" . $_POST['razao_social'] . "%')";
    $_SESSION['razao_social'] = $_POST['razao_social'];
  } else {
    unset($_SESSION['razao_social']);
  }
} else if (isset($_SESSION['razao_social'])) {
  $filtro .= " AND LOWER(razao_social) LIKE LOWER('%" . $_SESSION['razao_social'] . "%')";
}

//filtra referencia
if (isset($_POST['referencia'])) {
  if ($_POST['referencia'] != "") {
    $filtro .= " AND LOWER(descricao) LIKE LOWER('%" . $_POST['referencia'] . "%')";
    $_SESSION['referencia'] = $_POST['referencia'];
  } else {
    unset($_SESSION['referencia']);
  }
} else if (isset($_SESSION['referencia'])) {
  $filtro .= " AND LOWER(descricao) LIKE LOWER('%" . $_SESSION['referencia'] . "%')";
}

//filtra tamanho
if (isset($_POST['tamanho'])) {
  if ($_POST['tamanho'] != "") {
    $filtro .= " AND nome_tamanho =" . $_POST['tamanho'];
    $_SESSION['tamanho'] = $_POST['tamanho'];
  } else {
    unset($_SESSION['tamanho']);
  }
} else if (isset($_SESSION['tamanho'])) {
  $filtro .= " AND nome_tamanho =" . $_SESSION['tamanho'];
}

//filtra tamanho
if (isset($_POST['separados'])) {
  $filtro .= " AND pi.separado = 1 ";
  $_SESSION['separados'] = $_POST['separados'];
} else if (isset($_SESSION['separados'])) {
  $filtro .= " AND pi.separado = 1 ";
}

//filtra tamanho
if (isset($_POST['sinalizados'])) {
  $filtro .= " AND p.sinalizado = 1 ";
  $_SESSION['sinalizados'] = $_POST['sinalizados'];
} else if (isset($_SESSION['sinalizados'])) {
  $filtro .= " AND p.sinalizado = 1 ";
}

if (isset($_POST['limpar']) && $_POST['limpar'] != '') {
  unset($_SESSION['cod_cliente']);
  unset($_SESSION['cliente']);
  unset($_SESSION['referencia']);
  unset($_SESSION['tamanho']);
  unset($_SESSION['separados']);
  unset($_SESSION['sinalizados']);
  $filtro = "";
}

$itens = 25;

$clientes = listaPainelPagina($filtro, $pagina * $itens - $itens, $itens);

$totalClientes = sizeof(buscaTotalClientesComParesPainel());

$totalPedidos = buscaTotalDePedidos($filtro);
$nPag = ceil($totalPedidos / $itens);

?>

<div class="body-novo">

  <nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
      <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">
        <h5><b>Pedidos de clientes</b></h5>
      </a>
      <!-- <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">
        <h5><b>Gerar links de produtos</b></h5>
      </a> -->
    </div>
  </nav>
  <div class="m-4">
    <div class="tab-content" id="nav-tabContent">
      <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
        <form method="post">
          <h4>Buscar:</h4><br>
          <div class="row">
            <div class="col-sm-2"><label>Código:</label>
              <input class="form-control input-novo" type="number" name="cod_cliente" min="0" value="<?php if (isset($_SESSION['cod_cliente'])) {
                                                                                                        echo $_SESSION['cod_cliente'];
                                                                                                      } ?>" />
            </div>
            <div class="col-sm-5"><label>Cliente:</label><input class="form-control input-novo" type="text" name="razao_social" value="<?php if (isset($_SESSION['cliente'])) {
                                                                                                                                          // echo $_SESSION['cliente'];
                                                                                                                                        } ?>" />
            </div>
            <!-- <div class="col-sm-3"><label>Ref:</label><input class="form-control input-novo" type="text" name="referencia" value="<?php if (isset($_SESSION['referencia'])) {
                                                                                                                                        echo $_SESSION['referencia'];
                                                                                                                                      } ?>" />
            </div>
            <div class="col-sm-2"><label>Tamanho:</label><input class="form-control input-novo" type="number" name="tamanho" value="<?php if (isset($_SESSION['tamanho'])) {
                                                                                                                                      echo $_SESSION['tamanho'];
                                                                                                                                    } ?>" />
            </div> -->
          </div><br />
          <div class="row">
            <!-- <div class="col-sm-3">
              <a href="acompanhamento-pedidos.php?tela=pedido-painel" class="btn btn-block btn-danger">Acompanhamento pedidos</a>
            </div> -->
            <div class="col-sm-2">
              <button class="btn btn-success btn-block" type="submit">Filtrar</button>
            </div>
        </form>
        <div class="col-sm-2">
          <form action="pedido-painel.php?p=1" method="post">
            <input type="hidden" name="limpar" value="1">
            <button class="btn btn-block btn-danger"><span class="fa fa-times"></span> Limpar</button>
          </form>
        </div>
      </div>
      <br />
      <div class="row cabecalho">
        <div class="col-sm-3">Cliente</div>
        <div class="col-sm-1">Qtd.Produtos</div>
        <div class="col-sm-1">Em uso</div>
      </div>
      <?php foreach ($clientes as $indice => $cliente) :
        //verificar faturamento em aberto do cliente
        // $faturamentoEmAberto = verificaSeExisteFaturamentoAberto($cliente['id']);
        // $produtosExclusao = verificaSeExisteProdutosNaExclusao($cliente['id']);
        $paresAVencer1Dia = buscaParesAVencer1Dia($cliente['id']);
        $paresAVencer3Dia = buscaParesAVencer3Dia($cliente['id']);

        if (sizeof($paresAVencer1Dia) > 0) {
          $estilo = "fundo-vermelho";
        } else if (sizeof($paresAVencer3Dia) > 0) {
          $estilo = "fundo-amarelo";
        } else if ($indice % 2 == 0) {
          $estilo = "fundo-branco";
        } else {
          $estilo = "fundo-cinza";
        } ?>
        <div class="row corpo <?= $estilo; ?>">
          <div class="col-sm-3">
            <?php if ($cliente['sinalizado'] == 1) {
              echo "<span class='badge badge-pill badge-danger'>!</span> ";
            }
            echo $cliente['id'] . " - " . $cliente['razao_social']; ?>
          </div>
          <div class="col-sm-1"><?= $cliente['pares_separar']; ?></div>
          <!-- <div class="col-sm-1">
            <?php if (buscaDataUltimaCompra($cliente['id'])) {
              echo buscaDataUltimaCompra($cliente['id']);
            } ?>
          </div> -->
          <div class="col-sm-1"><?= $cliente['usuario']; ?></div>
          <div class="col-sm-3"><?= $cliente['usuario_contato'];
                                if ($cliente['data_contato'] != null) {
                                  $data = date_create($cliente['data_contato']);
                                  echo ' - ' . date_format($data, 'd/m/Y H:i:s');
                                }
                                ?></div>

          <div class="col-sm-2 form-group">
            <!-- <a href="pedido-cadastrar.php?cliente=<?= $cliente['id']; ?>" class="btn btn-primary">Painel</a> -->
            <a href="pedido-troca-pendente.php?cliente=<?= $cliente['id']; ?>" class="btn btn-danger">Trocas</a>
          </div>
        </div>
      <?php endforeach; ?><br />
      <div class="d-flex justify-content-center">
        <nav>
          <ul class="pagination">
            <li class="page-item">
              <a class="page-link" href="pedido-painel.php?p=1" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <?php for ($i = $pagina - 3; $i <= $pagina - 1; $i++) {
              if ($i >= 1) { ?>
                <li class="page-item"><a class="page-link" href="pedido-painel.php?p=<?= $i; ?>"><?= $i; ?></a></li>
              <?php }
            }
            echo '<li class="page-item"><a class="page-link">' . $pagina . '</a></li>';
            for ($i = $pagina + 1; $i <= $pagina + 3; $i++) {
              if ($i <= $nPag) { ?>
                <li class="page-item"><a class="page-link" href="pedido-painel.php?p=<?= $i; ?>"><?= $i; ?></a></li>
            <?php }
            } ?>
            <a class="page-link" href="pedido-painel.php?p=<?= $nPag ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
            </li>
          </ul>
        </nav>
      </div>

      <!-- <div>
        <h4><b>TOTAL</b></h4>
      </div>
      <div>
        <h4>Clientes com pares pendentes: <b><? //= $totalClientes; 
                                              ?></b></h4>
      </div>
      <div>
        <h4>Pares a Separar: <b><? //= buscaTotalParesASeparar(); 
                                ?></b></h4>
      </div> -->
    </div>
    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
      <!-- <div class="row col-xs-8">
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>index.php"><i class="far fa-copy"></i> <small>Filtros</small></button>
          <input type="text" id="copia_link" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>usuario-solicitar.php"><i class="fas fa-sign-in-alt"></i> <small>Cadastro</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>cliente-painel-historico-detalhes.php"><i class="fas fa-address-card"></i> <small>Rastreio</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>cliente-painel-saldo.php"><i class="fas fa-exchange-alt"></i> <small>Trocas</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>cliente-painel.php"><i class="fas fa-money-bill-alt"></i> <small>Pagamento</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>cliente-confirmar-pedido-parcial.php"><i class="fas fa-percentage"></i> <small>Pedido Parcial</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>cliente-seleciona-premio.php"><i class="fas fa-dollar-sign"></i> <small>Premiação</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>
        <div style="margin-top: 3px;" class="col-xs-6 col-sm-1">
          <button class="btn btn-info btn-block gerar_link" type="button" pagina="<?= $enderecoSite ?>politica_empresa.php"><i class="far fa-handshake"></i> <small>Política de vendas</small></button>
          <input type="text" id="" Value="" class="oculta_input">
        </div>

        <div style="margin-top: 3px;" class="col-xs-6 col-sm-2">
          <div class="input_colaborador">
            <select name="colaborador" id="colaborador" class="form-control">
              <option value="">Fornecedores:</option>
              <?php
              foreach ($fornecedores as $key => $fornecedor) { ?>
                <option value="<?= $fornecedor['id']; ?>">
                  <?php echo $fornecedor['razao_social']; ?>
                </option>
              <?php } ?>

            </select>
          </div>
        </div>
        
      </div>
      <script>
        $('[name=colaborador]').on('blur', function() {
          var colaborador = $('[name=colaborador]').val();
          $('[name=fornecedor]').val(colaborador);

        });
      </script> -->
      <!-- <h4>Para gerar links copie a URL da sua pesquisa e envie ao cliente</h4> -->
      <?php
      // $tela_link = true;
      // include_once('index.php');
      ?>




      <script type="text/javascript" src="js/gerar-link.js?1=1"> </script>
    </div>
  </div>

</div>
<?php
require_once 'rodape.php';
?>