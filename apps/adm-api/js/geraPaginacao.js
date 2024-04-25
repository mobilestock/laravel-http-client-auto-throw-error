function geraPaginacao( Pagina, Raio )
{
    let pagina = parseInt(Pagina)

    let raio = parseInt(Raio)
    
    let previa = pagina - 1

    let proximo = pagina + 1

    let contador = 1

    let paginacao = []

    let retorno = []

    if(pagina === 1){
        for (let i = 0; i <= 3; i++) {
            paginacao.push(i)
        }
    }

    if(pagina >= 2 && pagina <= 3){
        for (let i = pagina; i >= 0; i--) {
            paginacao.unshift(i)
        }

        for (let i = pagina+1; i <= 6; i++) {
            paginacao.push(i)  
        }
    }

    if(pagina > 3){
        for (let i = pagina; i >= pagina - raio; i--) {
            paginacao.unshift(i)
        }
        for (let i = pagina+1; i <= pagina + raio; i++) {
            paginacao.push(i) 
        }
    }
    const total = paginacao.length - 1

    paginacao.forEach((posicao, index)=>{

        const ativo =  pagina == posicao
        
        if(index !== 0 && index !== total && contador <= 5)
        {
            retorno.push({posicao,ativo})
        }

    })
    primeiraPagina = pagina >= 4

    return {
        primeiraPagina,
        paginaAnterior:previa,
        proximaPagina:proximo,
        paginas:retorno
    }

}
function paginacaoView(tipo)
{

    var raio = 3

    var posicaoAtual = 1
    
    if(localStorage.getItem("@posicao"))
    { 
        posicaoAtual =  localStorage.getItem("@posicao") 
    }
    if(localStorage.getItem("@raio"))
    { 
        raio =  localStorage.getItem("@raio") 
    }


    
    
    const paginacao = geraPaginacao(posicaoAtual,raio)


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
                    `):""}


                ${paginacao.paginas?.map(pagina=>{
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
                    `):""}
            </ul>
        </nav>
    `)
}