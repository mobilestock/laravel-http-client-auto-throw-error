<?php

use MobileStock\repository\UsuariosRepository;

require_once 'cabecalho.php';
require_once 'vendor/autoload.php';
acessoUsuarioVendedor();
?>
<div class="container-fluid">      
    <div class="row mt-3 d-flex justify-content-center">
        <div class="col-sm-auto col-auto">
            <?php
            if ($token = isset($_GET['token'])?$_GET['token']:false) {
                $usuario = new UsuariosRepository();
                if($usuario->armazenaTokenUsuarioMaquina(idUsuarioLogado(),$token)){
                    ?><h3 class='btm bg-verde'>token atualizado com sucesso!</h3><?php
                }else{
                    ?><h3 class='btm bg-vermelho'>Erro para autenticar token</h3><?php
                }
            }else{
                ?><h3 class='btm bg-vermelho'>Não existe tiken para autenticação</h3><?php
            }
            ?>
            <a class="btn btn-block bg-primary my-4" href="appSeparacao://">Voltar ao app</a>
        </div>
    </div>       
</div>
<?php require_once "rodape.php";?>