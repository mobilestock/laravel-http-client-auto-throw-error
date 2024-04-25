<?php
require_once __DIR__ . '/regras/alertas.php';
require_once __DIR__ . '/cabecalho.php';
extract($_GET)

?>

<input type="hidden" id="psw" value="<?= $psw ?>">
<div class="card col-sm-12 col-12 p-0">
    <div class="card-header bg-light m-1">Cadastro iToken Mobile Pay</div>
    <div class="card-body">
        <div class="form-group">
            <label for="new">Senha</label>
            <input type="password" class="form-control" id="new" aria-describedby="Senha" placeholder="Senha">
            <small id="erro" class="form-text text-muted">A senha deve conter 6 digitos.</small>
        </div>
        <div class="form-group">
            <label for="confirm-new">Confirmar Senha</label>
            <input type="password" class="form-control" id="confirm-new" placeholder="Password">
        </div>
        <div class="card-footer justify-content-center bg-white">
            <button id="confirm" class="btn btn-dark">Confirmar Cadastro</button>
        </div>
    </div>
</div>
<script>
    $('#confirm').click(function() {
        $('#erro').css('color', 'black');
        $('#new').removeClass('is-invalid');
        $('#confirm-new').removeClass('is-invalid');
        var pass = $('#new').val();
        var pass_confirm = $('#confirm-new').val();
        if (pass.trim() != '' && pass_confirm.trim() != '') {
            if (pass != pass_confirm) {
                $('#confirm-new').addClass('is-invalid');
            } else {
                if (pass.length != 6) {
                    $('#erro').css('color', 'red');
                    $('#new').addClass('is-invalid');
                    $('#confirm-new').addClass('is-invalid');
                } else {
                    $('#erro').css('color', 'black');
                    $('#new').removeClass('is-invalid');
                    $('#confirm-new').removeClass('is-invalid');
                    fetch(`api_administracao/pay/itoken/create`, {
                            method: "POST",
                            headers: new Headers({
                                token: $('#psw').val(),
                            }),
                            body: JSON.stringify({
                                senha: pass
                            }),
                        })
                        .then(resp => resp.json())
                        .then(resp => {
                            if (resp.status == true) {
                                $.dialog({
                                    title: 'Sucesso!',
                                    content: 'Token Cadastrado com sucesso.',
                                    type: 'green'
                                })
                            } else {
                                $.alert({
                                    title: 'Erro!',
                                    content: resp.message
                                })
                            }

                        })
                        .catch(resp => console.log(resp))
                        setTimeout(function() {
                            window.location.href = "cliente-login.php";
                        }, 5000);

                }
            }
        } else {
            $('#new').addClass('is-invalid');
            $('#confirm-new').addClass('is-invalid');
        }
    })
</script>