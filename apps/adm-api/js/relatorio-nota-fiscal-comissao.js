

buscaFaturamentosFornecedor();

function buscaFaturamentosFornecedor(){
    var idFornecedor = window.localStorage.getItem("id_fornecedor");
    var mes = window.localStorage.getItem("mes");
    var ano = window.localStorage.getItem("ano");
    var link = '../controle/fiscalController.php';

    var data = new FormData();
    data.append("acao","buscaFaturamentosComissao");
    data.append("id_fornecedor",idFornecedor);
    data.append("mes",mes);
    data.append("ano",ano);

    var $tabela = document.querySelector(".tabela");

    fetchAsync(link,'POST',data)
    .then(resultado=>montaTabelaFaturamentosComissao($tabela,resultado))
    .catch(erro=>console.log(erro));
}

function montaTabelaFaturamentosComissao($tabela,resultado){

    const linhaTemplate = function(linha){
        return `<tr>
                    <td class='mt-1 align-middle'>${linha.id}</td>
                    <td class='mt-1 align-middle'>${new Date(linha.data_emissao)}</td>
                    <td class='mt-1 align-middle'>${linha.data_fechamento}</td>
                    <td class='mt-1 align-middle'>R$ ${linha.valor_produtos.replace('.',',')}</td>
                    <td class='mt-1 align-middle'>${linha.pares}</td>
                    <td class='mt-1 align-middle'>${linha.cliente}</td>
                    <td class='mt-1 align-middle'></td>
                </tr>`;
    }

    function render(){
        $tabela.innerHTML = resultado.map((linha) => {
            return linhaTemplate(linha);
        }).join('');
    }

    render();
}