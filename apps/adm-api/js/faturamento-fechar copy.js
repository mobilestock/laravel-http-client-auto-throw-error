//ao clicar em adicionar executar função para adicionar lancamento
$("#adicionar").on("click",adicionaLancamento);

//ao cliar no crédito executar a função de abater o crédito
$(".cb_credito").on('click',adicionarCredito);

//ao selecionar tabela de preço altera o valor restante
$("#select_tabela_preco").on("change",alteraValorRestante);

//ao clicar no botão refazer
$("#refazer").on("click",function(){
    location.reload();
});

$('#faturar').on('click',verificaCredito);


verificaSeEhCredito();

function verificaSeEhCredito(){
    var valor_liquido = $('#valor_liquido').val();
    $("#valor_restante").val(valor_liquido);
    if(valor_liquido<0){
      $('#valor_informado').prop('disabled',true);
      //$('#valor_credito').val(Math.round((valor_liquido)*100)/100);
    }else if(valor_liquido==0){
      $('#adicionar').prop('disabled',true);
    }
    if(valor_liquido!=0){
      $('#faturar').prop('disabled',true);
    }
}

var credito_pagamento = 0;

var sequencia = 0;
var num_cheque = $("#num_cheque").val();
var num_lancamento = parseInt($("#num_lancamento").val());
var valorPago = 0;

//função para habilitar o botão faturamento
function liberaFaturamento(){
    //let valor = $("#valor_restante").val();
    //if(valor==0){
        $("#faturar").prop("disabled",false);
    //}
}

//função que altera o valor restante de acordo com a tabela de preços
function alteraValorRestante(){
    var tabela_preco_origem = $("#tabela_preco_origem").val();
    var tabela_preco = $("#select_tabela_preco").val();
    var valor_restante = parseFloat($("#valor_restante").val());
    $("#tabela_preco_origem").val(tabela_preco);
    if(tabela_preco_origem==2 && tabela_preco==1){
        //do atacado a vista para o atacado a prazo
        valor_adicional = ((13.63/100)*valor_restante);
        somaAdicional(valor_adicional);
    }else if(tabela_preco_origem==2 && tabela_preco==6){
        //do atacado a vista para o debito
        valor_adicional = ((4.59/100)*valor_restante);
        somaAdicional(valor_adicional);
    }else if(tabela_preco_origem==6 && tabela_preco==2){
        //do debito para o atacado a vista
        valor_adicional = ((4.35/100)*valor_restante);
        subtraiAdicional(valor_adicional);
    }else if(tabela_preco_origem==6 && tabela_preco==1){
        //do debito para o atacado a prazo
        valor_adicional = ((8.69/100)*valor_restante);
        somaAdicional(valor_adicional);
    }else if(tabela_preco_origem==1 && tabela_preco==6){
        //do atacado a prazo ao debito
        valor_adicional = ((8/100)*valor_restante);
        subtraiAdicional(valor_adicional);
    }else if(tabela_preco_origem==1 && tabela_preco==2){
        //do atacado a vista para o atacado a prazo
        valor_adicional = ((12/100)*valor_restante);
        subtraiAdicional(valor_adicional);
    }else if(tabela_preco_origem==4 && tabela_preco==3){
        //do varejo a vista para o varejo a prazo
        valor_adicional = ((11/100)*valor_restante);
        somaAdicional(valor_adicional);
    }else if(tabela_preco_origem==3 && tabela_preco==4){
        //do varejo a prazo para o varejo a vista
        valor_adicional = ((10/100)*valor_restante);
        subtraiAdicional(valor_adicional);
    }
}

//função que soma valor de alteração da tabela
function somaAdicional(valor_adicional){
    let valor = parseFloat($("#valor_restante").val());
    $("#valor_restante").val(Math.round((valor+valor_adicional)*100)/100);
    $("#valor_informado").val(Math.round((valor+valor_adicional)*100)/100);
    $("#label_valor_restante").text(Math.round((valor+valor_adicional)*100)/100);
}

//função que subtrai o valor de alteração da tabela
function subtraiAdicional(valor_adicional){
    var valor = parseFloat($("#valor_restante").val());
    $("#valor_restante").val(Math.round((valor-valor_adicional)*100)/100);
    $("#valor_informado").val(Math.round((valor-valor_adicional)*100)/100);
    $("#label_valor_restante").text(Math.round((valor-valor_adicional)*100)/100);
}

//função de adicionar crédito
function adicionarCredito(){
    if($(this).prop("checked")){
        var valor_liquido = parseFloat($("#valor_liquido").val());
        var valor_pago = parseFloat($("#valor_pago").val());
        let valor = valor_liquido-valor_pago;
        let credito = $(this).parent().find('.valor_credito').val();
        var valorAbatido =Math.round((valor-credito)*100)/100;
            $('#valor_restante').val(valorAbatido);
            $('#valor_informado').val(valorAbatido);
            if($('#valor_restante').val()==0){
                $('#adicionar').prop("disabled",true);
                $('#faturar').prop("disabled",false);
            }
        
    }else{
        var valor = parseFloat($('#valor_restante').val());
        var credito = parseFloat($(this).parent().find('.valor_credito').val());
        var valorAbatido =Math.round((valor+credito)*100)/100;
        if(valorAbatido>0){
            $('#valor_restante').val(valorAbatido);
            $('#label_valor_liquido').text(valorAbatido);
            $('#valor_informado').val(valorAbatido);
        }else{
            alert('Não é possível abater um crédito maior que o valor a pagar');
        }
    }
    $("#label_valor_restante").text(valorRestante);
    somaCredito();
}

//funcao que soma o crédito
function somaCredito(){
    var credito = 0;
    $.each($(".cb_credito"),function(){
        if($(this).prop("checked")){
         credito = credito+parseFloat($('.valor_credito').val());
        }
    });
    $("#credito_aproveitado").val(credito);
}

//função que verifica créditos marcados e armazena
function verificaCredito(){
    var valor_liquido = $('#valor_liquido').val();
    var confirmar = true;
    if(valor_liquido==0){
      event.preventDefault();
      var r = confirm("O valor do pedido está zerado. Deseja continuar?");
      if(r==true){
          confirmar=true;
      } else if(r==false){
        confirmar=false;
      }
    }
    

    if(confirmar==true){
      var creditos = [];
      $.each($(".cb_credito"),function(){
          if($(this).prop("checked")){
            var pedido_origem = $(this).parent().find(".pedido_origem").val();
            var pedido_destino = $(this).parent().find(".pedido_destino").val();
            creditos = [{pedido_origem:pedido_origem,pedido_destino:pedido_destino}];
          }
      });
      $("#lancamentos-credito").attr("name","lancamentos-credito").attr("value",JSON.stringify(creditos));
      $("#form-fechar-faturamento").submit();
    }
}

//função que adiciona o lançamento
function adicionaLancamento(){

    $("#faturar").prop("disabled", false);

    //armazena o tipo do documento escolhido
    let documento = parseInt($("#tipo_documento").val());
    let cliente = $("#cliente").val();
    let forma_pagamento = $("#forma_pagamento").val();
    let parcelas = quantParcelas(forma_pagamento);
    let dias = quantDias(forma_pagamento);
    let txtDocumento = documentoEscolhido(documento);
    let tabela_preco = parseInt($("#select_tabela_preco").val());
    let txtTabela = buscaNomeTabela(tabela_preco);

    let tipoCheque = verificaTipoDocumento(documento,6);

    let tipoVale = verificaTipoDocumento(documento,2);
    let representante = $("#id_representante option:selected").text();
    let id_representante = $("#id_representante").val();

    let tipoDeposito = verificaTipoDocumento(documento,7);
    let contaBancaria = $("#conta_bancaria").val();
    let nomeConta = $("#conta_bancaria option:selected").text();

    let tipoFreteiro = verificaTipoDocumento(documento,8);
    let freteiro = $("#id_freteiro").val();
    let nomeFreteiro = $("#id_freteiro option:selected").text();

    //armazena valor restante a ser acertado em variavel global
    //let valorRestante = parseFloat($("#valor_restante").val());

    //armazena valor informado
    //let valorInformado = parseFloat($("#valor_informado").val());

    //armazena valor liquido
    let valorLiquido = parseFloat($("#valor_liquido").val());

    //verifica se documento é válido
    let documentoEhValido = documento>0??alert('Informe Um documento válido.');

    //verifica se forma de pagamento é válida
    let formaPagamentoEhValido = verificaFormaDePagamento(forma_pagamento);

    //verifica se é vale e se o representante está preenchido
    let representanteEhValido = verificaRepresentante(id_representante,documento);

    //verifica se é deposito e se conta bancaria esta preenchido
    let contaBancariaEhValida = verificaContaBancaria(conta_bancaria,documento);

    //verifia se freteiro está preenchido
    let freteiroEhValido = verificaFreteiro(freteiro,documento);

    //se valor informado é valido então adiciona a linha do lançamento
    if(documentoEhValido&&formaPagamentoEhValido&&representanteEhValido&&contaBancariaEhValida&& freteiroEhValido){

        $("#valor_informado").prop("disabled",false);

        geraLinha(documento,tabela_preco,parcelas,dias,valorInformado,cliente,representante,id_representante,txtDocumento,tipoCheque,tipoVale,
                  txtTabela,contaBancaria,nomeConta,tipoDeposito,tipoFreteiro,freteiro,nomeFreteiro);
        $("#select_tabela_preco").prop("disabled", false);
        
        valorPago += valorInformado;
        $("#valor_pago").val(valorPago);

        preencheValorRestante();
        preencherValorPagar();
        preencheCredito();
        $("#label_valor_restante").text(valorRestante);
      }
    liberaFaturamento();
}

function preencheValorRestante(){
    var credito = $("#credito_aproveitado").val();
    var valor_pagar = $("#valor_restante").val()-credito;
    var valor_pago = $("#valor_pago").val();
    var valor_restante = valor_pagar - valor_pago;
    $("#valor_restante").val(Math.round((parseFloat(valor_restante))*100)/100);
}

function preencherValorPagar(){
    var credito = $("#credito_aproveitado").val();
    var valor_pagar = $("#valor_liquido").val()-credito;
    var valor_pago = $("#valor_pago").val();
    if(parseFloat(valor_pago)>=parseFloat(valor_pagar)){
      $('#valor_informado').val(0);
      $('#valor_informado').prop('disabled',true);
      $('#adicionar').prop('disabled',true);
    }else{
      $('#valor_informado').val(Math.round((parseFloat(valor_pagar)-parseFloat(valor_pago))*100)/100);
    }
}

function preencheCredito(){
    var valor_restante = $("#valor_restante").val();
    if(valor_restante<0){
      valor_restante *= -1;
      valor_restante = valor_restante.toFixed(2);
      valor_restante_formatado = valor_restante.replace('.',',');
      $('#valor_credito').val(Math.round((parseFloat(valor_restante))*100)/100);
      $('#label_credito').text(valor_restante_formatado);
    }
}

//função que busca o tipo do documento
function verificaTipoDocumento(documento,numero){
    if(documento==numero){
        return true;
    }else{
        return false;
    }
}

//função que busca o nome da tabela de preço
function buscaNomeTabela(tabela_preco){
    switch (tabela_preco) {
        case 1:
            return "Atac. Prazo";
        case 2:
            return "Atac. Vista";
        case 3:
            return "Varejo Prazo";
        case 4:
            return "Varejo Vista";
        case 5:
            return "Representação";
        case 6:
            return "Débito";
        default:
            break;
    }
}

//função que verica a quantidade de dias
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

//função que verifica quantidade de parcelas
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

//função que mostra nome do documento
function documentoEscolhido(documento){
    switch (documento) {
        case 1:
            return "Dinheiro";
        case 2:
            return "Vale";
        case 3:
            return "Promissória";
        case 4:
            return "Cartão Débito";
        case 5:
            return "Cartão Crédito";
        case 6:
            return "Cheque";
        case 7:
            return "Depósito";
        case 8:
            return "Pagamento Freteiro";
        case 9:
            return "Retirada após Pagamento";
        default:
            break;
    }
}

//função que verifica se conta bancaria é válida
function verificaContaBancaria(conta_bancaria,documento){
    if(contaBancaria==''&&documento==7){
        alert('Informe um valor de conta bancária válido.');
        return false;
    }else{
        return true;
    }
}

//função que verifica se freteiro é válido
function verificaFreteiro(freteiro,documento){
    if(freteiro==''&&documento==8){
        alert('Informe um valor de freteiro válido.');
        return false;
    }else{
        return true;
    }
}

//função que verifica se representante é válido
function verificaRepresentante(id_representante,documento){
    if(id_representante==''&&documento==2){
        alert('Informe um valor de representante válido.');
        return false;
      }else{
        return true;
      }
}

//função que verifica forma de pagamento
function verificaFormaDePagamento(forma_pagamento){
    if(forma_pagamento>0){
        return true;
    }else{
        alert("Informe forma de pagamento válida");
        return false;
    }
}

//função que busca a data de vencimento
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

//função que gera a linha do lançamento
function geraLinha(documento,tabela_preco,parcelas,dias,valorInformado,cliente,representante,id_representante,txtDocumento,tipoCheque,tipoVale,
    txtTabela,contaBancaria,nomeConta,tipoDeposito,tipoFreteiro,freteiro,nomeFreteiro){
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

    var id_cliente = $("#id_cliente").val();
    dataDoDia = dataDoDia();
    let diasVenc = parseInt(dias);
    let valor_parcela = parseFloat(valorInformado)/parseInt(parcelas);
    valor_parcela = valor_parcela.toFixed(2);
    for(let i=0;i<parcelas;i++){
        let dataVencimento = buscaDataVencimento(diasVenc);
        diasVenc = parseInt(diasVenc)+parseInt(dias);
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

//armazena valores informados
$("#form-fechar-faturamento").submit(armazenarCampos);

//função que armazena os valores informados
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
