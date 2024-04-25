function copiarParaAreaDeTrabalho()
{
    var input = document.querySelector('#barcode');
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    var texto = input.value;
    var divAlerta = document.querySelector('.alerta');
    divAlerta.innerHTML = `<div class='alert alert-success sm'>Código de barras copiado.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
    </div>`;
}