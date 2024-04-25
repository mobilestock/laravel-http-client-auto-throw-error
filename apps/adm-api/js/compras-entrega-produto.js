$(".tabela-detalhes").on("click",".botao-entrega-volume",realizaEntrega);

function realizaEntrega() {
  event.preventDefault();
  var compra = $(this).parent().parent().find(".compra").val();
  var sequencia = $(this).parent().parent().find(".sequencia").val();
  var volume = $(this).parent().parent().find(".volume").val();
  var valor_total = $(this).parent().parent().find(".valor_total").val();
  var pares = $(this).parent().parent().find(".pares").val();
  var fornecedor = $("#id_fornecedor").val();

  var dados = {
    compra:compra,
    sequencia:sequencia,
    volume:volume,
    fornecedor:fornecedor,
    valor_total: valor_total,
    pares: pares
  };

  $(".entrega-volume").attr("name","entrega-volume").attr("value",JSON.stringify(dados));

  var situacaoVolume = $(this).parent().parent().find(".situacao-volume");
  var situacaoProduto = $(".situacao-produto");
  var botaoEntregar = $(this);

  $.post("controle/compras-volume-entrega.php",dados,function(data) {
    situacaoVolume.fadeOut(200);
    botaoEntregar.fadeOut(200);
    setTimeout(function () {situacaoVolume.text("Entregue");},200);
    botaoEntregar.removeClass("btn-primary");
    botaoEntregar.addClass("btn-warning");
    botaoEntregar.attr("disabled","disabled");
    botaoEntregar.text("Entregue");
    situacaoVolume.fadeIn(200);
    botaoEntregar.fadeIn(200);
  }).done(function(data){
 		$('body').html(data);
  });

$(".botao-entrega-volume").on("click",function(){
  $(this).attr('disabled', true);
});
}
