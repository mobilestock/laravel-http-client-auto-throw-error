<?php

use MobileStock\service\Compras\ComprasService;

require_once '../classes/compras.php';
require_once '../regras/alertas.php';

acessoUsuarioFornecedor();

$_GET['id'] || die();
$id_compra = $_GET['id'];

$conexao = Conexao::criarConexao();
$compra = buscaCompra($id_compra);
?>
<html>

<head>
  <title>Mobile Stock - Relatório de compras</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css<?= $versao ?>">
  <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,700" rel="stylesheet">
  <link href="css/compras-relatorio.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar">
    <div class="container-fluid">
      <button onclick="imprimir()" class="btn btn-primary navbar-btn glyphicon glyphicon-print" data-toggle="dica" data-placement="bottom" title="IMPRIMIR"></button>
      <a href="javascript:history.go(-1)" class="btn btn-danger navbar-btn glyphicon glyphicon-remove" data-toggle="dica" data-placement="bottom" title="SAIR"></a>
    </div>
    </div>

  </nav>
  <div class="container">
    <h1><?= $_GET['id'] ?></h1>
    <table class="table table-striped">
      <tr>
        <td>
          <div class="form-group">
            <h2>Compra <b>#<?= $compra['id']; ?></b></h2>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="form-group">
            <h4>Fornecedor: <b><?= $compra['nome_fornecedor']; ?></b></h4>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="form-group">
            Data de previsão: <b><?php
                                  $data = date_create($compra['data_previsao']);
                                  echo date_format($data, 'd/m/Y');
                                  ?></b> / Situacao: <b><?= $compra['nome_situacao']; ?></b>
          </div>
        </td>
      </tr>
      <tr>
        <td>
          <table class="table table-bordered">
            <thead>
              <tr>
                <td>Id</td>
                <td>Produto</td>
                <td>Grade</td>
                <td>Caixas</td>
                <td>Quantidade</td>
                <td>Valor Unitario</td>
                <td>Valor Total</td>
                <td>Situação</td>
              </tr>
            </thead>
            <?php
            $produtos = buscaCompraProdutos($id_compra);
            foreach ($produtos as $produto) :
            ?>
              <tbody>
                <tr>
                  <td>
                    <div>
                      <b><?= $produto['id_produto'] ?></b>
                    </div>
                  </td>
                  <td>
                    <div class="form-group">
                      <b><?= $produto['desc_produto']; ?></b>
                    </div>
                  </td>
                  <td>
                    <!-- grade do produto -->
                    <table class="table table-bordered">
                      <tr>
                        <?php
                        $produtos_grade = ComprasService::buscaCompraProdutoGradeRelatorio($conexao, $id_compra, $produto['sequencia']);
                        foreach ($produtos_grade as $produto_grade) :
                        ?>
                          <td>
                            <div class="form-group">
                              <div><kbd><label for="inputCity">
                                    <?= $produto_grade['nome_tamanho'] ?></label>
                                </kbd><br>
                                <b><label for="inputCity"><?= $produto_grade['quantidade_total']; ?></label></b>
                              </div>
                            </div>
                          </td>
                        <?php endforeach ?>
                      </tr>
                    </table>

                  </td>
                  <td>
                    <div>
                      <b><?= $produto['caixas'] ?></b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <b><?= $produto['quantidade_total'] ?></b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <b>R$ <?= $produto['preco_unit'] ?></b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <b>R$ <?= $produto['valor_total'] ?></b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <b><?= $produto['nome_situacao'] ?></b>
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
          </table>
        </td>
      </tr>
      </tbody>
    </table>
    <div class="form-group">
      <h4><b>Resumo</b></h4>
    </div>
    <table class="table">
      <thead>
        <tr>
          <td>
            <b>Caixas</b>
          </td>
          <td>
            <b>Quantidade Total</b>
          </td>
          <td>
            <b>Valor Total</b>
          </td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <b><?= $compra['caixas']; ?></b>
          </td>
          <td>
            <b><?= $compra['quantidade_total']; ?></b>
          </td>
          <td>
            <b>R$ <?= $compra['valor_total']; ?></b>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js<?= $versao ?>"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js<?= $versao ?>"></script>

    <script>
      $(document).ready(function() {
        $('[data-toggle="dica"]').tooltip();
      });

      function imprimir() {
        window.print();
      }
    </script>
    <?php //require_once 'compras-lista-etiquetas.php';
    ?>
  </div>
</body>

</html>