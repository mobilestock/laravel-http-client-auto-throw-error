<?php

use MobileStock\helper\Globals;
use MobileStock\repository\UsuariosRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ZoopSellerService;


require_once __DIR__ . '/cabecalho.php';
require_once 'vendor/autoload.php';

?>

<link href="css/marketplace-fornecedor.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<div id="loading"></div>
<div class="container-fluid body-novo">
    <div class="card-title-market mb-4">
        <h2 class="display-4">Gerenciamento FAQ</h2>
        <h4><span id="label-nome-fornecedor"></span></h4>
    </div>
    <div class="card-market mb-4">
        <label>Situação:</label>
        <select class="form-control" id="situacao">
            <option value="">Respondidas</option>
            <option value="1">Aguard. Resposta</option>
            <option value="2">Recentes</option>
        </select>

    </div>
    <div id=lista-faq-pendente></div>
</div>
<script>
    buscaDuvida();

    function buscaDuvida() {
        $('#loading').html('<div class="v-overlay v-overlay--active theme--dark" style="z-index: 5;"><div class="v-overlay__scrim" style="opacity: 0.46; background-color: rgb(33, 33, 33); border-color: rgb(33, 33, 33);"></div><div class="v-overlay__content"><div role="progressbar" aria-valuemin="0" aria-valuemax="100" class="v-progress-circular v-progress-circular--indeterminate" style="height: 64px; width: 64px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="21.333333333333332 21.333333333333332 42.666666666666664 42.666666666666664" style="transform: rotate(0deg);"><circle fill="transparent" cx="42.666666666666664" cy="42.666666666666664" r="20" stroke-width="2.6666666666666665" stroke-dasharray="125.664" stroke-dashoffset="125.66370614359172px" class="v-progress-circular__overlay"></circle></svg><div class="v-progress-circular__info"></div></div></div></div>');

        var token = cabecalhoVue.user.token;
        fetch(`api_administracao/pay/help`, {
                method: 'GET',
                headers: new Headers({
                    token: token,
                })

            })
            .then(r => r.json())
            .then(json => {
                $('#lista-faq-pendente').html(json.data != '' ?
                    montaTabela(json.data) : `<div class="justify-content-center text-center"><p class="h4">${json.message} ;| </p></div>`);
                $('#loading').html('');
            })
            .catch(json => {
                $('#loading').html('');
            });
        $('#loading').html('');
    }

    function montaTabela(duvidas) {
        var str = '';
        var cont = 0;
        duvidas.map(i => {
            console.log(i.answer);
            cont += 1
            str += `<div class="card-market ${cont % 2 ? 'fundo-cinza':'bg-light'} text-justify">
                       ${i.frequencia > 1 ? ' <i class="fas fa-sort-up text-success fa-2x"></i> ' :'<i class="fas fa-sort-down text-danger fa-2x"></i> '}<label>${i.pergunta} <small>${i.data_pergunta}</small></label><br>
                        <input class="form-control" id="resposta_${i.id}" type="text" value="${(i.resposta ? i.resposta : '' )}" disabled><small>${i.data_resposta ? i.data_resposta : ''}</small></input><br>
                        <input class="form-control-sm" type="hidden" name="frequencia_${i.frequencia}" id="frequencia_${i.frequencia}" value="${i.frequencia}"><span id="f_${i.id}"></span>
                        <div class="row justify-content-center">
                            <button class="btn btn-primary btn-sm" id="salvar-${i.id}" onClick="responder(${i.id})" disabled>Salvar</button>
                            <button class="btn btn-light" id="editar-${i.id}" onClick="editar(${i.id})">Editar</button>
                            <button class="btn btn-success btn-sm" id="frequency-${i.id}" onClick="frequencia(${i.id})" disabled>Adicionar Frequência</button></div>
                    </div><br>
               
          `

        }).join('')
        return str;
    }

    function responder(id) {
        var token = cabecalhoVue.user.token;
        var resposta = $('#resposta_' + id).val();
        fetch(`api_administracao/pay/help/responde`, {
                method: "POST",
                headers: new Headers({
                    token: token,
                }),
                body: JSON.stringify({
                    resposta: resposta,
                    id: id
                })
            })
            .then(resp => (resp.json()))
            .then(json => {
                if (json.status == true) {
                    $.dialog({
                        title: 'Sucesso',
                        content: 'FAQ alterado com sucesso',
                        type: 'green'
                    })
                    console.log(json.message);
                    window.location.reload();
                } else {
                    $.dialog({
                        title: 'Erro',
                        content: 'Não foi possível alterar o FAQ',
                        type: 'red'
                    })
                }

                console.log(json.message);

            })

        $('#resposta_' + id).attr('disabled', true);
        $('#salvar-' + id).attr('disabled', true);
        $('#editar-' + id).attr('disabled', false);
        $('#frequency-' + id).attr('disabled', true);
        document.getElementsByName("frequencia_" + id)[0].type = "hidden";
        $('#f_' + id).html('');
        $('#f_' + id).removeClass('badge-danger');
        $('#f_' + id).removeClass('badge-sm');
    }

    function editar(id) {
        $('#resposta_' + id).attr('disabled', false);
        $('#salvar-' + id).attr('disabled', false);
        $('#editar-' + id).attr('disabled', true);
        $('#frequency-' + id).attr('disabled', false);
        $('#f_' + id).addClass('badge');
        $('#f_' + id).addClass('badge-danger');
        $('#f_' + id).addClass('badge-sm');
        $('#f_' + id).html(' Frequência');
        document.getElementsByName("frequencia_" + id)[0].type = "number";
    }
    $('#situacao').on('change', function() {
        var token = cabecalhoVue.user.token;
        var escolha = $('#situacao').val();
        if (escolha != 0) {
            $('#loading').html('<div class="v-overlay v-overlay--active theme--dark" style="z-index: 5;"><div class="v-overlay__scrim" style="opacity: 0.46; background-color: rgb(33, 33, 33); border-color: rgb(33, 33, 33);"></div><div class="v-overlay__content"><div role="progressbar" aria-valuemin="0" aria-valuemax="100" class="v-progress-circular v-progress-circular--indeterminate" style="height: 64px; width: 64px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="21.333333333333332 21.333333333333332 42.666666666666664 42.666666666666664" style="transform: rotate(0deg);"><circle fill="transparent" cx="42.666666666666664" cy="42.666666666666664" r="20" stroke-width="2.6666666666666665" stroke-dasharray="125.664" stroke-dashoffset="125.66370614359172px" class="v-progress-circular__overlay"></circle></svg><div class="v-progress-circular__info"></div></div></div></div>');
            fetch(`api_administracao/pay/help/${escolha}`, {
                    method: 'GET',
                    headers: new Headers({
                        token: token,
                    })

                })
                .then(r => r.json())
                .then(json => {
                    $('#lista-faq-pendente').html(json.data != '' ? montaTabela(json.data) : '');
                    $('#loading').html('');
                })
                .catch(json => {
                    $('#loading').html('');
                });
        } else {
            buscaDuvida();
        }
        $('#loading').html('');
    })

    function frequencia(id) {
        var token = cabecalhoVue.user.token;
        var frequencia = $('#frequencia_' + id).val();
        fetch(`api_administracao/pay/help/frequency`, {
                method: "POST",
                headers: new Headers({
                    token: token,
                }),
                body: JSON.stringify({
                    frequencia: frequencia,
                    id: id
                })
            })
            .then(resp => (resp.json()))
            .then(json => {
                if (json.status == true) {
                    $.dialog({
                        title: 'Sucesso',
                        content: 'Frequência FAQ alterada com sucesso',
                        type: 'green'
                    })
                    console.log(json.message);
                    window.location.reload();
                } else {
                    $.dialog({
                        title: 'Erro',
                        content: 'Não foi possível alterar o FAQ',
                        type: 'red'
                    })
                }

                console.log(json.message);

            })

        $('#resposta_' + id).attr('disabled', true);
        $('#salvar-' + id).attr('disabled', true);
        $('#editar-' + id).attr('disabled', false);
        $('#frequency-' + id).attr('disabled', true);
        document.getElementsByName("frequencia_" + id)[0].type = "hidden";
        $('#f_' + id).html('');
        $('#f_' + id).removeClass('badge-danger');
        $('#f_' + id).removeClass('badge-sm');
    }
</script>