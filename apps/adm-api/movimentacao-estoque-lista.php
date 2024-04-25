<?php
require_once 'cabecalho.php';

acessoUsuarioVendedor();

$filtro = ' WHERE 1=1 ';
$pesquisa = '';
//filtra codigo
if (isset($_POST['movimentacao']) && $_POST['movimentacao'] != 0) {
    $filtro .= ' AND me.id =' . $_POST['movimentacao'];
}

//filtra codigo
if (isset($_POST['codigo']) && $_POST['codigo'] != 0) {
    $filtro .= ' AND mei.id_produto =' . $_POST['codigo'];
}

//filtra descrição
if (isset($_POST['descricao']) && $_POST['descricao'] != '') {
    $filtro .= " AND LOWER(p.descricao) LIKE LOWER('%" . $_POST['descricao'] . "%')";
    $pesquisa = $_POST['descricao'];
}

//filtra origem
if (isset($_POST['origem']) && $_POST['origem'] != '') {
    $filtro .= " AND me.origem='" . $_POST['origem'] . "'";
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.js">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<div class="container-fluid">
  <div>
    <h2><b>Lista de movimentação de estoque</b></h2>
  </div>
  <div class="pesquisa">
    <form method="post">
      <div class="row">
        <div class="col-sm-2"><label>Número Mov:</label>
          <input class="form-control" type="number" name="movimentacao" />
        </div>
        <div class="col-sm-2"><label>Código Produto:</label>
          <input class="form-control" type="number" name="codigo" />
        </div>
        <div class="col-sm-3"><label>Descrição:</label><input class="form-control" type="text" value="<?= $pesquisa !=
        ''
            ? $pesquisa
            : '' ?>" name="descricao" /></div>
        <div class="col-sm-2"><label>Origem:</label>
          <select name="origem" class="form-control">
            <option value="">-- Origem</option>
            <option value="Compras Mobile">Compras Mobile</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-10"></div>
        <div class="col-sm-2"><button class="btn btn-success btn-block" type="submit"><b>FILTRAR</b></button></div>
      </div>
    </form>
  </div><br />
  <div class="row">
    <div class="col-sm-2">
      <a href="produtos-corrigir-estoque.php" class="btn btn-block btn-primary"><b>CORRIGIR</b></a>
    </div>
    <div class="col-sm-7"></div>
    <div class="col-sm-3">
      <a href="reposicao-estoque-lista.php" class="btn btn-block btn-success"><b>ORDENS DE REPOSIÇÃO</b></a>
    </div>
  </div><br />
  <div class="row cabecalho">
    <div class="col-sm-1">
      #
    </div>
    <div class="col-sm-2">
      Fornecedor
    </div>
    <div class="col-sm-2">
      Data
    </div>
    <div class="col-sm-1">
      Tipo
    </div>
    <div class="col-sm-2">
      Usuário
    </div>
    <div class="col-sm-2">
      Origem
    </div>
    <div class="col-sm-1">
    </div>
    <div class="col-sm-1">
    </div>
  </div>
  <?php if ($movimentacoes = listaMovimentacoes($filtro)) {
      foreach ($movimentacoes as $key => $movimentacao) {
          if ($key % 2 == 0) {
              $estilo = 'fundo-branco';
          } else {
              $estilo = 'fundo-cinza';
          } ?>
      <div class="linha  <?= $estilo ?>">
        <div class="row corpo">
          <div class="col-sm-1">

            <?= $movimentacao['id'] ?>
          </div>
          <div class="col-sm-2">
            <?= $movimentacao['razao_social'] ?>
          </div>
          <div class="col-sm-2">
            <?= date('d/m/Y', strtotime($movimentacao['data'])) ?>
          </div>
          <div class="col-sm-1">
            <?php if ($movimentacao['tipo'] == 'E') {
                echo 'Entrada';
            } elseif ($movimentacao['tipo']) {
                echo 'Saída';
            } ?>
          </div>
          <div class="col-sm-2">
            <?= $movimentacao['usuario'] ?>
          </div>
          <div class="col-sm-2">
            <?= $movimentacao['origem'] ?>
          </div>
          <div class="col-sm-1">
            <button type="button" onClick="buscarDetalhes(<?= $movimentacao[
                'id'
            ] ?>)" data-target="#movimentacao<?= $movimentacao[
    'id'
] ?>" data-toggle="collapse" class="btn desativado sobreProduto btn-dark"><i class="far fa-question-circle"></i></button>
          </div>
          <div class="col-sm-1">
            <a href="movimentacao-estoque-visualizar.php?id=<?= $movimentacao[
                'id'
            ] ?>" class="btn btn-primary"><span class="fa fa-search"></span></a>
          </div>
          <div class="col-sm-1">
          </div>
        </div>
        <div class="collapse multi-collapse" id="movimentacao<?= $movimentacao['id'] ?>">
          <div class="p-2">
            <div id="carregando<?php $movimentacao['id']; ?>">
              Carregando
            </div>
          </div>
        </div>
    <?php
      }
  } ?>
    <br>
    <div class="row">
      <div class="col-sm-2"><a href="estoque-config.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a></div>
    </div>
      </div>
      <?php require_once 'rodape.php'; ?>

<script src="mobilestockapi.js"></script>
<script>

  async function buscarDetalhes(id) {
    await MobileStockApi(`api_administracao/estoque/historico/${id}`)
    .then((resp) => resp.json())
    .then(({data}) => {
      renderizarDados(id, data)
    })
  }

  async function renderizarDados(id, data) {

    const div = document.querySelector(`div#movimentacao${id}`).querySelector("div.p-2")

    if (div) {
      div.innerHTML = `
      <div class="p-2">
        <div class="row cabecalho">
          <div class="col-sm-2">
            Produto
          </div>
          <div class="col-sm-2">
            Histórico de entrada
          </div>
          <div class="col-sm-1">
            Estoque
          </div>
          <div class="col-sm-1">
            Vendido
          </div>
          <div class="col-sm-1">
            <span style="margin-left:1em;">X</span>
          </div>
          <div class="col-sm-2">
            Preco de compra
          </div>
          <div class="col-sm-2">
            =
          </div>
          <div class="col-sm-1">
            Total
          </div>
        </div>
        <div class="row p-2">
          <div class="col-sm-2">
            <b>${data.items.produto}</b>
          </div>
          <div class="col-sm-2">
            <b>${data.items.historico}</b>
          </div>
          <div class="col-sm-1">
            <b>${data.estoque}</b>
          </div>
          <div class="col-sm-1">
            <b>${data.vendidos}</b>
          </div>
          <div class="col-sm-1">
          </div>
          <div class="col-sm-2">
            <b>${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(data.preco)}</b>
          </div>
          <div class="col-sm-2">
          </div>
          <div class="col-sm-1">
            <b>${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(eval(data.vendidos * parseFloat(data.preco)))}</b>
          </div>
        </div>
      </div>`
    }
  }

</script>
