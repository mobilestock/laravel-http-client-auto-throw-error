<?php
require_once 'cabecalho.php';
require_once 'model/configuracao.php';

acessoUsuarioAdministrador();

$listaCategorias = Configuracao::listaCategorias();

if(isset($_POST['nome'])&&$_POST['nome']!=''){
    $nomeCategoria = $_POST['nome'];
    $alturaSolado = 0;
    $alturaSolado = isset($_POST['altura_solado']) ? 1 : 0;
    Configuracao::cadastrarCategoria($nomeCategoria,$alturaSolado);
    header('location:produtos-categoria.php');die;
}

?>
<div class="container-fluid"><br>
  <h1>Cadastro de Conta bancaria</h1><br>

  <form method="POST">
    <div class="row">
      <div class="col-sm-3">
        <label>Nome da categoria</label>
        <input type="text" class="form-control"name="nome">
      </div>
      <div class="col-sm-1">
        <label>Altura solado</label>
        <div><input type="checkbox" name="altura_solado">
        </div>
      </div>
      <div class="col-sm-2"><br><div>
        <button type="submit" class="form-control btn-primary" name="enviar"><b>ENVIAR</b></button></div>
      </div>
    </div>
  </form>
<br>
  <div class="row">
      <div class="col s12 m6 push-m3">
      <div class="row cabecalho">
          <div class="col-2">Categoria</div>
          <div class="col">Obrigatorio altura solado</div>
      </div>
      <?php foreach ($listaCategorias as $key => $categoria) {
              $checked = $categoria['mostrar_altura_salto']==1 ? "checked" : "";
              if($key%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
        ?>
      <div class="row corpo <?=$estilo?>">
              <div class="col-2"><?= $categoria['nome'];?></div>
              <div class="col"><input type="checkbox" disabled <?=$checked;?> name="altura_solado"></div>
          </div>
          <?php }?>
      </div>
  </div><br>
  <div class="form-row justify-content">
      <div class="col-sm-2">
          <a href="configuracoes-sistema.php" class="btn btn-danger btn-block"><b>VOLTAR</b></a>
      </div>
    </div>
  </div>
</div>
<?php
require_once 'rodape.php';