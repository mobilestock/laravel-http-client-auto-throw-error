<?php
require_once 'cabecalho.php';
require_once 'classes/vales.php';

acessoUsuarioVendedor();

?>
  <div><h2><b>Cadastro de representante</b></h2></div>
<div class="pesquisa">
<div class="row">
  <div class="col-sm-4">
    <div class="form-group">
         <label>Nome:</label> <input type="text" class="form-control" name="nome">
    </div>
  </div>
  <div class="col-sm-4">
    <div class="form-group">
         <label>Cliente:</label> <select class="form-control" name="id_colaborador">

         </select>
    </div>
  </div>
  <div class="col-sm-2"><br/>
    <button type="submit" class="btn btn-primary btn-block"><b>SALVAR</b></button>
  </div>
</div>
</div>
