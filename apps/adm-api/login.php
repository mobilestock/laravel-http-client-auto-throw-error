<?php

require_once __DIR__ . '/vendor/autoload.php';

header('Location: ' . $_ENV['URL_AREA_CLIENTE'] . 'login');

// mostraAlerta("danger");
// mostraAlerta("success");

// if(usuarioEstaLogado()){
//   header("Location:menu-sistema.php");
//   die();
// }
?>
<!-- <br><br>
    <div class="card container">
        <div class="card-body text-center">
            <form action="controle/usuario-login.php" method="post">
            <input type="hidden" name="local" value="<?=isset($_GET['local'])?$_GET['local']:null?>">
            <h3>Login</h3><br>
                <input type="text" name="nome" placeholder="Usuário (Nome ou Email ou CPF ou CNPJ)" required autofocus class="form-control"><br>
                <input type="password" name="senha" placeholder="Senha" required autofocus class="form-control"><br>
                <button type="submit" class="btn btn-entrar btn-block">ENTRAR</button>
                <a href="usuario-esqueci-minha-senha.php" class="pull-right link-esqueci"><u>ESQUECI A SENHA</u></a><br><br>
                <a href="usuario-solicitar.php" class="btn btn-cadastrar btn-block">CADASTRAR</a>
            </form>
        </div>
    </div> -->
