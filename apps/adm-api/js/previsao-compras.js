$(document).on('click','.fornecedor',function(){
    $(this).attr("class","btn btn-info btn-block filtro").html("<b>GESTÃO DE COMPRAS</b>")
})
$(document).on('click','.filtro',function(){
    $(this).attr("class","btn btn-info btn-block fornecedor").html("<b>FORNCEDOR</b>")
    location.href ="previsao-compras.php";
})

document.querySelectorAll('[paginacao]').forEach(link => {
    const conteudo = document.getElementById('conteudo')
    link.onclick = function (e) {
        e.preventDefault()
        fetch(link.getAttribute('paginacao'))
            .then(resp => resp.text())
            .then(html => conteudo.innerHTML = html)
    }
})

