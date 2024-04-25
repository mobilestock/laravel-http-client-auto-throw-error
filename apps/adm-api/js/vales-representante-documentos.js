function ehCheque(documento){
  if(documento==6){
    return true;
  }
}

function buscaNomeDocumento(documento){
  if(documento==1){
    return "Dinheiro";
  }else if(documento==6){
    return "Cheque";
  }else if(documento==7){
    return "Depósito";
  }
}

var valor_total_pago = 0;
var sequencia = 1;
var num_cheque = $("#num_cheque").val();

//adiciona documento de pagamento
$("#adicionar").on("click",adicionarDocumento);
function adicionarDocumento(){

  var valor_selecionado = $("#valor_liquido").text().replace(',','.');
  var valor_pago = parseFloat($("#valor_pago").val());
  var documento = $("#tipo_documento").val();
  var txtDocumento = buscaNomeDocumento(documento);
  var valorEhValido = parseFloat(valor_pago)<=valor_selecionado;

  function ehDeposito(){
    if(documento==7){
      return true;
    }
  }

  var contaBancaria = $("#conta_bancaria").val();
  var nomeConta = $("#conta_bancaria option:selected").text();
  
  var tipoDeposito = false;
  tipoDeposito = ehDeposito();

  function contaBancariaEhValida(){
    if(contaBancaria==''&&documento==7){
      alert('Informe um valor de conta bancária válido.');
      return false;
    }else{
      return true;
    }
  }

  var valorTotalEhValido = (valor_total_pago+valor_pago)<=valor_selecionado;
  if(valorEhValido && valor_pago>0 && documento>0 && valorTotalEhValido && contaBancariaEhValida()){

    gerarLinha(valor_pago,documento,txtDocumento,tipoDeposito,contaBancaria,nomeConta);

    valor_total_pago += valor_pago;
    valor_restante = valor_selecionado-valor_total_pago;
    valor_restante = valor_restante.toFixed(2);
    $("#valor_pago").val(valor_restante);
    if($("#valor_pago").val()==0){
      $("#salvar_documentos").prop("disabled", false);
      $("#adicionar").prop("disabled", true);
    }else{
      $("#salvar_documentos").prop("disabled", true);
      $("#adicionar").prop("disabled", false);
    }

  }else if(!valorEhValido || valor_pago<=0 || !valorTotalEhValido){
    alert("Informe um valor válido.");
  }else if(documento<=0){
    alert("Informe um documento válido.");
  }
}

//gera a linha do documento
function gerarLinha(valor_pago,documento,txtDocumento,tipoDeposito,contaBancaria,nomeConta){

  var tipoCheque = ehCheque(documento);
  if(tipoCheque){
    num_cheque = parseInt(num_cheque)+1;
  }

  var id_cliente = $("#id_cliente").val();
  var cliente = $("#cliente").val();
  valor_pago = parseFloat(valor_pago).toFixed(2);

  var linha  = $("<div>").addClass("linha_documento").addClass("row").addClass("corpo");

  var colunaSequencia = $("<div>").addClass("col-sm-1").text(sequencia);
  var colunaValor = $("<div>").addClass("col-sm-2").text("R$ "+valor_pago);
  var colunaDocumento =$("<div>").addClass("col-sm-2").text(txtDocumento);
  var colunaRemover = $("<div>").addClass("col-sm-1");

  var colunaVencimento = $("<div>").addClass("col-sm-2");
  var colunaNumCheque = $("<div>").addClass("col-sm-2").text(num_cheque);
  var colunaTitularCheque = $("<div>").addClass("col-sm-2");

  if(tipoDeposito){
    var colunaContaBancaria = $("<div>").addClass("col-sm-3").text(nomeConta);
  }

  var campoValor = $("<input>").addClass("valor").attr("type","hidden").attr("value",valor_pago);
  var campoDocumento = $("<input>").addClass("documento").attr("type","hidden").attr("value",documento);
  var campoSequencia = $("<input>").addClass("sequencia").attr("type","hidden").attr("value",sequencia);
  var campoNumeroCheque = $("<input>").addClass("numero_cheque").attr("type","hidden").attr("value",num_cheque);
  var campoRecebidoDe = $("<input>").addClass("recebido_de").attr("type","hidden").attr("value",id_cliente);
  var campoChequeTitular = $("<input>").addClass("titular").attr("type","text").attr("value",cliente).addClass("form-control");
  var campoVencimento = $("<input>").addClass("vencimento").attr("type","date").addClass("form-control");
  if(tipoDeposito){
    var campoContaBancaria = $("<input>").addClass("conta_bancaria").attr("type","hidden").attr("value",contaBancaria);
  }

  var link = $("<a>").addClass("botao-remover").addClass("btn").addClass("btn-danger").addClass("fa").addClass("fa-trash").attr("href","#");

  colunaRemover.append(link);
  colunaVencimento.append(campoVencimento);
  colunaTitularCheque.append(campoChequeTitular);

  linha.append(colunaSequencia);
  linha.append(colunaValor);
  linha.append(colunaDocumento);
  linha.append(colunaRemover);
  if(tipoCheque){
      colunaTitularCheque.append(campoChequeTitular);
      linha.append(colunaNumCheque);
      linha.append(colunaTitularCheque);
      linha.append(colunaVencimento);
      linha.append(campoNumeroCheque);
  }else if(tipoDeposito){
    colunaContaBancaria.append(campoContaBancaria);
    linha.append(colunaContaBancaria);
    linha.append(campoContaBancaria);
  }

  linha.append(campoValor);
  linha.append(campoDocumento);
  linha.append(campoSequencia);
  linha.append(campoRecebidoDe);

  $("#documentos_pagamento").append(linha);

  sequencia = parseInt(sequencia)+1;
}

//remove a linha de documento inserido
$("#documentos_pagamento").on("click", ".botao-remover", removeLinha);
function removeLinha(){
  event.preventDefault();

  //retorna valor apagado
  var valor_documento = $(this).parent().parent().find(".valor").val();
  valor_restante = parseFloat(valor_restante)+parseFloat(valor_documento);
  valor_total_pago -= parseFloat(valor_documento).toFixed(2);
  $('#valor_total_pago').val(parseFloat(valor_total_pago).toFixed(2));

  sequencia = parseInt(sequencia)-1;

  var linha_documento = $(this).parent().parent();
  linha_documento.fadeOut();
  setTimeout(function(){
    linha_documento.remove();
    $("#valor_pago").val(parseFloat(valor_restante).toFixed(2));
    if($("#valor_pago").val()==0){
      $("#salvar_documentos").prop("disabled", false);
      $("#adicionar").prop("disabled", true);
    }else{
      $("#salvar_documentos").prop("disabled", true);
      $("#adicionar").prop("disabled", false);
    }
  },1000);

}

$("#form-lancamentos-baixa").submit(armazenarCampos);

function armazenarCampos(){
  if(parseFloat(valor_selecionado)<parseFloat(valor_total_pago)){
    alert("O valor dos documentos emitidos é maior do que os lançamentos a receber. Verifique.");
  }else{
    var lancamentos = [];
    var documentos = [];
    $('.linha').each(function(){
          var numero = $(this).find(".num_lancamento").val();
          var valor = $(this).find(".valor_desconto_calculado").val();
          var lancamento = {
            numero:numero,
            valor:valor
          }
          lancamentos.push(lancamento);
    });

    $('.linha_documento').each(function(){
      var sequencia = $(this).find(".sequencia").val();
      var doc = $(this).find(".documento").val();
      var valor = $(this).find(".valor").val();
      var num_cheque = $(this).find(".numero_cheque").val();
      var titular = $(this).find(".titular").val();
      var recebido = $(".id_cliente").val();
      var vencimento = $(".vencimento").val();
      if(($(this).find(".conta_bancaria").val())>0){
        var conta_bancaria = $(this).find(".conta_bancaria").val();
      }else{
        var conta_bancaria = 0;
      }
      var documento = {
        sequencia:sequencia,
        documento:doc,
        valor:valor,
        num_cheque:num_cheque,
        titular:titular,
        recebido:recebido,
        vencimento:vencimento,
        conta_bancaria:conta_bancaria
      }
      documentos.push(documento);
    });

    $("#lancamentos_baixados").attr("name","lancamentos_baixados").attr("value",JSON.stringify(lancamentos));
    $("#documentos_lancamentos").attr("name","documentos_lancamentos").attr("value",JSON.stringify(documentos));
  }
}

$("#tipo_documento").on("change",verificaTipoDocumento);
function verificaTipoDocumento(){
  if($(this).val()==7){
    $("#contaBancaria").css("display","block");
  }else{
    $("#contaBancaria").css("display","none");
  }
}
