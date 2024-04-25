$('#faturar').on('click',verificaCredito);

function verificaCredito(){
    var creditos = [];
    $.each($(".cb_credito"),function(){
        if($(this).prop("checked")){
          var numero = $(this).parent().find(".id_credito").val();
          creditos.push(numero);
        }
    });
    $("#lancamentos-credito").attr("name","lancamentos-credito").attr("value",JSON.stringify(creditos));
}

$('.cb_credito').on('click',adicionarCredito);

function adicionarCredito(){
    if($(this).prop("checked")){
        var valor = parseFloat($('#valor_liquido').val());
        var credito = parseFloat($(this).parent().find('.valor_credito').val());
        var valorAbatido =Math.round((valor-credito)*100)/100;
        if(valorAbatido>0){
            $('#valor_liquido').val(valorAbatido);
            $('#valor_restante').val(valorAbatido);
            $('#label_valor_liquido').text(valorAbatido);
            $('#valor_pagar').val(valorAbatido);
        }else{
            alert('Não é possível abater um crédito maior que o valor a pagar');
        }
    }else{
        var valor = parseFloat($('#valor_liquido').val());
        var credito = parseFloat($(this).parent().find('.valor_credito').val());
        var valorAbatido =Math.round((valor+credito)*100)/100;
        if(valorAbatido>0){
            $('#valor_liquido').val(valorAbatido);
            $('#valor_restante').val(valorAbatido);
            $('#label_valor_liquido').text(valorAbatido);
            $('#valor_pagar').val(valorAbatido);
        }else{
            alert('Não é possível abater um crédito maior que o valor a pagar');
        }
    }
    somaCredito();
}

function somaCredito(){
    var credito = 0;
    $.each($(".cb_credito"),function(){
        if($(this).prop("checked")){
         credito = credito+parseFloat($('.valor_credito').val());
        }
    });
    $("#valor_credito").val(credito);
}