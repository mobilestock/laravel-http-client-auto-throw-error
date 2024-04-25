$("#lista-reposicao").on("click",".prioridade",mudarPrioridade);

function mudarPrioridade() {
  event.preventDefault();
  var id = $(this).val();
  var dados = {
    id: id
  };
  $.post("controle/reposicao-estoque-lista-alterar-prioridade.php",dados);
  window.location.href = window.location.href;
}