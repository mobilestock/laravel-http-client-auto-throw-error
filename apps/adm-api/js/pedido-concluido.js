var vendidos = $("#vendidos").val();
var faturamentoAberto = $("#faturamento_aberto").val();

atualizaBotaoFinalizar();

function atualizaBotaoFinalizar(){
    var frete = $("#tipo_frete").val();
    if(vendidos>0 && frete<1){
        $("#finalizar").prop("disabled",true).text("PRESENCIAL, AJUSTE O FRETE").removeClass("btn btn-block btn-primary").addClass("btn btn-block btn-danger");
    }else if(faturamentoAberto>0&&frete==0){
        $("#finalizar").prop("disabled",true).text("FATURAMENTO EM ABERTO").addClass("btn btn-block btn-danger");
    }else{
        $("#finalizar").prop("disabled",false).text("FINALIZAR PEDIDO").addClass("btn btn-block btn-primary");;
    }
}

$("#tipo_frete").on("change",atualizaBotaoFinalizar);