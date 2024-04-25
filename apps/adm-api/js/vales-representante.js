$(document).ready(atualizarDesconto);

$("#considera_comissao").on("click", consideraComissao);

function consideraComissao(){
  if($(this).prop("checked")){
    $(".total_comissao").removeAttr("id");
    var valorTotal = 0;

      var valor = $("#valor_liquido").text();
      valor = parseFloat(valor.replace(',','.'));
      valorTotal = valorTotal+valor;

    var valorComissao = $("#comissao_selecionado").text();
    valorComissao = valorComissao.replace(',','.');
    var valorAbateComissao = parseFloat(valorTotal)-parseFloat(valorComissao);
    valorAbateComissao = parseFloat(valorAbateComissao);
    $("#valor_liquido").text(parseFloat(valorAbateComissao).toFixed(2).replace('.',','));
    $("#valor_pago").val(valorAbateComissao.toFixed(2));
  }else{
    $(".total_comissao").attr("id","noprint");
    var valorTotal = 0;
      var valor = $("#valor_liquido").text();
      valor = parseFloat(valor.replace(',','.'));
      var valorComissao = $("#comissao_selecionado").text();
      valorComissao = valorComissao.replace(',','.');
      valorTotal = parseFloat(valor)+parseFloat(valorComissao);

    $("#valor_liquido").text(parseFloat(valorTotal).toFixed(2).replace('.',','));
    $("#valor_pago").val(valorSelecionado.toFixed(2));
  }
}

$("#desconto").on("change", atualizarDesconto);

function atualizarDesconto(){
  var desconto = $("#desconto").val();
  if(desconto==''){
    desconto = 0;
  }
  var valor = 0;
  var totalComDesconto = 0;
  $(".baixar").each(function(){
    if($(this).prop("checked")){
      valor = valor+parseFloat($(this).parent().parent().find(".valor_lancamento").val());
    }
  });
  var perc = (desconto/valor)*100;
  $(".baixar").each(function(){
    if($(this).prop("checked")){
      var valor = parseFloat($(this).parent().parent().find(".valor_lancamento").val());
      var valorComDesconto = (valor-(valor*perc)/100);
      totalComDesconto = totalComDesconto+valorComDesconto;
      $(this).parent().parent().find(".valor_com_desconto").text(valorComDesconto.toFixed(2).replace('.',','));
    }
  });
  var valorComissao = $("#comissao_selecionado").text();
  valorComissao = valorComissao.replace(',','.');
  totalComDesconto = totalComDesconto - valorComissao;
  $("#valor_desconto").text(parseFloat(desconto).toFixed(2).replace('.',','));
  $("#valor_liquido").text(parseFloat(totalComDesconto).toFixed(2).replace('.',','));
  $("#valor_pago").val(totalComDesconto.toFixed(2));
}

$(".baixar").on("click",atualizaGuardar);

function atualizaGuardar() {
  event.preventDefault();
  var marcado = 0;
  var comissao = 0;

  if($(this).prop("checked")){
      marcado = 1;
    }else{
      marcado = 0;
    }
  var lancamento = $(this).parent().parent().find(".num_lancamento").val();
  var dados = {
    lancamento:lancamento,
    marcado:marcado
  };
  $.post("controle/vale-marca-baixa.php",dados);
    window.location.href = window.location.href;
}

  var pares=0;
  var valorComissao = 0;
  var valorSelecionado = 0;
  $(".baixar").each(function(){
    if($(this).prop("checked")){
      valorSelecionado = valorSelecionado+parseFloat($(this).parent().parent().find('.valor_lancamento').val());
      pares = pares+parseInt($(this).parent().parent().find('.pares').val());
      valorComissao = valorComissao+parseFloat($(this).parent().parent().find('.comissao_vale').val());
    }
  });

  $(document).ready(function(){
    $("#pares_selecionados").text(pares);
    $("#comissao_selecionado").text((pares*0.16).toFixed(2).replace('.',','));
    $("#valor_selecionado").text(valorSelecionado.toFixed(2).replace('.',','));
    $("#valor_pago").val(valorSelecionado.toFixed(2));

    $(".tipo_documento").each(function(){
      var documento = $(this).val();
      if(documento==2||documento==3||documento==4||documento==5){
        $(this).hide();
    }
  });
});
