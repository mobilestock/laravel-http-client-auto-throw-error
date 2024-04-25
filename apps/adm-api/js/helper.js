buscaDuvida();
function buscaDuvida() {
        var token = cabecalhoVue.user.token;
        fetch(`api_administracao/pay/help`, {
                method: 'GET',
                headers: new Headers({
                    token: token,
                })

            })
            .then(r => r.json())
            .then(json => {
                $('#faq').html(json.data != '' ? montaTabela(json.data):'');
            });
    }

    function montaTabela(duvidas) {
        var str = '';
        var cont = 0;
        duvidas.map(i => {
            cont += 1
            str += `<div class="card-market ${cont % 2 ? 'fundo-cinza':'bg-light'} text-justify">
                        <label>${i.pergunta} <small>${i.data_pergunta}</small></label><br>
                        <hr>
                        <p><b>Resposta:</b>${i.resposta ? i.resposta:''}</p><small>${i.data_resposta ? i.data_resposta : ''}</small>
                    </div><br>
               
           `

        }).join('')
        return str;
    }

    $('#enviar').click(function() {
        var token = cabecalhoVue.user.token;
        fetch(`api_administracao/pay/help/inserir`, {
            method: 'POST',
            headers: new Headers({
                token: token,
            }),
            body: JSON.stringify({
                pergunta:$('#pergunta').val(),
                tipo:'MP'
            })
            
        })
            .then(r => r.json())
            .then(json => {
               $.dialog({
                   title:'Sucesso',
                   content: 'Pergunta Enviada com sucesso!',
                   type:'green'
               })
               
                    window.location.reload();
               
            });

    })