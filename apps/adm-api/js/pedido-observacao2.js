var observacao2 = $("#observacao2");
observacao2.on("blur",function(event){
  var conteudo = observacao2.val();
  var dados = {
    conteudo : conteudo
  };
  $.post("controle/pedido-concluido-salva-observacao-conferencia.php", dados);
});
