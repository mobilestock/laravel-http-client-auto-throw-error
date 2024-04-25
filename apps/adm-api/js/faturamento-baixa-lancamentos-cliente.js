var sequencia = 1;
var parcelas = 1;
var dias = 1;
var num_cheque = $("#num_cheque").val();

var total_pagar = buscaValorAhPagar();
if(total_pagar==0){
    $("#adicionar").prop("disabled",true);
}

//ao selecionar lançamentos, armazenar valor total do lançamento
$(".cb_lancamento").on("change",atualizaTotalLancamentosSelecionados);

function atualizaTotalLancamentosSelecionados(){
    let valor = 0;
    $(".cb_lancamento").each(function(){
        if($(this).prop("checked")){
            let valorLancamento = $(this).parent().parent().find(".valor").val();
            valor = parseFloat(valor)+parseFloat(valorLancamento);
        }else{
            let valorOriginal = $(this).parent().parent().find(".valor").val();
            $(this).parent().parent().find(".valor_com_desconto").val(valorOriginal);
            $(this).parent().parent().find(".valor_com_desconto_texto").text(valorOriginal);
        }
    });
    $("#valor_lancamentos_selecionado").val(valor);
    $("#valor_selecionado").text(valor.toFixed(2).replace('.',','));

    let desconto = buscaValorDesconto();
    let valorLancamentosSelecionados = buscaValorLancamentosSelecionados();
    let percDesc = (desconto/valorLancamentosSelecionados)*100;

      valor = 0;
      $(".cb_lancamento").each(function(){
          if($(this).prop("checked")){
              let valorLancamento = $(this).parent().parent().find(".valor").val();
              if(desconto>0){
                valorLancamento = valorLancamento - ((valorLancamento*percDesc)/100);
              }
              valor = parseFloat(valor)+parseFloat(valorLancamento);
              $(this).parent().parent().find(".valor_com_desconto").val(valorLancamento);
              $(this).parent().parent().find(".valor_com_desconto_texto").text(valorLancamento);
          }
      });
    
    $("#valor_lancamentos_selecionado").val(valor);
    $("#valor_selecionado").text(valor.toFixed(2).replace('.',','));
    atualizaValorASerPago();
}

//ao selecionar creditos, armazenar valor total do credito
$(".cb_credito").on("change",atualizaTotalCreditosSelecionados);

function atualizaTotalCreditosSelecionados(){
    let credito = 0;
    $(".cb_credito").each(function(){
      if($(this).prop("checked")){
          credito = parseFloat(credito)+parseFloat($(this).parent().find(".valor_credito").val());
      }
  });
  $("#valor_credito_selecionado").val(credito);
  atualizaValorASerPago();
}

$("#desconto").on("change",atualizaTotalLancamentosSelecionados);

function buscaValorDesconto(){
    return $("#desconto").val();
}

function atualizaValorASerPago(){
    let valorLancamentosSelecionado = buscaValorLancamentosSelecionados();
    let valorCreditoSelecionado = buscaValorCreditoSelecionado();
    let valorAPagar = valorLancamentosSelecionado-valorCreditoSelecionado;
    atualizaValorInformado(valorAPagar);
    atualizaValorRestante(valorAPagar);
    atualizaValorAPagar(valorAPagar);
    if(valorAPagar>0){
      $("#adicionar").prop("disabled",false);
    }
    atualizaDocumento();
}

function atualizaValorRestante(valor){
    $("#valor_restante").val(valor.toFixed(2));
}

function atualizaValorAPagar(valor){
  $("#valor_a_pagar").val(valor.toFixed(2));
}

function buscaValorAhPagar(){
    return parseFloat($("#valor_a_pagar").val());
}

function buscaValorLancamentosSelecionados(){
    return parseFloat($("#valor_lancamentos_selecionado").val());
}

function buscaValorInformado(){
  return parseFloat($("#valor_informado").val());
}

function buscaValorCreditoSelecionado(){
    return $("#valor_credito_selecionado").val();
}

function atualizaValorInformado(valor){
    $("#valor_informado").val(valor.toFixed(2));
}

function atualizaDocumento(){
    let valor = $("#valor_informado").val();
    if(valor<0){
      $(".option_tipo_documento").each(function(){
        if($(this).val()!=12){
          $(this).hide();
        }else if($(this).val()==12){
          $(this).prop("selected",true);
          $(this).show();
        }
      });
    }else{
      $(".option_tipo_documento").each(function(){
        if($(this).val()==12){
          $(this).hide();
        }else if($(this).val()==1){
          $(this).prop("selected",true);
          $(this).show();
        }else{
          $(this).show();
        }
      });
    }
}

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

function buscaValorRestante(){
    return parseFloat($("#valor_restante").val());
}

function ehCheque(documento){
    if(documento==6){
        return true;
    }
}

function ehDeposito(documento){
    if(documento==7){
        return true;
    }
}

//adiciona documento de pagamento
$("#adicionar").on("click",adicionarDocumento);

function adicionarDocumento(){
    var valor_informado = buscaValorInformado();
    if( valor_informado < document.getElementById('valor_lancamentos_selecionado').value )
    {
      $.alert({
        title: 'Alerta!!',
        content: `O valor deve ser maior que ${document.getElementById('valor_lancamentos_selecionado').value}`,
      });
      return false
    }
    var documento = $("#tipo_documento").val();
    var txtDocumento = buscaNomeDocumento(documento);
    var valor_restante = buscaValorRestante();

    var valor_total_pago = parseFloat($("#valor_total_pago").val());
    var contaBancaria = $("#conta_bancaria").val();
    var nomeConta = $("#conta_bancaria option:selected").text();
    
    function contaBancariaEhValida(){
        if(contaBancaria==''&&documento==7){
            alert('Informe um valor de conta bancária válido.');
            return false;
        }else{
            return true;
        }
    }

    var tipoDeposito = false;
    tipoDeposito = ehDeposito(documento);

    if(documento>0 && contaBancariaEhValida()){

        if($("#tipo_documento").val()==4 || $("#tipo_documento").val()==5 || $("#tipo_documento").val()==6){
            var condPag = $("#cond_pagamento").val();
            parcelas = quantParcelas(condPag);
            dias = quantDias(condPag);
        }

        if(valor_informado<0){
            valor_informado *= -1;
        }

        gerarLinha(valor_informado,documento,txtDocumento,tipoDeposito,contaBancaria,nomeConta,parcelas,dias);
        if(valor_informado>=0){
            valor_total_pago = valor_total_pago + valor_informado;
        }else{
            valor_total_pago = valorLancamentosSelecionado;
        }
        $('#valor_total_pago').val(valor_total_pago);
        valor_restante = valor_restante-valor_informado;
        valor_restante = parseFloat(valor_restante);
        
    }
    verificaCreditoGerado();
    verificaValorPago();
    desabilitaCampos();

    if(valor_restante>0){
      $("#valor_restante").val(valor_restante);
      $("#valor_informado").val(valor_restante.toFixed(2));
    }else{
        $("#valor_restante").val(0);
        $("#valor_informado").val(0);
    }

    if($("#valor_restante").val()==0){
        $("#adicionar").prop("disabled", true);
    }else{
        $("#adicionar").prop("disabled", false);
    }
}

function verificaCreditoGerado(){
    let valor_a_pagar = buscaValorAhPagar();
    let valor_pago = buscaValorPago();
    let valor_informado = buscaValorInformado();
    let credito = 0;
    if(valor_informado<0){
      credito = valor_informado;
    }else if(valor_pago>valor_a_pagar){
      credito = parseFloat(valor_a_pagar)-parseFloat(valor_pago);
    }
    if(credito<0){
      credito *= -1;
    }
      $("#credito_gerado").val(credito);
}

function buscaValorPago(){
    return parseFloat($("#valor_total_pago").val());
}

function desabilitaCampos(){
    $(".cb_lancamento").prop("disabled",true);
    $(".cb_credito").prop("disabled",true);
    $("#desconto").prop("disabled",true);
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
    var creditos = [];

    $('.linha').each(function(){
      if($(this).find(".cb_lancamento").prop("checked")==true){
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

    $.each($(".cb_credito"),function(){
      if($(this).prop("checked")){
        var id_lanc = $(this).parent().find(".id_lanc").val();
        creditos = [{id_lanc:id_lanc}];
      }
    });

    $("#lancamentos_baixados").attr("name","lancamentos_baixados").attr("value",JSON.stringify(lancamentos));
    $("#documentos_lancamentos").attr("name","documentos_lancamentos").attr("value",JSON.stringify(documentos));
    $("#creditos_baixados").attr("name","creditos_baixados").attr("value",JSON.stringify(creditos));
  
}

function verificaValorPago(){
    if($("#valor_total_pago").val()>0){
        $("#salvar_documentos").prop("disabled", false);
    }else if($("#valor_total_pago").val()==0){
        $("#salvar_documentos").prop("disabled", true);
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

