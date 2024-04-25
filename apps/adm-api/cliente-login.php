<?php

require_once __DIR__ . '/vendor/autoload.php';
header('Location: ' . $_ENV['URL_AREA_CLIENTE'] . 'login');
/*

// require_once 'cabecalho.php';
?>
<!-- <script src="https://cdn.rawgit.com/igorescobar/jQuery-Mask-Plugin/master/src/jquery.mask.js"></script> -->
<!-- <link rel="stylesheet" href="css/cliente-login.css">

<div id="my-container" class="container-fluid">
    <input type="hidden" id="pagamento" value="<?= $_GET['pagamento'] ?>">
    <?php if (!isset($_GET['pagamento'])) : ?>
        <div class="row ">

            <div class="col">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
            <div class="col titulo">Login
            </div>
            <div class="col">
            </div>

        </div>
    <?php endif; ?>

    <form action="controle/usuario-login.php" onsubmit="enviaFormularioLogin(this)" method="post">
        <div class="email-senha">

            <div class="row d-flex justify-content-center">
                <div class="col-0 col-xl-3"></div>
                <div class="col-10 m-0 p-0 col-xl-6">
                    <div class="div-input">
                        <input type="text" name="nome" placeholder="Usuário (Nome)" class="input-text">
                    </div>
                </div>
                <div class="col-0 col-xl-3"></div>
            </div>


            <div class="row d-flex justify-content-center">
                <div class="col-0 m-0 col-xl-3"></div>
                <div class="col-10 m-0 p-0 col-xl-6">
                    <div class="div-input">
                        <input type="password" name="senha" placeholder="Senha" class="input-text">
                    </div>
                    <span class="info-inportant">Minimo 4 caracteres</span>
                </div>
                <div class="col-0 col-xl-3"></div>
            </div>

        </div>
        <div class="confirma-cadastro">
            <div class="row d-flex justify-content-center">
                <div class="col-0 col-xl-3"></div>
                <div class="col-10 m-0 p-0 col-xl-6">
                    <div class="div-button">
                        <button type="submit" class="btn-ms">Entrar</button>
                    </div>
                </div>
                <div class="col-0 col-xl-3"></div>
            </div>
        </div>
        <div class="confirma-cadastro">
            <div class="row d-flex justify-content-center">
                <div class="col-0 col-xl-3"></div>
                <div class="col-10 m-0 p-0 col-xl-6">
                    <div class="div-button">
                        <a href="cliente-cadastro.php" class="btn-block a-ms">Cadastrar-se</a>
                    </div>
                </div>
                <div class="col-0 col-xl-3"></div>
            </div>
        </div>
        <div class="row d-flex justify-content-center">
            <div class="col-0 col-xl-3"></div>
            <div class="col-10 m-0 p-0 col-xl-6">

                <div class="msg"></div>

            </div>
            <div class="col-0 col-xl-3"></div>
        </div>

    </form>
    <div class="row d-flex justify-content-center">
        <div class="col-0 col-xl-3"></div>
        <div class="col-10 m-0 p-0 col-xl-6">
            <a href="usuario-esqueci-minha-senha.php">
                <u>Esqueci minha senha</u>
            </a>
        </div>
        <div class="col-0 col-xl-3"></div>
    </div>

</div>


<script src="js/cliente-cadastro.js<?= $versao; ?>"></script>
<script>
    function enviaFormularioLogin(e) {
        event.preventDefault();

        let form = new FormData();
        form.append('nome', document.querySelector('input[name="nome"]').value);
        form.append('senha', document.querySelector('input[name="senha"]').value);
        fetch('controle/usuario-login.php', {
            method: 'POST',
            body: form
        }).then(r => r.json()).then(json => {

            let pagamento = document.querySelector('#pagamento').value === 'true';

            if (pagamento === true) {
                localStorage.setItem('id_colaborador', json.data.usuario.id_colaborador)
                localStorage.setItem('regime', json.data.usuario.regime);
                localStorage.setItem('token', json.data.usuario.token);
                $('#botao-fechar-modal-cadastro').trigger('click');
            } else if (json.status === true) {
                window.location.href = json.data.local
            } else {
                alert(json.message)

            }

            let openRequest = window.indexedDB.open("carrinho-virtual", cabecalhoVue.versaoBanco);
            let conexao;

            openRequest.onsuccess = async function(e) {
                conexao = e.target.result;

                let transaction = conexao.transaction(["carrinho"], "readwrite");
                let store = transaction.objectStore("carrinho");

                store.clear();
            };
        });
    }
</script>*/
