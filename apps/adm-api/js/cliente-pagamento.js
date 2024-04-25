var acrescimo = 0;
var calculadoraPedido;
var calculadoraProdutos;
var taxaJuros = 0;
var cartao = 0;
var divPedido = _('#produtos-cliente');
var string = '';
var creditos = ( parseFloat(creditos) >= parseFloat(valor_produtos) ? parseFloat(valor_produtos) : creditos );
var valorTotalCalculado = ( parseFloat(valor_frete) + parseFloat(acrescimo) -parseFloat(creditos) + parseFloat(valor_produtos) )
var pedidoZerado = (tipo_frete==1||tipo_frete==3)&&(parseFloat(valorTotalCalculado)==0)
var opcoes = frete==3?['cartao','deposito','boleto']:['cartao','boleto'];
opcoes = pedidoZerado?['credito']:opcoes;

for(const o of opcoes){
  if(o!='credito')
  _(`.radio-${o}`).style.display = 'block';
}

montaEstruturaInicial();
toggleModoPagamento();

function montaEstruturaInicial()
{

    if(!pedidoZerado)
    {
        _('.pagamento-obrigatorio').style.display = 'block';
    }

    _('#boleto').checked = frete==3?false:true;
    _('#deposito').checked = frete!=3?false:true;

    if(tipoPagamento==2)
    {
        let contaBancariaEscolhidaDeposito = document.querySelector("#conta-bancaria-escolhida-deposito");
        if (contaBancariaEscolhidaDeposito !== undefined) {
            let contaSelecionada = listaContas.find(
              (contaBancaria) =>
                contaBancaria.conta === contaBancariaEscolhidaDeposito.value
            );
            document.querySelector("#conta-bancaria-exibir").innerHTML = linhaConta(
              contaSelecionada
            );
        }
    }
}

function toggleModoPagamento() {

    tipoPagamento = pedidoZerado?'credito':$("input[name=tipo_tabela]:checked").val();
    for(const o of opcoes){
        if(o==tipoPagamento){
            $(`#opcao-${o}`).show();
        }else{
            $(`#opcao-${o}`).hide();
        }
    }

    atualizaValorPedido();
    habilitaConfirmar();
    preencheParcelamentoCartao()

}

function atualizaTela(e){
  atualizaValorPedido();
    habilitaConfirmar();
    preencheParcelamentoCartao()
}

function atualizaValorPedido(){

  let tipoPagamento = pedidoZerado?'credito':document.querySelector('.tipo_tabela:checked').value;
  
  calculadoraPedido = {

      cartao : function(){ 
        obj = juros.find((item) => item.numero_de_parcelas == $("#parcelas").val());
        acrescimo = ( valor_produtos + valor_frete -creditos ) *( obj.juros / 100 )
        taxaJuros = parseFloat(1 + obj.juros / 100);
        cartao = 1;
      },
      deposito : function(){
        valor_frete = parseFloat(valor_frete);
        taxaJuros = 0;
        acrescimo = 0;
        cartao = 0;
      },
      boleto : function(){
        obj = juros.find((item) => item.boleto > 0);
        valor_frete = parseFloat(valor_frete);
        acrescimo = obj.boleto
        taxaJuros = parseFloat(1 + obj.boleto / 100);
        cartao = 0;
      },
      credito : function(){
        valor_frete = 0;
        taxaJuros = 0;
        acrescimo = 0;
        cartao = 0;
      }

  }
  calculadoraPedido[tipoPagamento]();
  montaTotais(); 
}


function preencheParcelamentoCartao() {
  let valorSelecionado = (parseInt(_('#parcelas').value) - 1).toString();
  _('#parcelas').innerHTML = '';
  let strParcelas = '';
  for(j in juros){
    let produtos_com_juros = calculaTaxa(valor_produtos, juros[j].juros);
    let frete_com_juros = calculaTaxa(valor_frete, juros[j].juros);
    let simples = juros[j].juros/100 - 0.03;
    let juros_c = (Math.pow(1 + simples, 1 / juros[j].numero_de_parcelas)) - 1;
    let juros_compostos = juros_c * 100;
    let preco_com_juros = parseFloat(produtos_com_juros)+parseFloat(frete_com_juros);
    let valorParcela = (preco_com_juros / juros[j].numero_de_parcelas).toFixed(2);
     let preco_real = juros[j].numero_de_parcelas * valorParcela;
    strParcelas += `<option ${j === valorSelecionado ? 'selected': ''} real="${preco_real.toFixed(2)}" value="${juros[j].numero_de_parcelas}">
      ${juros[j].numero_de_parcelas} X Taxa de ${juros_compostos.toFixed(4)}% a.m. </option>`;
  }
  _('#parcelas').innerHTML = strParcelas;
}

function calculaTaxa(valor, juros){
  return parseFloat(valor * (1 + juros / 100));
}

function habilitaConfirmar(){
  let tipoPagamento = document.querySelector('.tipo_tabela:checked').value;
  habilita = {
    cartao : ()=>{
      return camposCartao = _("#cc-name").value != '' &&
      _("#cc-number").value != '' &&
      _("#cc-number").value.length == 16 &&
      _("#cc-cvv").value != '' &&
      _("#cc-cvv").value.length == 3
    },
    boleto : () => true,
    deposito: () => true,
    credito : () => true
  }
  let confirm = habilita[tipoPagamento]();
  _("#btn-confirmar-pagamento").disabled = !confirm;
}


function montaTotais(){
  const details = document.getElementById("details");
  
  let tipoPagamento = document.querySelector('.tipo_tabela:checked').value;
  let paramentosDeExibicao = [
    {
      nome:"valor",
      titulo:"Valor dos produtos",
      tituloAlternativo:null,
      visibilidade:true,
      EPositivo:true,
      valor:parseFloat(valor_produtos).toFixed(2).replace('.',',')
    },
    {
      nome:"credito",
      titulo:"Créditos",
      tituloAlternativo:null,
      visibilidade:true,
      EPositivo:parseFloat(creditos)==0.00?true:false,
      valor:parseFloat(creditos).toFixed(2).replace('.',',')
    },
    {
      nome:"frete",
      titulo:"Frete",
      tituloAlternativo:null,
      visibilidade:true,
      EPositivo:true,
      valor:parseFloat(valor_frete).toFixed(2).replace('.',',')
    },
    {
      nome:"acrecimo",
      titulo: tipoPagamento === 'cartao' ? "Taxas e juros do cartão" : 'Tarifa do Boleto',
      tituloAlternativo:"Tarifa boleto",
      visibilidade:tipoPagamento === 'boleto' || tipoPagamento === 'cartao',
      EPositivo:parseFloat(acrescimo)>=0,
      valor:parseFloat(acrescimo).toFixed(2).replace('.',',')
    },
    {
      nome:"total",
      titulo:"Valor Total",
      tituloAlternativo:null,
      visibilidade:true,
      EPositivo:true,
      valor:(parseFloat(valorTotalCalculado)+parseFloat(acrescimo)).toFixed(2).replace('.',',')
    }
  ]

      stringTotal =  paramentosDeExibicao.map(item=>{
         return `
            <span class="${item.visibilidade === true ? '' : 'd-none'}">
              <div class="row d-flex ${item.nome === "total" ?"my-3":""} justify-content-between align-items-center">
                <div class="col-auto col-sm-auto">
                  ${item.titulo} 
                </div>
                ${ !item.EPositivo 
                  ? (`<div class="col-auto text-danger col-sm-auto">
                        R$ -${item.valor}
                      </div>`) 
                  : (`<div class="col-auto  col-sm-auto">
                      ${item.nome === "total" 
                        ?`<b>R$ ${item.valor}</b>`
                        :`R$ ${item.valor}`}
                      </div>`) }
              </div>
              <hr class="my-1">
            </span>`
        }).join('')


    details.innerHTML = stringTotal;
}

document.querySelector("#cc-number").onclick = ()=>{
  document.querySelector("#cc-number").scrollHeight = 500;
}