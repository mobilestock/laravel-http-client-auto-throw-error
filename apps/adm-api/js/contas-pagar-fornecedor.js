//filtro de codigo do cheque
var $doc = $('html, body');
$('#codigo').keydown(function (e) {
    if (e.which === 13) {
        e.preventDefault();
        $('#codigo_'+$(this).val()).parent().find('input.abater_cheque').prop('checked',true);
        validarValores();
        var ancora = 'codigo_' + $(this).val();
        $doc.scrollTop($('#' + ancora).offset().top);
        $(this).val('');
    }
});

$('#valor_de').keydown(function (e) {
    if (e.which === 13) {
        e.preventDefault();
        filtraValorDe();
    }
});

$('#valor_ate').keydown(function (e) {
    if (e.which === 13) {
        e.preventDefault();
        filtraValorAte();
    }
});

function filtraValorDe(){
    $(".valor_cheque").each(function(){
        var valorDe = $('#valor_de').val();
        valorDe = parseFloat(valorDe);
        if($(this).val()>=valorDe && valorDe>0){
            $(this).parent().parent().css('background-color','lightgreen');
        }else{
            $(this).parent().parent().attr('style',false);
        }
    });
    $('#valor_de').val('');
 }

function filtraValorAte(){
    $(".valor_cheque").each(function(){
        var valorAte = $('#valor_ate').val();
        valorAte = parseFloat(valorAte);
        if($(this).val()<=valorAte && valorAte>0){
            $(this).parent().parent().css('background-color','lightgreen');
        }else{
            $(this).parent().parent().attr('style',false);
        }
    });
    $('#valor_ate').val('');
}

$('#downloadAll').on('click', validarValores);
$('.baixar').on('click', validarValores);
$('.abater_defeito').on('click', validarValores);
$('.abater_cheque').on('click', validarValores);

$(document).ready(function () {
    $('#btn_conferir_pagamento').prop('disabled', true);
});

function validaBotao(valorPago, valorSelecionado, valorDefeito, valorCheque) {
    //verificação para habilitar botao
    if (valorPago <= 0) {
        $('#btn_conferir_pagamento').prop('disabled', true);
        if ((valorDefeito > 0 || valorCheque > 0) && (valorDefeito + valorCheque) > valorSelecionado) {
            alert('O valor dos lançamentos(' + valorSelecionado + ') é menor que do defeito(' + valorDefeito + ') e cheques selecionados(' + valorCheque + '). Verifique!');
        }
    } else {
        $('#btn_conferir_pagamento').prop('disabled', false);
    }
}

function validarValores() {

    //valor selecionado para ser baixado
    var valorSelecionado = 0;
    $('.baixar').each(function () {
        if ($(this).prop("checked")) {
            var valor = $(this).parent().parent().find('.valor').val();
            valor = parseFloat(valor);
            valorSelecionado += valor;
        }
    });
    valorSelecionado.toFixed(2);

    //valor de defeito selecionado
    var defeitos = 0;
    var valorDefeito = 0;
    $('.abater_defeito').each(function () {
        if ($(this).prop("checked")) {
            var valor = $(this).parent().parent().find('.valor_defeito').val();
            valor = parseFloat(valor);
            valorDefeito += valor;
            defeitos++;
        }
    });
    valorDefeito.toFixed(2);

    //valor de cheque selecionado
    var cheques = 0;
    var valorCheque = 0;
    $('.abater_cheque').each(function () {
        if ($(this).prop("checked")) {
            var valor = $(this).parent().parent().find('.valor_cheque').val();
            valor = parseFloat(valor);
            valorCheque = parseFloat(valorCheque);
            valorCheque += valor;
            cheques++;
        }
    });
    valorCheque.toFixed(2);

    //valor pago
    var valorPago = 0;
    valorPago = valorSelecionado - valorCheque - valorDefeito;
    valorPago = parseFloat(valorPago);
    valorPago = valorPago.toFixed(2);

    validaBotao(valorPago, valorSelecionado, valorDefeito, valorCheque);

    filtraValorDe();
    filtraValorAte();

    $('#valor_selecionado').text(valorSelecionado);
    $('#valor_defeitos_selecionados').text(valorDefeito);
    $('#pares_selecionados').text(defeitos);
    $('#valor_cheques_selecionados').text(valorCheque);
    $('#qte_cheques_selecionados').text(cheques);

    if (valorPago > 0) {
        $('#dinheiro').val(valorPago);
        $('#dinheiro_resumo').text(valorPago);
    } else {
        $('#dinheiro').val(0.00);
        $('#dinheiro_resumo').text(0.00);
    }
}