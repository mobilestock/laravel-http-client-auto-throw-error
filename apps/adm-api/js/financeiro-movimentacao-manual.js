const camposDePesquisaTabela = [
    {
        nome: "Tipo",
        tipo: "select",
        filhos: [
            {
                nome: "Entrada",
                valor: "E"
            },
            {
                nome: "Saida",
                valor: "S"
            }
        ],
        desativado: false
    },
    {
        nome: "Valor",
        tipo: "number",
        filhos: null,
        desativado: false
    },
    {
        nome: "Motivo",
        tipo: "text",
        filhos: null,
        desativado: false
    },
    {
        nome: "Responsavel",
        tipo: "select",
        filhos: [
            {
                nome: "Fabio",
                valor: "356"
            },
            {
                nome: "larissa",
                valor: "526"
            },
            {
                nome: "Admin",
                valor: "8"
            }
        ],
        desativado: false
    },
    {
        nome: "todos",
        tipo: "text",
        filhos: null,
        desativado: false
    }
]

const dados = {
    "titulos":
        [
            'Data Movimentação',
            "Tipo",
            "Valor",
            "Motivo",
            "Responsavel",
            "Estado",
            'Transação/Faturamento',
            'Conferidor',
            'Data Conferido'
        ]
}
$('#data_inicio').val($('#hoje').val());
$('#data_final').val($('#hoje').val());
$(document).on('click', '.paginacao-movimentacoes', function (evento) {
    const posicao = $('.paginacao-movimentacoes').attr('posicao')
    evento.preventDefault()
    buscaDadosParaTabela('#movimentacoes', 'buscarTodos', posicao)
})
$(document).on('click', '#busca-movimentacoes', function (evento) {
    const posicao = $('.paginacao-movimentacoes').attr('posicao')
    evento.preventDefault()
    buscaDadosParaTabela('#movimentacoes', 'buscarTodos', posicao)
})

tabelaMobile('#movimentacoes', '', camposDePesquisaTabela)
buscaDadosParaTabela('#movimentacoes', 'buscarTodos')

function tabelaMobile(seletor, titulo, camposDePesquisaTabela) {

    const seletorHtml = document.querySelector(seletor)

    const nomeSeletorHtml = seletorHtml.getAttribute('id')

    const lista = camposDePesquisaTabela.map(
        pesquisa => {
            return (`<div class="col-3">
                        <div class="custom-control custom-radio">
                            <input 
                                type="radio" 
                                name="filtro-${nomeSeletorHtml}" 
                                id="${nomeSeletorHtml}-${pesquisa.nome}" 
                                class="custom-control-input" 
                                onchange="alteraPesquisa(this,'input-de-pesquisa-${nomeSeletorHtml}',1)">
                            <label 
                                class="custom-control-label" 
                                for="${nomeSeletorHtml}-${pesquisa.nome}">
                                ${pesquisa.nome}
                            </label>
                        </div>
                    </div>`)
        }
    ).join('')

    const html = (`
            <h4>${titulo}</h4>
            <div class="card-market">
                
                    <div id="lista-${nomeSeletorHtml}">
                    
                    </div>
            </div>

`)

    seletorHtml.innerHTML = html
}



function buscaDadosParaTabela(seletor, acao, pagina = 1) {

    const seletorHtml = document.querySelector(seletor)
    const nomeSeletorHtml = seletorHtml.getAttribute('id')

    const selectTemporario = document.getElementById('select-temporiario')
    const pesquisa = document.getElementById(`input-de-pesquisa-${nomeSeletorHtml}`)
    const categoria = document.querySelector(`[name="filtro-${nomeSeletorHtml}"]:checked`)
    // const filtro = categoria?.getAttribute('id')

    // let busca = pesquisa.value

    // if (selectTemporario?.value !== "" && selectTemporario?.value !== undefined) {
    //     busca = selectTemporario?.value
    // }
    var inicio = $('#data_inicio').val();
    var final = $('#data_final').val();
    var data = new FormData()
    data.append('acao', acao)
    if (pagina > 1) {
        if (inicio) {
            data.append('data_inicio', inicio)
        }
        if (final) {
            data.append('data_final', final)
        }
    }

    // data.append('pagina', pagina)

    fetch(`src/controller/movimentacaoManualCaixaController.php`, {
        method: 'post',
        body: data
    })
        .then(resp => resp.json())
        .then(resultado => {
            // localStorage.setItem("@posicao", pagina)
            // localStorage.setItem("@raio", 3)
            $(`#lista-${nomeSeletorHtml}`).html(tabela(seletor, dados, resultado))
            // localStorage.removeItem("@posicao")
            // localStorage.removeItem("@raio")

        })
        .catch(erro => { $(`#lista-${nomeSeletorHtml}`).html('Nada encontrado!') })

}

function tabela(seletor, dados, resultado) {
    const seletorHtml = document.querySelector(seletor)

    const nomeSeletorHtml = seletorHtml.getAttribute('id')

    function geraPaginacao(Pagina, Raio) {
        let pagina = parseInt(Pagina)

        let raio = parseInt(Raio)

        let previa = pagina - 1

        let proximo = pagina + 1

        let contador = 1

        let paginacao = []

        let retorno = []

        if (pagina === 1) {
            for (let i = 0; i <= 3; i++) {
                paginacao.push(i)
            }
        }

        if (pagina >= 2 && pagina <= 3) {
            for (let i = pagina; i >= 0; i--) {
                paginacao.unshift(i)
            }

            for (let i = pagina + 1; i <= 6; i++) {
                paginacao.push(i)
            }
        }

        if (pagina > 3) {
            for (let i = pagina; i >= pagina - raio; i--) {
                paginacao.unshift(i)
            }
            for (let i = pagina + 1; i <= pagina + raio; i++) {
                paginacao.push(i)
            }
        }
        const total = paginacao.length - 1

        paginacao.forEach((posicao, index) => {

            const ativo = pagina == posicao

            if (index !== 0 && index !== total && contador <= 5) {
                retorno.push({ posicao, ativo })
            }

        })
        primeiraPagina = pagina >= 4

        return {
            primeiraPagina,
            paginaAnterior: previa,
            proximaPagina: proximo,
            paginas: retorno
        }

    }

    function paginacaoView(tipo) {
        var raio = 3

        var posicaoAtual = 1

        if (localStorage.getItem("@posicao")) {
            posicaoAtual = localStorage.getItem("@posicao")
        }
        if (localStorage.getItem("@raio")) {
            raio = localStorage.getItem("@raio")
        }

        const paginacao = geraPaginacao(posicaoAtual, raio)


        return (`
                <nav aria-label="Navegação de página exemplo">
                
                
                <ul class="pagination justify-content-end pagination-sm my-2">

                ${paginacao.primeiraPagina ? (`
                        <li class="page-item  mx-2">
                            <a class="page-link pagina" posicao="1">1</a>
                        </li>
                        `) : ""}
                        
                        ${paginacao.paginaAnterior >= 2 ? (`
                            <li class="page-item">
                                <a class="page-link pagina ${tipo}" posicao="${paginacao.paginaAnterior}" tabindex="-1">anterior</a>
                            </li>
                            `) : ""}


                        ${paginacao.paginas?.map(pagina => {
            return (`
                                    <li class="page-item ${pagina.ativo && ("active")}">
                                        <a class="page-link pagina ${tipo}" posicao="${pagina.posicao}">${pagina.posicao}</a>
                                    </li>
                                    `)
        }).join('')}

                            
                        ${paginacao.proximaPagina ? (`
                            <li class="page-item ">
                                <a class="page-link pagina ${tipo}" posicao="${paginacao.proximaPagina}"  tabindex="-1">Próximo</a>
                            </li>
                            `) : ""}
                    </ul>
                </nav>
            `)
    }

    return `
        ${paginacaoView(`paginacao-${nomeSeletorHtml}`)}
            <table class="table table-hover table-sm">
                <thead>
                    <tr> ${dados.titulos.map(data => `<th>${data}</th>`).join('')} </tr>
                </thead>

                <tbody class="corpo-tabela-${nomeSeletorHtml}">
                    ${montaLinhasTabelaMobile(resultado)}
                </tbody>
            
            </table>
        ${paginacaoView(`paginacao-${nomeSeletorHtml}`)}
    `
}

function montaLinhasTabelaMobile(resultado) {
    console.log(resultado);
    return resultado.map(item => {
        if (item.conferido_em == "") {
            conferido = `<button class="btn btn-secondary btn-sm conferir" id="${item.id}">Conferir</button>`
        } else {
            conferido = `<button class="btn btn-primary btn-sm" >Conferido</button>`
        }
        return `
        <tr class="${(item.tipo == 'S' ? 'fundo-vermelho' : '')}">
        <td>${item.criado_em}</td>
        <td>${item.tipo}</td>
        <td>${item.valor}</td>
        <td>${(item.motivo ? item.motivo : '-')}</td>
        <td>${item.responsavel}</td>
        <td>${conferido}</td>
         <td>${(item.id_faturamento ? item.id_faturamento : '-')}</td>
        <td>${(item.conferido_por ? item.conferido_por : '-')}</td>
        <td>${(item.conferido_em ? item.conferido_em : '-')}</td>
        </tr>`
    }).join('')
}

function alteraPesquisa(entrada, inputBusca, tipo) {
    const selecttemporario = document.getElementById('select-temporiario')

    const dicionario = camposDePesquisaTabela

    const categoria = entrada.getAttribute('id').split('-')
    const pegaTipo = dicionario.find(item => item.nome === categoria[categoria.length - 1])
    const busca = document.getElementById(inputBusca)

    busca.removeAttribute('type')
    busca.removeAttribute('disabled')
    busca.value = ''

    selecttemporario?.parentNode.removeChild(selecttemporario)

    if (pegaTipo.tipo === 'select') {

        const select = document.createElement('select')
        const optionPadrao = document.createElement('option')

        busca.style.display = 'none'
        busca.value = ""

        select.setAttribute('id', 'select-temporiario')
        select.className = "form-control input-novo"

        optionPadrao.setAttribute('hidden', true)
        optionPadrao.innerHTML = "Selecione"

        select.append(optionPadrao)

        pegaTipo.filhos?.map(categorias => {

            const option = document.createElement('option')

            option.value = categorias.valor
            option.innerHTML = categorias.nome

            select.append(option)

        })

        if (pegaTipo.desativado) {

            select.setAttribute('disabled', pegaTipo.desativado)
            optionPadrao.value = "bloqueado"
            optionPadrao.innerHTML = "Produtos bloqueados"

        }

        busca.insertAdjacentElement('afterend', select)

        return
    }

    busca.style.display = 'block'
    busca.setAttribute('type', pegaTipo.tipo)
}
