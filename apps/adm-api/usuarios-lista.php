<?php
require_once 'cabecalho.php';
require_once 'classes/usuarios.php';

acessoUsuarioAdministrador();

$filtro = " WHERE 1=1";

  //filtra usuario
  if(isset($_POST['usuario']) && $_POST['usuario']!=""){
    $filtro.=" AND LOWER(u.nome) LIKE LOWER('%".$_POST['usuario']."%')";

  }

    //filtra usuario
    if(isset($_POST['razao_social']) && $_POST['razao_social']!=""){
      $filtro.=" AND LOWER(c.razao_social) LIKE LOWER('%".$_POST['razao_social']."%')";
  
    }

  //filtra categoria
  if(isset($_POST['tipo']) && $_POST['tipo']!=0){
    $filtro.=" AND u.nivel_acesso =".$_POST['tipo'];
  }

  $itens = 20;
  if(isset($_GET['p'])){
    $pagina = $_GET['p'];
  }else{
    $pagina = 0;
  }


  $temp = listaUsuarios($filtro);
  $qRegistros = sizeof($temp);
  $nPag = ceil($qRegistros/$itens);

$usuarios = listaUsuariosPagina($pagina*$itens,$itens,$filtro);

function nomeAcesso($nivel){
  if($nivel==10){
    return 'Cliente';
  }else if($nivel==30){
    return 'Fornecedor';
  }else if($nivel==20){
    return 'Transportador';
  }else if($nivel==51){
    return 'Vendedor';
  }else if($nivel==52){
    return 'Separador';
  }else if($nivel==53){
    return 'Estoquista';
  }else if ($nivel == 54) {
    return 'Conferidor';
  }else if($nivel==55){
    return 'Gerente';
  }else if($nivel==56){
    return 'Financeiro';
  }else if($nivel==57){
    return 'Administrador';
  }
}
?>
<div class="container-fluid body-novo">
<h2><b>Usuários</b></h2>
<form method="post">
<div class="pesquisa">
  <div class="row">
  <div class="col-sm-2">
    <label>Usuário:</label><input class="form-control bg-white" type="text" name="usuario"/>
  </div>
  <div class="col-sm-2">
    <label>Razao Social:</label><input class="form-control bg-white" type="text" name="razao_social"/>
  </div>
  <div class="col-sm-2">
    <label>Tipo:</label><select class="form-control" name="tipo">
<option value=""> -- Tipo</option>
<option value="1">Cliente</option>
<option value="2">Fornecedor</option>
<option value="3">Transportador</option>
<option value="4">Vendedor</option>
<option value="5">Estoquista</option>
<option value="6">Gerencial</option>
<option value="7">Gerente</option>
<option value="8">Financeiro</option>
<option value="8">Administrador</option>
    </select>
  </div>
  <div class="col-sm-2"><br/><button type="submit" name="pesquisar" class="btn btn-primary">Pesquisar</button>
  </div>
  
    </div>
</div>
</form><br/>
<div class="row">
  <div class="col-sm-2">
    <a href="usuario-cadastrar.php" class="btn btn-primary"><b>CADASTRAR</b></a>
  </div>
</div><br/>
<div class="row cabecalho">
  <div class="col-sm-1">#</div>
  <div class="col-sm-2">Nome</div>
  <div class="col-sm-3">Razao Social</div>
  <div class="col-sm-3">Nível</div>
  <div class="col-sm-2"></div>
</div>
<?php

foreach ($usuarios as $indice=>$usuario):
  if($indice%2==0){$estilo="fundo-branco";}else{$estilo="fundo-cinza";}
?>
<div class="row corpo <?=$estilo;?>">
<div class="col-sm-1"><?= $usuario['id']?></div>
<div class="col-sm-2"><?= $usuario['nome']?></div>

<div class="col-sm-3">
  <?=$usuario['razao_social'];?>
</div>
<div class="col-sm-3">
  <?=nomeAcesso($usuario['nivel_acesso']);?>
</div>
<div class="col-sm-1">
<?php if($usuario['bloqueado']==1){?>
<form method="post" action="controle/usuario-altera-bloqueio.php" id='formulario'>
  <input type="hidden" name="id" value="<?=$usuario['id']?>">
  <input type="hidden" name="bloqueado" value="0">
<button type="submit" class="btn btn-success">
  <span class="fa fa-check"></span></button></form>
<?php } else {?>
  <form method="post" action="controle/usuario-altera-bloqueio.php" id='formulario'><input type="hidden" name="id" value="<?=$usuario['id']?>">
    <input type="hidden" name="bloqueado" value="1">
  <button type="submit" class="btn btn-danger"><span class="fa fa-ban"></span></button></form>
  <?php }?>
</div>
<div class="col-sm-1"><form method="post" action="usuario-cadastrar.php"><input type="hidden" name="id" value="<?=$usuario['id']?>">
<button type="submit" class="btn btn-warning"><span class="fa fa-pen"></span></button></form></div>
<div class="col-sm-1"><form method="post" action="controle/usuario-remove.php" id='formulario'><input type="hidden" name="id" value="<?=$usuario['id']?>">
<button type="submit" class="btn btn-danger"><span class="fa fa-trash"></span></button></form></div>
</div>

<?php endforeach;
?>
<nav>
  <ul class="pagination">
    <li class="page-item">
      <a class="page-link" href="usuarios-lista.php?p=0" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>
    <?php for($i=0;$i<$nPag;$i++) {
      $estilo ="";
      if($pagina==$i){
        $estilo=="active";
      }
      ?>
    <li class="page-item"><a class="page-link" href="usuarios-lista.php?p=<?=$i;?>"><?=$i+1;?></a></li>
    <?php } ?>
    <li class="page-item <?=$estilo;?>">
      <a class="page-link" href="usuarios-lista.php?p=<?=$nPag-1;?>" aria-label="Next">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  </ul>
</nav>
</div>
<?php
require_once 'rodape.php';
?>
