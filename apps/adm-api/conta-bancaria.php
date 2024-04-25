<?php
require_once 'cabecalho.php';
require_once 'classes/conta-bancaria.php';

acessoUsuarioAdministrador();
    $listaConta =listaContasBancarias();
if(isset($_POST['enviar'])){
  $nomeConta = filter_input(INPUT_POST,'nome',FILTER_SANITIZE_SPECIAL_CHARS);
    if($nomeConta!=""){
        cadastraContaBancaria($nomeConta);
        header('location:conta-bancaria.php');
        die;
    }
}

?>
<div class="container-fluid">
  <h1 style="font-size: 22px">Cadastro de Conta bancaria</h1><br>
  <div class='text-center'>
  <form method="POST">
    <div class="form-row justify-content">
      <div class="col col-lg-5">
        <input type="text" class="form-control"name="nome">
      </div>
      <div class="col-4 col-lg-2">
        <button type="submit" class="form-control btn-primary" name="enviar"><b>ENVIAR</b></button>
      </div>
    </div>
  </form>
  <br>
  </div>
  <div class="row">
      <div class="col s12 m6 push-m3">
      <div class="row cabecalho">
          <div class="col">Conta Bancaria</div>
      </div>
      <?php foreach ($listaConta as $key => $value) {
              if($key%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
        ?>
      <div class="row corpo <?=$estilo?>">
              <div class="col"><?= $value['nome'];?></div>
          </div>
          <?php }?>
      </div>
  </div><br>
  <div class="form-row justify-content">
      <div class="col-sm-2">
          a<a href="configuracoes-sistema.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a>
      </div>
    </div>
  </div>
</div>
<?php
require_once 'rodape.php';