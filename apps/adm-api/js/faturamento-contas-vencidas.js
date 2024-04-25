var valor_total = 0;
var valor_selecionado = 0;

$('.linha').each(function(){
  var valor = $(this).find(".valor").val();
  valor_total += parseFloat(valor);
});

valor_total = valor_total.toFixed(2);
$("#valor_total").text(valor_total);

$('.baixa').on("click",verificaValoresSelecionados);

function verificaValoresSelecionados(){
  valor_selecionado = 0;
  $('.linha').each(function(){
    var valor = $(this).find(".valor").val();
    if($(this).find(".baixa").prop("checked")==true){
      valor_selecionado += parseFloat(valor);
    }
  });
  valor_selecionado = valor_selecionado.toFixed(2);
  $("#valor_selecionado").text(valor_selecionado);
}
