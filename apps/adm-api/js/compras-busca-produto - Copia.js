$(document).ready(function(){
  $(".total").each(function(){
    if($(this).val()<0){
      $(this).parent().parent().addClass('fundo-vermelho');
    }else if($(this).val()==0){
      $(this).parent().parent().addClass('fundo-amarelo');
    }
  });
});

var arrayEstoque = JSON.parse($("#estoque").val());
var arrayPrevisao = JSON.parse($("#previsao").val());
var arrayReservado = JSON.parse($("#reservado").val());

$(".saldo").each(function(){
    var id_produto = $(this).parent().find(".id_produto").text();
    var estoqueFiltrado = $.grep(arrayEstoque,function(e){
      return e.id_produto.indexOf(id_produto);
    },true);
    var previsaoFiltrado = $.grep(arrayPrevisao,function(e){
      if(e.id_produto!=null){
        return e.id_produto.indexOf(id_produto);
      }
      return false;
    },true);
    var reservadoFiltrado = $.grep(arrayReservado,function(e){
      if(e.id_produto!=null){
        return e.id_produto.indexOf(id_produto);
      }
    },true);
    
});