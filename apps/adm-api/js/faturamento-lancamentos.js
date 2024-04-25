//armazena o campo de valor digitado
var valor_pagar = parseFloat($("#valor_pagar").val());
var num_cheque = $("#num_cheque").val();
var num_lancamento = parseInt($("#num_lancamento").val());

//armazena o total a ser acertado pela primeira vez
var valor_liquido = parseFloat($("#valor_liquido").val());
var valor_restante = parseFloat($("#valor_restante").val());
var valor_acertado = 0;
var sequencia = 0;

//armazena valor adicional da troca de tabela
var valor_adicional = 0;

//ao clicar em adicionar
$("#adicionar").on("click",adicionaLancamento);

//ao selecionar tabela de preço
$("#select_tabela_preco").on("change",alteraValorRestante);

//ao clicar no botão refazer
$("#refazer").on("click",function(){
  location.reload();
});

//função para habilitar o botão faturamento
function liberaFaturamento(){
  if(valor_restante==0){
    $("#faturar").prop("disabled", false);
    $("#adicionar").prop("disabled", true);
  }
}

function alteraValorRestante(){
  var tabela_preco_origem = $("#tabela_preco_origem").val();
  var tabela_preco = $("#select_tabela_preco").val();
  var credito = parseFloat($("#valor_credito").val());
  var valor_restante = parseFloat($("#valor_restante").val());
  if(tabela_preco_origem==2 && tabela_preco==1){
      //do atacado a vista para o atacado a prazo
      valor_adicional = ((13.63/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      somaAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==2 && tabela_preco==6){
      //do atacado a vista para o debito
      valor_adicional = ((4.59/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      somaAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==6 && tabela_preco==2){
      //do debito para o atacado a vista
      valor_adicional = ((4.35/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      subtraiAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==6 && tabela_preco==1){
      //do debito para o atacado a prazo
      valor_adicional = ((8.69/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      somaAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==1 && tabela_preco==6){
      //do atacado a prazo ao debito
      valor_adicional = ((8/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      subtraiAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==1 && tabela_preco==2){
      //do atacado a vista para o atacado a prazo
      valor_adicional = ((12/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      subtraiAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==4 && tabela_preco==3){
      //do varejo a vista para o varejo a prazo
      valor_adicional = ((11/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      somaAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else if(tabela_preco_origem==3 && tabela_preco==4){
      //do varejo a prazo para o varejo a vista
      valor_adicional = ((10/100)*valor_restante);
      valor_adicional = parseFloat(valor_adicional.toFixed(2));
      valor_restante = valor_restante + valor_adicional - credito;
      valor_restante = parseFloat(valor_restante.toFixed(2));
      subtraiAdicional(valor_adicional);
      preencheCamposDeValor(valor_restante);
  }else{
      preencheCamposDeValor(valor_restante);
  }
}

function preencheCamposDeValor(valor_restante){
  valor_restante = valor_restante.toFixed(2);
  $("#valor_pagar").val(valor_restante);
  $("#valor_restante").val(valor_restante);
}

function somaAdicional(valor_adicional, credito){
  var liquido = parseFloat($("#valor_liquido").val());
  var credito = parseFloat($("#valor_credito").val());
  $("#valor_liquido").val(liquido+valor_adicional-credito);
  $("#label_valor_liquido").text(liquido+valor_adicional-credito);
}

function subtraiAdicional(valor_adicional, credito){
  var liquido = parseFloat($("#valor_liquido").val());
  var credito = parseFloat($("#valor_credito").val());
  $("#valor_liquido").val(liquido-valor_adicional-credito);
  $("#label_valor_liquido").text(liquido-valor_adicional-credito);
}

function adicionaLancamento(){
    var valor_restante = parseFloat($("#valor_restante").val());
    var cliente = $("#cliente").val();
    var representante = $("#id_representante option:selected").text();
    var id_representante = $("#id_representante").val();
    var contaBancaria = $("#conta_bancaria").val();
    var nomeConta = $("#conta_bancaria option:selected").text();
    var freteiro = $("#id_freteiro").val();
    var nomeFreteiro = $("#id_freteiro option:selected").text();
    var documento = $("#tipo_documento").val();
    var forma_pagamento = $("#forma_pagamento").val();

    //pega valor digitado
    var valor_digitado = parseFloat($("#valor_pagar").val());

    //armazena valor restante
    var valor_liquido = $("#valor_liquido").val();
    
    function documentoEhValido() {
        if(documento>0){
          return true;
        }else{
          alert("Informe um documento válido");
        }
    }

    function documentoEscolhido(){
      if(documento==1){
        return "Dinheiro";
      }else if(documento==2){
        return "Vale";
      }else if(documento==3){
        return "Promissória";
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
      }else if(documento==9){
        return "Retirada após Pagamento";
      }
    }

    function buscaNomeTabela(){
      if(tabela_preco==1){
        return "Atac. Prazo";
      }else if(tabela_preco==2){
        return "Atac. Vista";
      }else if(tabela_preco==3){
        return "Varejo Prazo";
      }else if(tabela_preco==4){
        return "Varejo Vista";
      }else if(tabela_preco==5){
        return "Representação";
      }else if(tabela_preco==6){
        return "Débito";
      }
    }

    function formaPagamentoEhValido() {
        if(forma_pagamento>0){
          return true;
        }else{
          alert("Informe forma de pagamento válido");
        }
    }

    function valorPagarEhValido() {
        if(valor_digitado!=0 && valor_digitado<=valor_restante){
          return true;
        }else{
          alert("Informe um valor de pagamento diferente de 0");
          return false;
        }
    }

    function quantParcelas(){
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

    function quantDias(){
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

    function ehCheque(){
      if(documento==6){
        return true;
      }else{
        return false;
      }
    }

    function ehVale(){
      if(documento==2){
        return true;
      }else{
        return false;
      }
    }

    function ehDeposito(){
      if(documento==7){
        return true;
      }else{
        return false;
      }
    }

    function ehFreteiro(){
      if(documento==8){
        return true;
      }else{
        return false;
      }
    }

    var tipoCheque = ehCheque();
    var tipoVale = ehVale();
    var tipoDeposito = ehDeposito();
    var tipoFreteiro = ehFreteiro();

    function representanteEhValido(){
      if(id_representante==''&&documento==2){
        alert('Informe um valor de representante válido.');
        return false;
      }else{
        return true;
      }
    }

    function contaBancariaEhValida(){
      if(contaBancaria==''&&documento==7){
        alert('Informe um valor de conta bancária válido.');
        return false;
      }else{
        return true;
      }
    }

    function freteiroEhValido(){
      if(freteiro==''&&documento==8){
        alert('Informe um valor de freteiro válido.');
        return false;
      }else{
        return true;
      }
    }


    if(documentoEhValido() && formaPagamentoEhValido() && representanteEhValido() && valorPagarEhValido() && contaBancariaEhValida() && freteiroEhValido()){
      var parcelas = quantParcelas();
      var dias = quantDias();
      var tabela_preco = $("#select_tabela_preco").val();
      var select_tabela_preco = $("#select_tabela_preco");
      var txtDocumento = documentoEscolhido();
      var txtTabela = buscaNomeTabela();

      geraLinha(parcelas,dias,valor_digitado,cliente,representante,id_representante,txtDocumento,tipoCheque,tipoVale,
      txtTabela,contaBancaria,nomeConta,tipoDeposito,tipoFreteiro,freteiro,nomeFreteiro);
      select_tabela_preco.prop("disabled", false);

      if(valor_restante>valor_digitado){
          valor_restante=Math.round((valor_restante-valor_digitado)*100)/100;
      }else if(valor_restante==valor_digitado){
          valor_restante = 0;
          if(valor_restante<=0){
            $("#faturar").prop("disabled", false);
          }
      }
      valor_acertado = valor_acertado + valor_digitado;
      $("#valor_pagar").val(valor_restante);
      tabela_preco_origem = tabela_preco;
      liberaFaturamento();
    }
}

//função para gerar a linha do lançamento
function geraLinha(parcelas,dias,valor_digitado,cliente,representante,id_representante,txtDocumento,tipoCheque,
  tipoVale,txtTabela,contaBancaria,nomeConta,tipoDeposito,tipoFreteiro,freteiro,nomeFreteiro){

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

    var id_cliente = $("#id_cliente").val();
    var documento = $("#tipo_documento").val();
    var dataDoDia = dataDoDia();
    var diasVenc = parseInt(dias);

    var tabela_preco = $("#select_tabela_preco").val();
    var valor_parcela = parseFloat(valor_digitado)/parseInt(parcelas);
    valor_parcela = valor_parcela.toFixed(2);

    for(var i=0;i<parcelas;i++){
        var dataVencimento = buscaDataVencimento(diasVenc);
        var diasVenc = parseInt(diasVenc)+parseInt(dias);
        if(tipoCheque){
          num_cheque = parseInt(num_cheque)+1;
        }
        num_lancamento ++;
        sequencia++;
        var linha  = $("<div>").addClass("linha").addClass("row").addClass("corpo");

        var colunaEmissao = $("<div>").addClass("col-sm-1").text(dataDoDia);
        var colunaSequencia = $("<div>").addClass("col-sm-1").text(sequencia);
        var colunaNumeroDoc = $("<div>").addClass("col-sm-4").text(num_lancamento);
        var colunaNumeroLanc = $("<div>").addClass("col-sm-1").text(num_lancamento);
        if(tipoCheque){
          var colunaNumCheque = $("<div>").addClass("col-sm-1").text(num_cheque);
          var colunaTitularCheque = $("<div>").addClass("col-sm-3");
        }else if(tipoVale){
          var colunaRepresentante = $("<div>").addClass("col-sm-3").text(representante);
        }else if(tipoDeposito){
          var colunaContaBancaria = $("<div>").addClass("col-sm-3").text(nomeConta);
        }else if(tipoFreteiro){
          var colunaFreteiro = $("<div>").addClass("col-sm-3").text(nomeFreteiro);
        }
        var colunaTabela = $("<div>").addClass("col-sm-1").text(txtTabela);
        var colunaDocumento = $("<div>").addClass("col-sm-2").text(txtDocumento);
        var colunaVencimento = $("<div>").addClass("col-sm-2");
        var colunaValor = $("<div>").addClass("col-sm-1").text(valor_parcela);


        var campoEmissao = $("<input>").addClass("emissao").attr("type","hidden").attr("value",dataDoDia);
        var campoSequencia = $("<input>").addClass("sequencia").attr("type","hidden").attr("value",sequencia);
        var campoNumeroDoc = $("<input>").addClass("numero_doc").attr("type","hidden").attr("value",num_lancamento);
        var campoNumeroCheque = $("<input>").addClass("numero_cheque").attr("type","hidden").attr("value",num_cheque);
        var campoRecebidoDe = $("<input>").addClass("recebido_de").attr("type","hidden").attr("value",id_cliente);
        var campoTabela = $("<input>").addClass("tabela").attr("type","hidden").attr("value",tabela_preco);
        var campoDocumento = $("<input>").addClass("documento").attr("type","hidden").attr("value",documento);
        var campoValor = $("<input>").addClass("valor").attr("type","hidden").attr("value",valor_parcela);
        var campoVencimento = $("<input>").addClass("vencimento").attr("type","date_time").attr("value",dataVencimento).addClass("form-control");
        if(tipoCheque){
          var campoChequeTitular = $("<input>").addClass("titular").attr("type","text").attr("value",cliente).addClass("form-control");
        }else if(tipoVale){
          var campoIdRepresentante = $("<input>").addClass("id_representante").attr("type","hidden").attr("value",id_representante).addClass("form-control");
        }else if(tipoDeposito){
          var campoContaBancaria = $("<input>").addClass("conta_bancaria").attr("type","hidden").attr("value",contaBancaria).addClass("form-control");
        }else if(tipoFreteiro){
          var campoFreteiro = $("<input>").addClass("freteiro").attr("type","hidden").attr("value",freteiro).addClass("form-control");
        }
        colunaVencimento.append(campoVencimento);

        linha.append(colunaEmissao);
        linha.append(colunaSequencia);

        if(tipoCheque){    
            colunaTitularCheque.append(campoChequeTitular);
            linha.append(colunaNumCheque);
            linha.append(colunaTitularCheque);
            linha.append(campoNumeroCheque);
        }else if(tipoVale){
            linha.append(colunaNumeroLanc);
            linha.append(colunaRepresentante);
            linha.append(campoIdRepresentante);
        }else if(tipoDeposito){
            linha.append(colunaNumeroLanc);
            linha.append(colunaContaBancaria);
            linha.append(campoContaBancaria);
        }else if(tipoFreteiro){
            linha.append(colunaNumeroLanc);
            linha.append(colunaFreteiro);
            linha.append(campoFreteiro);
        }else{
            linha.append(colunaNumeroDoc);
        }

        linha.append(colunaTabela);
        linha.append(colunaDocumento);
        linha.append(colunaVencimento);
        linha.append(colunaValor);

        linha.append(campoRecebidoDe);
        linha.append(campoEmissao);
        linha.append(campoSequencia);
        linha.append(campoNumeroDoc);
        linha.append(campoTabela);
        linha.append(campoDocumento);
        linha.append(campoValor);

        $("#lancamentos").append(linha);
    }
}

$("#form-fechar-faturamento").submit(armazenarCampos);

function armazenarCampos() {

    var lancamentos = [];
        $('.linha').each(function(){
            var emissao = $(this).find(".emissao").val();
            var sequencia = $(this).find(".sequencia").val();
            var numero_doc = $(this).find(".numero_doc").val();

            if(($(this).find(".numero_cheque").val())>0){
              var num_cheque = $(this).find(".numero_cheque").val();
            }else{
              var num_cheque = 0;
            }

            if(($(this).find(".id_representante").val())>0){
              var id_representante = $(this).find(".id_representante").val();
            }else{
              var id_representante = 0;
            }

            if(($(this).find(".conta_bancaria").val())>0){
              var conta_bancaria = $(this).find(".conta_bancaria").val();
            }else{
              var conta_bancaria = 0;
            }

            if(($(this).find(".titular").val())>0){
              var titular = $(this).find(".titular").val();
            }else{
              var titular = "Lançamento";
            }

            if(($(this).find(".freteiro").val())>0){
              var freteiro = $(this).find(".freteiro").val();
            }else{
              var freteiro = 0;
            }

            var recebido_de = $(this).find(".recebido_de").val();
            var tabela = $(this).find(".tabela").val();
            var documento = $(this).find(".documento").val();
            var vencimento = $(this).find(".vencimento").val();
            var valor = $(this).find(".valor").val();
            var pares = $("#pares").val();

            var lancamento = {
              emissao:emissao,
              sequencia:sequencia,
              numero_doc:numero_doc,
              num_cheque:num_cheque,
              recebido_de:recebido_de,
              tabela:tabela,
              documento:documento,
              vencimento:vencimento,
              valor:valor,
              titular:titular,
              id_representante:id_representante,
              pares:pares,
              conta_bancaria:conta_bancaria,
              freteiro:freteiro
            };
          lancamentos.push(lancamento);
        });

        $("#lancamentos-faturamento").attr("name","lancamentos-faturamento").attr("value",JSON.stringify(lancamentos));
}
