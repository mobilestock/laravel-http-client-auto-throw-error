$("#adicionar").on("click",verificaValores);
$(".baixa").on("click",verificaValores);
$("#desconto").on("change",verificaValores);
$("#observacao").on("change",verificaValores);

function verificaValores(){
    var lancExcede = false;
    var valorTotal = 0;
    var valorPago = $("#valor_total_pago").val();
    valorPago = parseFloat(valorPago);
    $(".baixa").each(function(){
        if($(this).prop("checked")){
            var valorComDesc = $(this).parent().parent().find(".valor_com_desconto").val();
            valorComDesc = parseFloat(valorComDesc);
            valorTotal = parseFloat(valorTotal)+parseFloat(valorComDesc);
            var diferenca = valorPago-valorTotal;
            if(diferenca<0){
                diferenca = diferenca * -1;
            }
            if(valorPago<valorTotal && diferenca>valorComDesc && valorPago>0){
                $("#salvar_documentos").prop("disabled",true);
                lancExcede = true;
                verificaValoresSelecionados();
            }
        }
    });
    if(lancExcede==false){
        if(valorPago>valorTotal){
            if($("#desconto").val()>0){
                if($("#observacao").val()==""){
                    $("#salvar_documentos").prop("disabled",true);
                    alert("Informe observação quando existir desconto");
                }else{
                    $("#salvar_documentos").prop("disabled",false);
                }
            }else{
                $("#salvar_documentos").prop("disabled",false);
            }
        }else if(valorPago<=valorTotal && valorPago>0){
            if($("#desconto").val()>0){
                if($("#observacao").val()==""){
                    $("#salvar_documentos").prop("disabled",true);
                    alert("Informe observação quando existir desconto");
                }else{
                    $("#salvar_documentos").prop("disabled",false);
                }
            }else{
                $("#salvar_documentos").prop("disabled",false);
            }
        }
    }else if(lancExcede==true){
        $("#salvar_documentos").prop("disabled",true);
    }
}