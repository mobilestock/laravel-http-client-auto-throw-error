<?php

require 'cabecalho.php';
acessoUsuarioAdministrador();
?>
<div class="container-fluid">
    <header>
        <h1>Central de notificações</h1>
    </header>

    <main>
        <section>
            <form id="form-teste">
                <div class="row d-flex my-2 mt-5 justify-content-between">
                    <div class="col-sm-2 col-4 ">
                        <button class="btn btn-danger shadow btn-sm btn-block">Voltar</button>
                    </div>

                    <div class="col-12 col-sm-7 m-0 p-0 row">
                        <div class="col-sm-6">
                            <input type="text" id="nome" placeholder="Pesquisar" name="nome" class="btn-sm form-control">
                        </div>
                        <div class="col-sm-3">
                            <input type="date" id="data" name="data" class="btn-sm  form-control">
                        </div>
                        <div class="col-sm-3">
                            <select class="btn-sm form-control" name="tipo" id="tipo">
                                <option value="" hidden> Selecione o tipo</option>
                                <option value="All">Todos</option>
                                <option value="Z"> Zoop</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-1 ">
                        <button type="button" id="limpa-pesquisa" class="btn btn-sm shadow btn-danger btn-block">Limpar</button>
                    </div>
                    <div class="col-sm-2 ">
                        <button type="submit" class="btn btn-sm shadow btn-light btn-block">Buscar</button>
                    </div>
                </div>
            </form>
        </section>
        <section id="tabela-notificacao">

        </section>

    </main>

</div>

<script>
    window.onload = () => {

        buscaNotificacoes()

        document.getElementById('form-teste').addEventListener('submit', (event) => {
            event.preventDefault()
            buscaNotificacoes()
        })
        document.getElementById('limpa-pesquisa').addEventListener('click', (event) => {
            event.preventDefault()
            document.getElementById('nome').value = ''
            document.getElementById('data').value = ''
            buscaNotificacoes()
        })

        async function buscaNotificacoes(pagina = 1) {
            localStorage.setItem("@posicao", pagina)
            localStorage.setItem("@raio", 3)
            let form = new FormData()


            const nome = document.getElementById('nome').value
            const data = document.getElementById('data').value
            const tipo = document.getElementById('tipo').value
            form.append('nome', nome)
            form.append('tipo', tipo)
            form.append('data', data)
            form.append('pagina', pagina)

            await fetch(
                    'src/controller/Notificacoes/NotificacoesListar.php', {
                        method: 'POST',
                        body: form
                    })
                .then(async resp => await resp.json())
                .then(resposta => {

                    tabela = `<div class="my-4">
            ${paginacaoView('paginacao-notificacao')}
                <table class="table table-sm">
                    <thead  class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Mensagem</th>
                            <th scope="col">Destinatario</th>
                            <th scope="col">Lido</th>
                            <th scope="col">data</th>
                            <th scope="col">Tipo</th>
                        </tr>
                    </thead>
                    <tbody >
                    ${resposta.data.map(items=>{
                        return ( `
                        <tr>
                        <td>${items.id}</td>
                        <td>${items.mensagem}</td>
                        <td>${items.id_cliente}</td>
                        <td>${items.recebida?"Lido":"Não lido"}</td>
                        <td>${items.data_evento?.substr(0,10).split('-').reverse().join('/')} <br> ${items.data_evento?.substr(10)}</td>
                        <td>${items.tipo_mensagem}</td>
                        </tr>
                        ` )
                    }).join('')}

                        </tbody>
                </table>
            ${paginacaoView('paginacao-notificacao')}
            </div>
            `

                    if (resposta.data.length === 0) {
                        tabela = `
                        <div style="width: 100vw; height:20vh" class="row d-flex justify-content-center align-items-center">
                            Sua mensagem não foi encontrada 😕
                        </div>
                    `
                    }


                    document.getElementById('tabela-notificacao').innerHTML = tabela
                    localStorage.removeItem("@posicao")
                    localStorage.removeItem("@raio")
                }).catch(()=>{
                    alert('não foi possivel obter os dados corretamente, verifique com um responsavel da equipe de TI.')
                })
        }

        $(document).on('click', '.paginacao-notificacao', function() {
            const pagina = $(this).attr('posicao')

            buscaNotificacoes(pagina)

        })
    }
</script>

<script src="js/geraPaginacao.js<?= $versao; ?>"></script>

<?php

require 'rodape.php';

?>