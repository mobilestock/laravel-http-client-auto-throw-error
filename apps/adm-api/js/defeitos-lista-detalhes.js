$(".abater").on("click", verificaValorSelecionado);

function verificaValorSelecionado() {
  var valor = 0;
  var pares = 0;
  let defeitos = [];
  $(".abater").each(function () {
    if ($(this).prop("checked") == true) {
      valorSelecionado = $(this).parent().parent().find(".preco").val();
      valorSelecionado = parseFloat(valorSelecionado);
      valor += valorSelecionado;
      pares++;

      uuid = $(this).parent().parent().find(".uuid").val();
      id_cliente = $(this).parent().parent().find(".id_cliente").val();
      id_produto = $(this).parent().parent().find(".id_produto").val();
      sequencia = $(this).parent().parent().find(".sequencia").val();

      var defeito = {
        uuid: uuid,
        id_cliente: id_cliente,
        id_produto: id_produto,
        sequencia: sequencia,
      };

      defeitos.push(defeito);
    }
  });
  $("#defeitos").attr("value", JSON.stringify(defeitos));
  valor = valor.toFixed(2);
  $("#valor_selecionado").val(valor);
  $("#pares").val(pares);
  liberarBotaoRelatorio();
}

$("#nota_fiscal").on("blur", liberarBotaoRelatorio);

function liberarBotaoRelatorio() {
  if ($("#pares").val() > 0 && $("#nota_fiscal").val() > 0) {
    $("#relatorio_abatimento").prop("disabled", false);
  } else {
    $("#relatorio_abatimento").prop("disabled", true);
  }
}
