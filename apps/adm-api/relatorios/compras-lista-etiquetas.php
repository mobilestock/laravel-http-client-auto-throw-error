<?php

use MobileStock\service\Compras\ComprasService;

require_once '../classes/conexao.php';
require_once '../classes/compras.php';
require_once '../regras/alertas.php';

acessoUsuarioConferenteInternoOuAdm();

$id_compra = $_GET['id'];

$conexao = Conexao::criarConexao();
$codigo_barras = ComprasService::buscaCodigoBarrasCompra($conexao, $id_compra);
?>

<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/etiquetas.css">
  <script src='../js/JsBarcode.all.min.js'></script>

</head>

<body>
  <div class="row">
    <div class="col-sm-2">
      <a href="javascript:history.go(-1)" class="btn btn-danger btn-block"><b>VOLTAR</b><a>
    </div>
  </div>
  <div class="row">
    <?php foreach ($codigo_barras as $key => $codigo_barra) {
        $idBarcode = "barcode{$key}"; ?>

      <div class="col-sm-12">
        <div class="card" style="width: 18rem;">
          <div class="card-body">
            <table>
              <tr>
                <td>
                  <div>
                    <h2><?= $codigo_barra['desc_produto'] ?></h2>
                  </div>
                  <input type="hidden" name="codigo_barras" value="<?= $codigo_barra['codigo_barras'] ?>">
                </td>
              </tr>
              <tr>
                <td>
                  <table class="table table-bordered">
                    <tr>
                      <?php
                      $produtos_grade = ComprasService::buscaCompraProdutoGrade(
                          $conexao,
                          $id_compra,
                          $codigo_barra['id_sequencia']
                      );
                      foreach ($produtos_grade as $produto_grade) { ?>
                        <td>
                          <div class="form-group">
                            <div><kbd><label for="inputCity">
                                  <?= $produto_grade['nome_tamanho'] ?></label>
                              </kbd><br>
                              <b><label for="inputCity"><?= $produto_grade['quantidade'] ?></label></b>
                            </div>
                          </div>
                        </td>
                      <?php }
                      ?>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td>
                  <div>Data previsão: <b><?php
                  $data = date_create($codigo_barra['previsao']);
                  echo date_format($data, 'd/m/Y');
                  ?>
                    </b></div>
                </td>
              </tr>
              <tr>
                <td>
                  <div>
                    <svg id="<?= $idBarcode ?>">
                      <script>
                        var codigo = '<?php echo $codigo_barra['codigo_barras']; ?>';
                        JsBarcode("#<?= $idBarcode ?>", codigo, {
                          displayValue: true,
                          fontSize: 14,
                          height: 50,
                          width: 1.5
                        }).EAN13("123456789128");
                      </script>
                    </svg>
                  </div>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>

    <?php
    } ?>
  </div>
</body>
