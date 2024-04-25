$('#btn-limpar').on('click',limparFiltros);

function limparFiltros(){
    $(':input,#form')[0].reset();
}