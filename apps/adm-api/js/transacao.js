
var situacao = new Map();
situacao.set('CR', 'Criado');
situacao.set('PE', 'Pendente');
situacao.set('PA', 'Pago');

var pagamento = new Map();
pagamento.set('BL', 'Boleto <i class="fas fa-barcode"></i>');
pagamento.set('CA', 'Cartão <i class="far fa-credit-card"></i>');
pagamento.set('CR', 'Crédito <i class="fas fa-coins"></i>');
pagamento.set('PX', 'PIX <i class="fas fa-exchange"></i>');

//buscaTodasTransacoes();

// $('#pesquisar').click(function(){
    // var filtros = []
    // let value = "";
    // if($('#id').val()){
    //     value = $('#id').val();
    //     filtros.push({'id' : value});
    // }
    // if ($('#cod_transacao').val()) {
    //     value ="'";
    //     value = value + $('#cod_transacao').val();
    //     value = value + "'";
    //     filtros.push({ 'cod_transacao': value });
    //     // filtros.set('cod_transacao', value);
    // }
    // if ($('#pagador').val()!='') {
    //     value = $('#pagador').val();
    //     filtros.push({'pagador': value});
    //     // filtros.set('pagador', value);
    // }
    // if ($('#responsavel').val()!='') {
    //     value = $('#responsavel').val();
    //     filtros.push({'responsavel': value[0]});
    //     // filtros.set('responsavel', value);
    // }
    // if ($('#meio_pagamento').val() !='') {
    //     value = "'";
    //     let pagamento = $('#meio_pagamento').val();
    //     value = value + pagamento[0];
    //     value = value + "'";
    //     filtros.push({'metodo_pagamento': value});
    //     // filtros.set('pagamento', value);
    // }
    // if ($('#status').val()!='') {
    //     value = "'";
    //     let status = $('#status').val();
    //     value = value + status[0];
    //     value = value + "'";
    //     filtros.push({'status': value});
    //     // filtros.set('situacao', value);
    // }
    // if ($('#id_faturamento').val() != '') {
    //     value = $('#id_faturamento').val();
    //     filtros.push({ 'id_faturamento': value });
    //     // filtros.set('situacao', value);
    // }
    // if($('#credito').is(':checked')){
    //     value = $('#credito').val();
    //     filtros.push({ 'tipo_item': value });
    // }
//     buscaTransacoesFiltro();
// })

// function buscaTodasTransacoes(){
//     fetch(`./src/controller/Transacao/TransacaoController.php?action=buscaTransacoes`)
//         .then(resp => resp.json())
//         .then(resultado => {
//             //document.querySelector('#modal-' + id).innerHTML
//             $('#lista-transacoes').html(indiceTabelaTransacao(resultado));
//         });
// }
async function limpar(){
    window.location.reload();
}
async function buscaTransacoesFiltro() {
    // data.append('action','buscaTransacoesFiltros');
    // data.append('filtros',JSON.stringify(filtros));
    var status = $('#status').val();
    var pagamento = $('#meio_pagamento').val();

    if ($('#id').val()) {
        var id = $('#id').val();
    } else {
        var id = 0;
    }

    if($('#data_de').val()){
        var data_de = $('#data_de').val();
    }else{
        var data_de = 0000-00-00;
    }

    if ($('#data_ate').val()) {
        var data_ate = $('#data_ate').val();
    }else{
        var data_ate = 0000-00-00;
    }

    if ($('#pagador').val()) {
        var pagador = $('#pagador').val();
    }else{
        var pagador = 0;
    }

    if ($('#cod_transacao').val()) {
        var cod_transacao = $('#cod_transacao').val();
    } else {
        var cod_transacao = 0;
    }

    if ($('#id_entrega').val()) {
        var entrega = $('#id_entrega').val();
    } else {
        var entrega = 0;
    }

    if (pagamento[0]) {
        var meio_pagamento = pagamento[0];
    } else {
        var meio_pagamento = 0;
    
    }

    if (status[0]) {
        var status = status[0];
    } else {
        var status = 0;
    }


    await MobileStockApi(`api_administracao/transacoes/transacao`, {
        method: 'post',
        body: JSON.stringify({
            id: id,
            data_de: data_de,
            data_ate: data_ate,
            pagador: pagador,
            cod_transacao: cod_transacao,
            entrega: entrega,
            meio_pagamento: meio_pagamento,
            status: status

        })
    })
            .then(resp => resp.json())
            .then(resultado => {
                //document.querySelector('#modal-' + id).innerHTML
                $('#lista-transacoes').html(indiceTabelaTransacao(resultado));
            });
}
function indiceTabelaTransacao(model){
    return `
                <table class="table table-sm table-striped table-hover text-dark">
                    <tr class="font-table-sm table-info" style="font-size:16px">
                        <th>ID</th>
                        <th>Pagador</th>
                        <th>Responsável</th>
                        <th>Pagamento</th>
                        <th>Situação</th>
                        <th>Valor</th>
                        <th>Crédito</th>
                        <th>Data</th>
                        <th>Transação</th>
                        <th>Detalhe</th>
                    </tr>
                    ${templateTransacao(model.data.transacoes)}
                </table>`
}

function templateTransacao(linhas){
    return `${linhas.map((linha) => {
        return `<tr class="font-table" style="font-size:14px">
                    <td>${linha.id}</td>
                    <td>${linha.pagador}</td>
                    <td>${linha.responsavel}</td>
                    <td>${pagamento.get(linha.metodo_pagamento)}</td>
                    <td>${situacao.get(linha.status)}</td>
                    <td>${parseFloat(linha.valor_liquido).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' })}</td>
                    <td>${parseFloat(linha.valor_credito).toLocaleString('pt-br', { style: 'currency', currency: 'BRL' })}</td>
                    <td>${linha.data_atualizacao}</td>
                    <td>${linha.cod_transacao}</td>
                    <td><a href="transacao-detalhe.php?id=${linha.id}"><i class="fas fa-info-circle"></i></a></td>

                </tr>`;
    }).join('')}`;
}