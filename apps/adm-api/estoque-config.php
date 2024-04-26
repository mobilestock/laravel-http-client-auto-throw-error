<?php
require_once 'cabecalho.php';

acessoUsuarioVendedor();
?>
<link rel="stylesheet" href="css\estoque-config.css">
<div class="container-fluid">
  <h1 class="text-center">Controle de estoque</h1>
  <div class="row">
    <div class="col-sm-6">
      <a href="estoque-conferencia-localizacao.php" class="btn btn-block btn-primary botao-menu-sistema">Conferência por
        Localização</a>
    </div>
    <div class="col-sm-6">
      <a href="estoque-conferencia-referencia.php" class="btn btn-block btn-primary botao-menu-sistema">Conferência por
        Referência</a>
    </div>
    <!-- <div class="col-sm-6">
      <a href="estoque-sem-localizacao.php" class="btn btn-block btn-primary botao-menu-sistema">Produtos sem
        Localização</a>
    </div> -->
    <!-- <div class="col-sm-6">
      <a href="logs_movimentacao.php" class="btn btn-block btn-primary botao-menu-sistema">Log da movimentação de estoque</a>
    </div> -->
    <!-- <div class="col-sm-6">
      <a href="fotos-para-separar.php" class="btn btn-block btn-primary botao-menu-sistema">Fotos para separar</a>
      <a href="correcao.php" class="btn btn-block btn-primary botao-menu-sistema">Pares Corrigidos</a>
    </div> -->

  </div><br>
  <div class="row">
    <div class="col-sm-2">
      <a href="menu-sistema.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a>
    </div>
  </div><br>

</div>
<!--<script src="js/mostrar-detalhes.js--><?//= $versao ?><!--"></script>-->
<?php require_once 'rodape.php';
