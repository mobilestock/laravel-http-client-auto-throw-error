var valor_total = 0;
var valor_selecionado = 0;
var valor_total_pago = 0;
var valor_restante = 0;
var sequencia = 1;
var parcelas = 1;
var dias = 1;
var num_cheque = $("#num_cheque").val();

$(".cb_credito").on("click",adicionaCredito);

$(document).ready(function(){
  $(".option_tipo_documento").each(function(){
    var doc = $(this).val();
    if(doc==2||doc==3){
      $(this).hide();
    }
  });
});

$("#tipo_documento").on("change",verificaTipoDocumento);
function verificaTipoDocumento(){
  if($(this).val()==4 || $(this).val()==5 || $(this).val()==6){
    $("#contaBancaria").css("display","none");
    $("#condPagamento").css("display","block");
  }else if($(this).val()==7){
    $("#contaBancaria").css("display","block");
    $("#condPagamento").css("display","none");
  }else{
    $("#contaBancaria").css("display","none");
    $("#condPagamento").css("display","none");
  }
}

//busca valor total selecionado
$('.baixa').on("click",verificaValoresSelecionados);
$('#desconto').on("change",verificaValoresSelecionados);

function adicionaCredito(){
  var credito = 0;
  $.each($(".cb_credito"),function(){
      if($(this).prop("checked")){
       credito = credito+parseFloat($('.valor_credito').val());
      }
  });
  $("#valor_credito_selecionado").val(credito);
  verificaValoresSelecionados();
}

function verificaValoresSelecionados(){
  var desconto = $("#desconto").val();
  var credito = $("#valor_credito_selecionado").val();
  var valor = 0;
  var totalComDesconto = 0;
  valor_selecionado = 0;

  $(".baixa").each(function(){
    if($(this).prop("checked")){
      valor = valor+parseFloat($(this).parent().parent().find(".valor").val());
    }
  });
  var perc = (desconto/valor)*100;

  $(".baixa").each(function(){
    if($(this).prop("checked")){
      var valor = parseFloat($(this).parent().parent().find(".valor").val());
      var valorComDesconto = (valor-(valor*perc)/100);
      valor_selecionado += parseFloat(valorComDesconto);
      totalComDesconto = totalComDesconto+valorComDesconto;
      $(this).parent().parent().find(".valor_com_desconto").val(valorComDesconto.toFixed(2).replace(',','.'));
      $(this).parent().parent().find(".valor_com_desconto_texto").text(valorComDesconto.toFixed(2).replace('.',','));
    }else{
      var valor = parseFloat($(this).parent().parent().find(".valor").val());
      var valorComDesconto = parseFloat($(this).parent().parent().find(".valor").val());
      totalComDesconto = totalComDesconto+valorComDesconto;
      $(this).parent().parent().find(".valor_com_desconto").val(valorComDesconto.toFixed(2).replace(',','.'));
      $(this).parent().parent().find(".valor_com_desconto_texto").text(valorComDesconto.toFixed(2).replace('.',','));
    }
  });

  valor_selecionado = valor_selecionado.toFixed(2);
  $("#valor_selecionado").text(valor_selecionado);
  var valor_selecionado_temp = valor_selecionado-valor_total_pago-credito;
  valor_selecionado_temp = valor_selecionado_temp.toFixed(2);
  $("#valor_pago").val(parseFloat(valor_selecionado_temp));
  $("#valor_desconto").text(parseFloat(desconto).toFixed(2).replace('.',','));
}

function ehCheque(documento){
  if(documento==6){
    return true;
  }
}

function buscaNomeDocumento(documento){
  if(documento==1){
    return "Dinheiro";
  }else if(documento==2){
    return "Vale";
  }else if(documento==3){
    return "Promissoria";
  }else if(documento==4){
    return "Cartão Débito";
  }else if(documento==5){
    return "Cartão Crédito";
  }else if(documento==6){
    return "Cheque";
  }else if(documento==7){
    return "Depósito";
  }else if(documento==8){
    return "Pagamento Freteiro";
  }else if(documento==11){
    return "Comissao";
  }
}

//adiciona documento de pagamento
$("#adicionar").on("click",adicionarDocumento);
function adicionarDocumento(){
  var valor_pago = $("#valor_pago").val();
  valor_pago = parseFloat(valor_pago);
  var documento = $("#tipo_documento").val();
  var txtDocumento = buscaNomeDocumento(documento);
  valor_selecionado = parseFloat(valor_selecionado);
  valor_somado = valor_total_pago+valor_restante;
  var contaBancaria = $("#conta_bancaria").val();
  var nomeConta = $("#conta_bancaria option:selected").text();
  
  function ehDeposito(){
    if(documento==7){
      return true;
    }
  }

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

  if(valor_pago>0 && documento>0 && contaBancariaEhValida() ){
    if($("#tipo_documento").val()==4 || $("#tipo_documento").val()==5 || $("#tipo_documento").val()==6){
      var condPag = $("#cond_pagamento").val();
      parcelas = quantParcelas(condPag);
      dias = quantDias(condPag);
    }
    gerarLinha(valor_pago,documento,txtDocumento,tipoDeposito,contaBancaria,nomeConta,parcelas,dias);
    valor_total_pago = valor_total_pago + valor_pago;
    $('#valor_total_pago').val(valor_total_pago);
    var credito = $("#valor_credito_selecionado").val();
    valor_restante = valor_selecionado-valor_total_pago-credito;
    valor_restante = parseFloat(valor_restante).toFixed(2);
    if(valor_restante>0){
      $("#valor_informado").val(valor_restante);
    }else{
      $("#valor_informado").val(0);
    }

  verificaValorPago();
}

//gera a linha do documento
function gerarLinha(valor_pago,documento,txtDocumento,tipoDeposito,contaBancaria,nomeConta,parcelas,dias){

  //função para gerar a data no formato correto
  function dataDoDia(){
    var data = new Date(),
      dia = data.getDate(),
      ano = data.getFullYear();
      var mes = new Array();
      mes[0] = "01";
      mes[1] = "02";
      mes[2] = "03";
      mes[3] = "04";
      mes[4] = "05";
      mes[5] = "06";
      mes[6] = "07";
      mes[7] = "08";
      mes[8] = "09";
      mes[9] = "10";
      mes[10] = "11";
      mes[11] = "12";

    return [dia, mes[data.getMonth()], ano].join('/');
  }

  function buscaDataVencimento(d){
    var data = new Date();
    data.setDate(data.getDate()+d);
    var dia = data.getDate();
    var ano = data.getFullYear();
    var mes = new Array();
      mes[0] = "01";
      mes[1] = "02";
      mes[2] = "03";
      mes[3] = "04";
      mes[4] = "05";
      mes[5] = "06";
      mes[6] = "07";
      mes[7] = "08";
      mes[8] = "09";
      mes[9] = "10";
      mes[10] = "11";
      mes[11] = "12";
    return [dia, mes[data.getMonth()], ano].join('/');
  }

  var dataDoDia = dataDoDia();
  var diasVenc = parseInt(dias);

  var valor_parcela = parseFloat(valor_pago)/parseInt(parcelas);
  valor_parcela = valor_parcela.toFixed(2);

  var id_cliente = $("#id_cliente").val();
  var cliente = $("#cliente").val();

  for(var i=0;i<parcelas;i++){
    var dataVencimento = buscaDataVencimento(diasVenc);
    var diasVenc = parseInt(diasVenc)+parseInt(dias);

    var tipoCheque = ehCheque(documento);
    if(tipoCheque){
      num_cheque = parseInt(num_cheque)+1;
    }

      var linha  = $("<div>").addClass("linha_documento").addClass("row").addClass("corpo");

      var colunaSequencia = $("<div>").addClass("col-sm-1").text(sequencia);
      var colunaValor = $("<div>").addClass("col-sm-2").text("R$ "+valor_parcela);
      var colunaDocumento =$("<div>").addClass("col-sm-2").text(txtDocumento);

      var colunaVencimento = $("<div>").addClass("col-sm-2");
      var colunaNumCheque = $("<div>").addClass("col-sm-1").text(num_cheque);
      var colunaTitularCheque = $("<div>").addClass("col-sm-4");

      if(tipoDeposito){
        var colunaContaBancaria = $("<div>").addClass("col-sm-3").text(nomeConta);
      }

      var campoValor = $("<input>").addClass("valor").attr("type","hidden").attr("value",valor_parcela);
      var campoDocumento = $("<input>").addClass("documento").attr("type","hidden").attr("value",documento);
      var campoSequencia = $("<input>").addClass("sequencia").attr("type","hidden").attr("value",sequencia);
      var campoNumeroCheque = $("<input>").addClass("numero_cheque").attr("type","hidden").attr("value",num_cheque);
      var campoRecebidoDe = $("<input>").addClass("recebido_de").attr("type","hidden").attr("value",id_cliente);
      var campoChequeTitular = $("<input>").addClass("titular").attr("type","text").attr("value",cliente).addClass("form-control");
      var campoVencimento = $("<input>").addClass("vencimento").attr("type","date_time").attr("value",dataVencimento).addClass("form-control");
      if(tipoDeposito){
        var campoContaBancaria = $("<input>").addClass("conta_bancaria").attr("type","hidden").attr("value",contaBancaria);
      }

      colunaVencimento.append(campoVencimento);

      linha.append(colunaSequencia);
      linha.append(colunaValor);
      linha.append(colunaDocumento);
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
}

$("#form-lancamentos-baixa-lote").submit(armazenarCampos);

function armazenarCampos(){

    var lancamentos = [];
    var documentos = [];
    $('.linha').each(function(){
      if($(this).find(".baixa").prop("checked")==true){
          var numero = $(this).find(".numero_lancamento").val();
          var sequencia = $(this).find(".sequencia").val();
          var valor = $(this).find(".valor").val();
          var valor_com_desconto = $(this).find(".valor_com_desconto").val();

          var lancamento = {
            numero:numero,
            sequencia:sequencia,
            valor:valor,
            valor_com_desconto:valor_com_desconto,
          }
          lancamentos.push(lancamento);
      }
    });

    $('.linha_documento').each(function(){
      var sequencia = $(this).find(".sequencia").val();
      var doc = $(this).find(".documento").val();
      var valor = $(this).find(".valor").val();
      var num_cheque = $(this).find(".numero_cheque").val();
      var titular = $(this).find(".titular").val();
      var recebido = $(".recebido_de").val();
      var vencimento = $(this).find(".vencimento").val();
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
    
    var creditos = [];
    $.each($(".cb_credito"),function(){
        if($(this).prop("checked")){
          var pedido_origem = $(this).parent().find(".pedido_origem").val();
          creditos = [{pedido_origem:pedido_origem}];
        }
    });
    $("#creditos_baixados").attr("name","creditos_baixados").attr("value",JSON.stringify(creditos));
  
}

function verificaValorPago(){
    let valor_restante = buscaValorRestante();
    if(valor_restante<=0){
      $("#adicionar").prop("disabled",true);
      $("#salvar_documentos").prop("disabled",false);
    }else{
      $("#adicionar").prop("disabled",false);
      $("#salvar_documentos").prop("disabled",true);
    }
}

function quantParcelas(forma_pagamento){
  if(forma_pagamento==1 || forma_pagamento==2 || forma_pagamento==5 || forma_pagamento==7 || forma_pagamento==13 
    || forma_pagamento==14 || forma_pagamento==15 || forma_pagamento==16){
    return 1;
  }else if(forma_pagamento==3 || forma_pagamento==8){
    return 2;
  }else if(forma_pagamento==4 || forma_pagamento==9){
    return 3;
  }else if(forma_pagamento==6 || forma_pagamento==10){
    return 4;
  }else if(forma_pagamento==11){
    return 5;
  }else if(forma_pagamento==12){
    return 6;
  }
}

function quantDias(forma_pagamento){
  if(forma_pagamento==1){
    return 0;
  }else if(forma_pagamento==2 || forma_pagamento==3 || forma_pagamento==4 || forma_pagamento==6 ||
  forma_pagamento==7 || forma_pagamento==8 ||forma_pagamento==9 ||forma_pagamento==10 ||forma_pagamento==11 ||forma_pagamento==12){
    return 30;
  }else if(forma_pagamento==5){
    return 20;
  }else if(forma_pagamento==13){
    return 1;
  }else if(forma_pagamento==14){
    return 60;
  }else if(forma_pagamento==15){
    return 2;
  }else if(forma_pagamento==16){
    return 3;
  }
}

$("#adicionar").on("click",verificaValores);
$(".baixa").on("click",verificaValores);
$("#desconto").on("change",verificaValores);
$("#observacao").on("change",verificaValores);

function verificaValores(){
    var lancExcede = false;
    var valorTotal = 0;
    var valorPago = $("#valor_total_pago").val();
    valorPago = parseFloat(valorPago);
    $(".baixa").each(function(){
        if($(this).prop("checked")){
            var valorComDesc = $(this).parent().parent().find(".valor_com_desconto").val();
            valorComDesc = parseFloat(valorComDesc);
            valorTotal = parseFloat(valorTotal)+parseFloat(valorComDesc);
            var diferenca = valorPago-valorTotal;
            if(diferenca<0){
                diferenca = diferenca * -1;
            }
            if(valorPago<valorTotal && diferenca>valorComDesc && valorPago>0){
                $("#salvar_documentos").prop("disabled",true);
                lancExcede = true;
                verificaValoresSelecionados();
            }
        }
    });
    if(lancExcede==false){
        if(valorPago>valorTotal){
            if($("#desconto").val()>0){
                if($("#observacao").val()==""){
                    $("#salvar_documentos").prop("disabled",true);
                    alert("Informe observação quando existir desconto");
                }else{
                    $("#salvar_documentos").prop("disabled",false);
                }
            }else{
                $("#salvar_documentos").prop("disabled",false);
            }
        }else if(valorPago<=valorTotal && valorPago>0){
            if($("#desconto").val()>0){
                if($("#observacao").val()==""){
                    $("#salvar_documentos").prop("disabled",true);
                    alert("Informe observação quando existir desconto");
                }else{
                    $("#salvar_documentos").prop("disabled",false);
                }
            }else{
                $("#salvar_documentos").prop("disabled",false);
            }
        }
    }else if(lancExcede==true){
        $("#salvar_documentos").prop("disabled",true);
    }
}