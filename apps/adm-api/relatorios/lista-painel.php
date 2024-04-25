<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css<?=$versao?>">
  <link rel="stylesheet" href="../css/etiquetas.css<?=$versao?>">
  <script src='../js/JsBarcode.all.min.js'></script>

</head>
<body>
<div class="row">
  <div class="col-sm-1">
    <a href="../configuracoes-sistema.php" class="btn btn-danger btn-block"><b>VOLTAR</b><a>
  </div>
</div>
<div class="container">
<table>
  <tr>
  <?php for($i=1;$i<=300;$i++){
    $idBarcode = "barcode{$i}";
    ?>
  <td>
  <div class="col-sm-3">
    <div class="card" style="width: 18rem; height:25rem">
      <div class="card-body">
        <table>
          <tr>
            <td>
              <div><h2>PAINEL</h2></div>
            </td>
          </tr>
          <tr>
            <td>
              <div>
                <svg id="<?= $idBarcode; ?>"/>
                <script>
                  var codigo = '<?=$i;?>';
                  JsBarcode("#<?= $idBarcode; ?>", codigo , {
                    displayValue:true,
                    fontSize:30,
                    height: 50,
                    width: 1.5})
                    .blank(20)
                    .EAN5("12345");
                </script>
            </div>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>
</td>
<?php }; ?>
</tr>
</table>
</div>
</body>
