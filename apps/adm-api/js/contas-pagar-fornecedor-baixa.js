$('#form_contas_pagar').submit(adicionaDocumentos);

function adicionaDocumentos() {
    var lancamentos = [];
    $('.baixar').each(function () {
        if ($(this).prop("checked")) {
            var numero = $(this).parent().parent().find(".id_lanc").val();
            var lanc = {
                numero: numero
            }
            lancamentos.push(lanc);
        }
    });
    $('#lancamentos_selecionados').attr("name", "lancamentos_selecionados").attr("value", JSON.stringify(lancamentos));

    var defeitos = [];
    $('.abater_defeito').each(function () {
        if ($(this).prop("checked")) {
            var uuid = $(this).parent().parent().find(".uuid").val();
            var sequencia = $(this).parent().parent().find(".sequencia").val();
            var preco = $(this).parent().parent().find(".valor_defeito").val();
            var id_fornecedor = $(this).parent().parent().find(".id_fornecedor").val();
            var defeito = {
                uuid: uuid,
                sequencia: sequencia,
                preco:preco,
                id_fornecedor:id_fornecedor
            }
            defeitos.push(defeito);
        }
    });

    var cheques = [];
    $('.abater_cheque').each(function () {
        if ($(this).prop("checked")) {
            var cheque = $(this).parent().parent().find(".cheque").val();
            var cheque = {
                cheque: cheque
            }
            cheques.push(cheque);
        }
    });

    $('#lancamentos_selecionados').attr("value", JSON.stringify(lancamentos));
    $('#defeitos_selecionados').attr("value", JSON.stringify(defeitos));
    $('#cheques_selecionados').attr("value", JSON.stringify(cheques));
}

function downloadAll(){

    var button =document.getElementById('downloadAll')
    
    var all = document.getElementsByClassName('baixar')

    for (let i = 0; i < all.length; i++) {
        if(button.checked){
            all[i].checked = true;
        }else{
            all[i].checked = false;
        }
        
    }
}