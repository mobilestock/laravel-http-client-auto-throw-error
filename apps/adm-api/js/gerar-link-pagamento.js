let valor, nome_consumidor_final;

document.querySelector('#valor').addEventListener('input', function(evt) {


    valor = evt.target.value.replace(/\D/g, '').replace(/([0-9]{2})$/g, ',$1') || 0

    if (valor.length > 6) {
      valor = valor.replace(/([0-9]{3}),([0-9]{2}$)/g, '.$1,$2')
    }
    document.querySelector('#valor').value = valor;
    valor = valor.toString().split('.').join('').split(',').join('.')

    let pix = parseFloat(valor);
    let cartao = parseFloat(valor) + (parseFloat(valor) * 0.03);

    document.querySelector('#valor-pix').innerText = pix.toLocaleString("pt-BR", { style: "currency" , currency:"BRL"});
    document.querySelector('#valor-cartao').innerText = cartao.toLocaleString("pt-BR", { style: "currency" , currency:"BRL"});
});


document.querySelector('#formulario-gerar-link').addEventListener('submit', function(evt) {

    evt.preventDefault();

    let nome_consumidor_final = document.querySelector('#consumidor-final').value;
    let html = document.querySelector('#btn-submit').innerHTML;
    document.querySelector('#btn-submit').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    MobileStockApi('api_cliente/link_pagamento/credito', {
        body: JSON.stringify({
            valor,
            nome_consumidor_final
        }),
        method: 'POST'
    }).then(function(res) {
        return res.json();
    }).then(function(json) {
        if (!json.status) {
            $.dialog(json.message);
            return;
        }

        $('#btn-submit').html(html);

        $.get(json.data.link.url).done(function(html) {
            $.confirm({
                content: html,
                buttons: {
                    voltar: {},
                    "enviar link" : {
                        btnClass: 'btn-block px-4'
                    }
                }
            })
            $(".jconfirm-title-c").hide();
            $(window).resize(function () {
                $('.ui-dialog').css({
                     'width': $(window).width(),
                     'height': $(window).height(),
                     'left': '0px',
                     'top':'0px'
                });
             }).resize();
            // document.querySelector('#conteudo-modal').innerHTML = html;
        });
    });


});