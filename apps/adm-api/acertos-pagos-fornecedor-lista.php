<?php
/*
require_once 'cabecalho.php';
require_once 'classes/acertos.php';
require_once 'classes/usuarios.php';
require_once __DIR__ . '/vendor/autoload.php';

use MobileStock\repository\LancamentoSellerRepository;

$l = new LancamentoSellerRepository();
acessoUsuarioFinanceiro();

$filtro = 'WHERE 1=1 AND acertos.tipo="P" AND acertos.origem="Fornecedor"';

if (isset($_POST['numero']) && $_POST['numero'] != "") {
  $filtro .= " AND lancamento_financeiro_seller.numero_documento = " . $_POST['numero'];
}

if (isset($_POST['acerto']) && $_POST['acerto'] != "") {
  $filtro .= " AND acertos.id = " . $_POST['acerto'];
}

if (isset($_POST['cliente']) && $_POST['cliente'] != "") {
  $filtro .= " AND lower(colaboradores.razao_social) LIKE '%" . $_POST['cliente'] . "%'";
}

if (isset($_POST['origem']) && $_POST['origem'] != "") {
  $filtro .= " AND lower(acertos.origem) = lower('" . $_POST['origem'] . "')";
}

if (isset($_POST['usuario']) && $_POST['usuario'] != "") {
  $filtro .= " AND acertos.usuario = " . $_POST['usuario'];
}

if ((isset($_POST['data_de']) && $_POST['data_de'] != "")) {
  $filtro .= " AND DATE(acertos.data_acerto) >= '" . $_POST['data_de'] . "'";
}

if ((isset($_POST['data_ate']) && $_POST['data_ate'] != "")) {
  $filtro .= " AND DATE(acertos.data_acerto) <= '" . $_POST['data_ate'] . "'";
}

$usuarios = listaUsuariosFinanceiro();

?>
<div class="container-fluid">
  <h2><b>Lista de pagamentos de Fornecedores</b></h2>
  <div class="pesquisa">
    <form method="post">
      <div class="row">
        <div class="col-sm-2">
          <label>Acerto</label>
          <input class="form-control" value="<?= isset($_POST['acerto']) ? $_POST['acerto'] : "" ?>" type="number" name="acerto" />
        </div>
        <div class="col-sm-2">
          <label>Pedido</label>
          <input class="form-control" value="<?= isset($_POST['numero']) ? $_POST['numero'] : "" ?>" type="number" name="numero" />
        </div>
        <div class="col-sm-4">
          <label>Cliente</label>
          <input class="form-control" value="<?= isset($_POST['cliente']) ? $_POST['cliente'] : "" ?>" type="text" name="cliente" />
        </div>
        <div class="col-sm-2">
          <label>Data De</label>
          <input class="form-control" value="<?= isset($_POST['data_de']) ? $_POST['data_de'] : "" ?>" type="date" name="data_de" />
        </div>
        <div class="col-sm-2">
          <label>Data Até</label>
          <input class="form-control" value="<?= isset($_POST['data_ate']) ? $_POST['data_ate'] : "" ?>" type="date" name="data_ate" />
        </div>
      </div><br />
      <div class="row">
        <div class="col-sm-2">
          <label>Origem</label>
          <select name="origem" class="form-control">
            <option value="">-- Origem</option>
            <option <?= isset($_POST['origem']) && $_POST['origem'] == 'Faturamento' ? 'selected' : '' ?> value="Faturamento">Faturamento</option>
            <option <?= isset($_POST['origem']) && $_POST['origem'] == 'La' ? 'selected' : '' ?> value="Lançamento Manual">Lançamento Manual</option>
            <option <?= isset($_POST['origem']) && $_POST['origem'] == 'Baixa Lançamento' ? 'selected' : '' ?> value="Baixa Lançamento">Baixa Lançamento</option>
            <option <?= isset($_POST['origem']) && $_POST['origem'] == 'Vales' ? 'selected' : '' ?> value="Vales">Vales</option>
          </select>
        </div>
        <div class="col-sm-2">
          <label>Usuário</label><select name="usuario" class="form-control">
            <option value="">-- Usuário</option>
            <?php foreach ($usuarios as $key => $usuario) : ?>
              <option value="<?= $usuario['id']; ?>" <?= isset($_POST['usuario']) && $_POST['usuario'] == $usuario['id']
                                                        ? 'selected'
                                                        : '' ?>><?= $usuario['nome']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4">
        </div>
        <div class="col-sm-2"><br /><a href="<?= $_SERVER['PHP_SELF']; ?>" class="btn btn-danger btn-block"><b>LIMPAR</b></a></div>
        <div class="col-sm-2"><br /><button class="btn btn-success btn-block" type="submit"><b>FILTRAR</b></button></div>
      </div><br />
    </form>
  </div><br />
  <div class="row cabecalho">
    <div class="col-sm-1">
      Acerto
    </div>
    <div class="col-sm-2">
      Data Pagamento
    </div>
    <div class="col-sm-1">
      Tipo
    </div>
    <div class="col-sm-1">
      Origem
    </div>
    <div class="col-sm-3">
      Razão Social
    </div>

    <div class="col-sm-2">
      Valor
    </div>
    <div class="col-sm-1">

    </div>
    <div class="col-sm-1">

    </div>
  </div>
  <?php $listaAcertos = $l->buscaListaAcertos($filtro);
  foreach ($listaAcertos as $key => $acerto) :
    if ($key % 2 == 0) {
      $estilo = "fundo-branco";
    } else {
      $estilo = "fundo-cinza";
    }
  ?>
    <div class="row corpo <?= $estilo; ?>">
      <div class="col-sm-1">
        <?= $acerto['id']; ?>
      </div>
      <div class="col-sm-2">
        <b><?php $data = date_create($acerto['data_acerto']);
            echo date_format($data, 'd/m/Y H:i:s'); ?></b>
      </div>
      <div class="col-sm-1">
        <?= $acerto['tipo']; ?>
      </div>
      <div class="col-sm-1">
        <?= $acerto['origem']; ?>
      </div>
      <div class="col-sm-3">
        <?= $acerto['razao_social']; ?>
      </div>
      <div class="col-sm-2">
        <?= $acerto['valor_total']; ?>
      </div>
      <div class="col-sm-1">
        <a href="contas-pagar-fornecedor-conferir-pago.php?id=<?= $acerto['id']; ?>" class="btn btn-primary"><span class="fa fa-search"></span></a>
      </div>
      <div class="col-sm-1">
        <a href="controle/contas-pagar-fornecedor-excluir.php?id=<?= $acerto['id']; ?>" class="btn btn-danger remover-acerto"><span class="fa fa-trash"></span></a>
      </div>
    </div>
  <?php
  endforeach;
  ?>
  <br />
  <div class="row">
    <div class="col-sm-2">
      <a href="contas-pagar-lista.php" class="btn btn-block btn-danger"><b>VOLTAR</b></a>
    </div>
  </div>
</div>
<script>
  $('a.remover-acerto').confirm({
    title: "Atenção",
    content: "Deseja excluir esse acerto? Os lançamentos voltaram em aberto e documentos gerados são excluídos.",
    buttons: {
      ok: {
        text: 'Continuar',
        btnClass: 'btn-blue',
        action: function() {
          location.href = this.$target.attr('href');
        }
      },
      cancel: {
        text: 'Fechar'
      }
    }
  });
</script>
<?php require_once 'rodape.php';
*/
?>