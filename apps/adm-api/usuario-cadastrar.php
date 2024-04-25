<?php

use MobileStock\repository\UsuariosRepository;

require_once 'cabecalho.php';
require_once 'classes/usuarios.php';
require_once 'classes/colaboradores.php';

acessoUsuarioAdministrador();

// edita usuario
if (isset($_POST["id"])) {
  // busca usario que está sendo editado
  $id = $_POST["id"];
  $usuario = buscaCadastroUsuario($id);
  $link = "controle/usuario-altera.php";
} else {
  // deixa os campos de usuario vazios
  $id = 0;
  $usuario = array(
    "id" => $id,
    "nome" => "",
    "senha" => "",
    "id_colaborador" => 0,
    "nivel_acesso" => 10,
    "bloqueado" => 0,
    "email" => "",
    "cnpj" => "",
    "telefone" => ""
  );
  $link = "controle/usuario-insere.php";
}

$acessos = array(
  array(10, "Cliente"),
  array(20, "Transportador"),
  array(30, "Fornecedor"),
  array(51, "Vendedor"),
  array(52, "Separador"),
  array(53, "Estoquista"),
  array(54, "Conferente"),
  array(55, "Gerente"),
  array(56, "Financeiro"),
  array(57, "Administrador")
);
$bloqueado = $usuario['bloqueado'] ? "checked='checked'" : "";
?>
<style>
  .list-item {
    display: inline-block;
    margin-right: 10px;
  }

  .list-enter-active,
  .list-leave-active {
    transition: all 1s cubic-bezier(.33, .86, .38, .97);
  }

  .list-enter,
  .list-leave-to {
    opacity: 0;
    transform: translateX(-30px);
  }
</style>
<div id="app" class="container-fluid body-novo">
  <h2>Cadastrar Usuário</h2>
  <form action="<?= $link ?>" method="post" id='formulario'>
    <div class="row">
      <div class="form-group">
        <input type="hidden" name="id" value="<?= $usuario['id']; ?>" />
      </div>
      <div class="col-sm-3">
        <label>Nome</label> <input type="text" class="form-control" name="nome" value="<?= $usuario['nome']; ?>" />
      </div>
      <div class="col-sm-3">
        <label>Senha</label> <input type="password" class="form-control" name="senha" placeholder="*****" />
      </div>
      <div class="col-sm-3">
        <label>Acesso</label>
        <select name="acesso" class="form-control">
          <?php foreach ($acessos as $acesso) :
          ?>
            <option value="<?= $acesso[0] ?>" <?php if ($acesso[0] == $usuario['nivel_acesso']) { ?> selected="selected" <?php } ?>><?= $acesso[1] ?></option>
          <?php
          endforeach;
          ?>
        </select>
      </div>
      <div class="col-sm-3"><label>Colaborador</label><br />
        <select class="form-control" name="id_colaborador" required>
          <option value="0">-- Colaborador</option>
          <?php
          $colaboradores = listaPessoas();
          foreach ($colaboradores as $key => $colaborador) :
          ?>
            <option value="<?= $colaborador['id']; ?>" <?php if ($colaborador['id'] == $usuario['id_colaborador']) { ?> selected="selected" <?php } ?>><?= $colaborador['razao_social']; ?></option>
          <?php
          endforeach;
          ?>
        </select>
      </div>
    </div><br />
    <div class="row">
      <div class="col-sm-3">
        <label>E-mail</label> <input type="text" class="form-control" name="email" value="<?php if (isset($usuario['email'])) {
                                                                                            echo $usuario['email'];
                                                                                          } ?>" />
      </div>
      <div class="col-sm-3">
        <label>CNPJ/CPF</label> <input type="text" class="form-control" name="cnpj" value="<?php if (isset($usuario['cnpj'])) {
                                                                                              echo $usuario['cnpj'];
                                                                                            }; ?>" />
      </div>
      <div class="col-sm-3">
        <label>Telefone</label> <input type="text" class="form-control" name="telefone" value="<?php if (isset($usuario['telefone'])) {
                                                                                                  echo $usuario['telefone'];
                                                                                                }; ?>" />
      </div>

      <div class="col-sm-3">
        <h6><b>Opções</b></h6>
        <span class="mt-1">
          <label class="font-weight-normal" for="bloqueado">Bloqueado</label>
          <input type="checkbox" class="checkmark" id="bloqueado" name="bloqueado" <?= $bloqueado; ?> value="true" />
        </span>
      </div>
    </div>
    <div class="form-group row mt-2">

      <div id="campoTipos" class="col-sm-3 d-none">
        <label for="tipo" class="d-flex">Tipos
          <transition-group name="list" class="d-flex" div="div">
            <div @click="removeItemListaTipos(tipo)" class="badge badge-dark m-1 d-flex align-items-center justify-content-center" v-for="(tipo, i) in listaTiposAdicionados" :key="i">
              {{ tipo }}
            </div>

          </transition-group>
        </label>
        <input @input="adicionaItemListaTipos" list="tipos" id="tipo" class="form-control">
        <input type="hidden" name="tipos" :value="listaTiposAdicionadosValores.join(',')">
        <input type="hidden" id="tiposInp" value="<?= $usuario['tipos'] ?>">
        <datalist id="tipos">
          <option v-for="tipo in tipos" :value="tipo.nome"></option>
        </datalist>
      </div>
    </div>
    <br />
    <div class="form-group row">
      <div class="col-sm-2">
        <button type="submit" formaction="usuarios-lista.php" class="btn btn-danger btn-block"><b>VOLTAR</b></button>
      </div>
      <div class="col-sm-8">

      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-primary btn-block"><b>SALVAR</b></button>
      </div>


    </div>
  </form>
</div>
<script src="js/usuario-cadastrar.js"></script>
<?php
require_once 'rodape.php';
?>