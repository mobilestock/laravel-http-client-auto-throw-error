let elementListaContas = document.querySelector("#contas-deposito");
var contaSelecionada = '';

let listaContas = [
    {conta:'Pagamento em dinheiro',img:'images/money.svg',nome:'Dinheiro',banco:'',agencia:'',cc:'',cpf:'',titular:'Pagamento presencial'}
  ]

let html = listaContas.map((linha)=>{
  return linhaConta(linha);
}).join('');

function linhaConta(linha){
  return `
  <div class="col-sm-6 card-banco" onclick="atualizaContaBancaria('${linha.conta}')">
    <input type="hidden" class="conta-bancaria">
    <div class="d-flex linha-conta">
      <div class="p-2 m-1 d-flex align-content-center">
        <img src="${linha.img}" alt="${linha.nome}" width="40px" class="pl-1 pr-0">
      </div>
      <div class="p-10 m-1 d-flex flex-column justify-content-center">
        <div><b>${linha.nome}</b></div>
        <div>Banco: <b>${linha.banco}</b> - Agência: <b>${linha.agencia}</b> ${verificaOperacao(linha.op)}</div>
        <div>Conta: <b>${linha.cc}</b> - CPF: <b>${linha.cpf}</b></div>
        <div>Titular: <b>${linha.titular}</b></div>
      </div>
    </div>
  </div>
  `;
}

function verificaOperacao(op)
{
  return op>0?`Op: <b>${op}</b>`:' ';
}

elementListaContas.innerHTML = html;

function atualizaContaBancaria(conta){
  contaSelecionada = conta;
  habilitaConfirmar();
}

$(".linha-conta").on("click", function () {
  let card = $(this);
  let contas = $("#contas-deposito");
  $(".linha-conta").each(function () {
    $(this).css("background", "");
  });
  card.css("background", "#FFEFD5");
  $("#conta-bancaria").val(contas.find(".conta-bancaria").val());

  $("#btn-confirmar-pagamento").removeClass("btn-danger");
  $("#btn-confirmar-pagamento")
    .addClass("btn-primary")
    .text("Confirmar");

});
