let labelTotal = document.querySelector(".saldo-credito")
let tabelaTudo = document.querySelector(".tabela-extrato")
tabelaTudo.innerHTML = `<i class="fa fa-circle-o-notch fa-spin"></i>`;
let tabelaPendente = document.querySelector(".tabela-pendente")
labelTotal.innerHTML = `<i class="fa fa-circle-o-notch fa-spin"></i>`

creditos().then(json => {

    labelTotal.textContent = parseFloat(json.valor_total).toLocaleString("pt-br", {
        style: "currency",
        currency: "BRL",
    });

    if (json.pendentes.length > 0) {
        tabelaPendente.innerHTML = montaLancamentosPendentes(json.pendentes)
    }

    tabelaTudo.innerHTML = montaLancamentos(json.lancamentos);

})

function montaLancamentosPendentes(pendentes) {
    return `
  <div class="card-market">
  <h5>Créditos aguardando aprovação</h5>
    <table class="table table-sm table-striped table-hover">
    <thead>
      <tr>
        <th>Cod</th>
        <th>Data Inserção</th>
        <th>Valor</th>
        <th></th>
      </tr>
    </thead>
    ${pendentes.map(i => {
        return `
      <tr>
        <td>${i.id}</td>
        <td>${i.data_emissao}</td>
        <td>${parseFloat(i.valor).toLocaleString("pt-br", {
            style: "currency",
            currency: "BRL",
        })}</td>
        <td><a style="font-size:18px" class="btn btn-primary" href="${i.url_boleto}"><i class="fas fa-barcode"></i></a></td>
      </tr>
      `
    }).join('')}</table></div><br>`
}

function montaLancamentos(lancamentos) {
    let str = '';
    let saldoDia = 0;
    let lastDate = ''

    lancamentos.map((u, i) => {
        u.tipo == 'P' ? saldoDia += parseFloat(u.preco) : saldoDia -= parseFloat(u.preco)
        lancamentos[i].saldo = saldoDia
    })
    lancamentos.reverse();
    lancamentos.map(i => {
        if (lastDate != i.data_credito) {
            lastDate = i.data_credito
            str += `<div class="m-2 p-2 bg-light">Data: <strong>${i.data_credito}</strong> / Saldo dia: <strong>${parseFloat(i.saldo).toLocaleString("pt-br", {
                style: "currency",
                currency: "BRL",
            })}</strong></div></div>`
        }
        str += `<div class="d-flex bd-highlight">
                <div class="col-sm-1 col-1 p-2">${mostraIconeOrigem(i.origem)}</div>
                <div class="col-sm-4 col-5 p-2"><strong style="font-size:12px">${mostraMensagemOrigem(i.origem)}</strong><br /><small>${i.observacao}</small></div>
                <div class="col-sm-4 col-5 p-2"><strong>${formataValor(i.tipo, i.preco)}</strong></div>
                <div class="col-sm-3 col-1 p-2"><a onclick="mostrarDetalhes(${i.id})" class="botao-detalhes" style="box-shadow: none;"><i class="fas fa-info-circle"></i></a></div>
            </div>
            <div style="display:none;" class="detalheLinha_${i.id}">
                <div class="row pl-3 pr-3">
                    <div class="col-sm-2 col-4 p-2">${mostraFotoProduto(i)}</div>
                </div>
            </div>`
    }).join('')
    return str;
}

function mostraIconeOrigem(origem) {
    switch (origem) {
        case 'PI':
            return `<i class="fas fa-dollar-sign"></i>`
            break;
        case 'RI':
            return `<i class="fas fa-dollar-sign"></i>`
            break;
        case 'PC':
            return `<i class="fas fa-dollar-sign"></i>`
            break;

        case 'CM':
            return `<i class="fas fa-dollar-sign"></i>`
            break;

        case 'CP':
            return `<i class="fas fa-check-circle"></i>`
            break;

        case 'TR':
            return `<i class="fas fa-exchange-alt"></i>`
            break;

        case 'AT':
            return `<i class="fas fa-headset"></i>`
            break;

        case 'AU':
            return `<i class="fas fa-sync"></i>`
            break;

        case 'MA':
            return `<i class="fas fa-hand-holding"></i>`
            break;
        case 'RE':
            return '<i class="fas fa-undo"></i>'
            break;        
        case 'ES':
            return '<i class="fas fa-backspace"></i>'
            break;
        case 'TX':
            return '<i class="fas fa-frown"></i>'
            break;
        default:
            break;
    }
}

function mostraMensagemOrigem(origem) {
    switch (origem) {
        case 'PI':
            return `Transferência Mobile Pay`
            break;
        case 'RI':
            return `Saque Mobile Pay`
            break;
        case 'DR':
            return `Venda devolução`
            break;
        case 'PC':
            return `Utilização de crédito`
            break;

        case 'CM':
            return `Crédito adicionado`
            break;

        case 'CP':
            return `Faltou no pedido`
            break;

        case 'TR':
            return `Devolução`
            break;

        case 'AT':
            return `Estorno de valor`
            break;

        case 'AU':
            return `Valor excedente`
            break;

        case 'MA':
            return `Crédito manual`
            break;
        case 'RE':
            return `Reembolso`
            break;
        case 'ES':
            return `Estorno`
            break;
        case 'TX':
            return `Taxa`
            break;

        default:
            break;
    }
}

function formataValor(tipo, valor) {
    if (tipo == 'P') {
        return `<span class="text-success">+ ${parseFloat(valor).toLocaleString("pt-br", {
            style: "currency",
            currency: "BRL",
        })}</span>`
    } else {
        return `<span class="text-danger">- ${parseFloat(valor).toLocaleString("pt-br", {
            style: "currency",
            currency: "BRL",
        })}</span>`
    }
}

function mostrarDetalhes(id) {
    let detalhe = document.querySelector(`.detalheLinha_${id}`);
    if (detalhe.style.display == "block") {
        detalhe.style.display = "none"
    } else {
        detalhe.style.display = "block"
    }
}

function mostraFotoProduto(i) {
    if (i.caminho != 'NA') {
        return `<img src="${i.caminho}" width="80px"></img>`
    } else {
        return '<i class="fas fa-image"></i>'
    }
}

document.querySelector('#form-inserir-credito').addEventListener('submit',async e => {

    if (e.target.querySelector('#id_transacao').value === ''){
        e.preventDefault();
        
        let btnSubmit = document.querySelector('#btn-enviar-credito');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="sr-only">Loading...</span>`;

        let json = await fetch('api_pagamento/transacao/credito', {
            method: 'POST',
            headers: new Headers({
                token: cabecalhoVue.user.token
            }),
            body: JSON.stringify({
                id_cliente: document.querySelector('#id_cliente').value,
                valor: document.querySelector("#credito-manual").value
            })
        }).then(r => r.json());

        if (!json.status) {$.alert(json.message);return;}

        document.querySelector('#id_transacao').value = json.data.id;
        document.querySelector('#form-inserir-credito').submit();
    }
});