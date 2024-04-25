var observacao = $("#observacoes");
observacao.on("blur",function(){
  var conteudo = observacao.val();
  var dados = {
    conteudo : conteudo
  };
  $.post("controle/pedido-concluido-salva-observacao.php", dados);
});
