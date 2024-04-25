var valor_frete = 0;
let elMensagem = _("#mensagem-frete");
_("#btn-pagamento").disabled = true;
function validaFrete(el)
{
    if(_('#cb_endereco').checked) 
        _('#btn-pagamento').disabled = false;

        console.log(el);

    let frete = parseInt(el.value);
    let pares = parseInt(localStorage.getItem('pares'));
    let uf = seller.uf;
    
    let data = new FormData();
    data.append("frete",frete);
    data.append("pares",pares);
    data.append("uf",uf);
    calculaFrete(data)
    .then(resultado => {
        elMensagem.innerHTML= resultado;
        _("#btn-pagamento").disabled = false;
    })
    .catch(error => console.log(error));
}

if(localStorage.getItem('acrescimo')==true)
{
    _("#btn-pagamento").disabled = false;
}

async function calculaFrete(data){
    let retorno = await fetch('src/controller/FreteController.php',{
        method:'POST',
        body: data
    });

    let resultado = await retorno.json();
    valor_frete = resultado.data.valor_frete;
    return resultado.data.html;
}
