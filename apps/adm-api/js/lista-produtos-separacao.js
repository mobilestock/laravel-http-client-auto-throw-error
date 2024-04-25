$("#lista-produtos-separacao").on("click","#prioridade",mudarPrioridade);

function mudarPrioridade() {
  event.preventDefault();
  var sep = $(this).val();
  var dados = {
    id_sep:sep
  };
  $.post("controle/pedido-lista-separacao-alterar-prioridade.php",dados);
  window.location.href = window.location.href;
}

$("#lista-produtos-separacao").on("click",".cliente-aguardando",mudarClienteAguardando);

function mudarClienteAguardando() {
  event.preventDefault();
  var sep = $(this).parent().find(".id_sep").val();
  var dados = {
    id_sep:sep
  };
  $.post("controle/pedido-lista-separacao-cliente-aguardando.php",dados);
  window.location.href = window.location.href;
}

