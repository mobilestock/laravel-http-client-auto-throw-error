$('#transportadora').change(atualizaTransportadora);

function atualizaTransportadora(){
    event.preventDefault();
    $('#hora_coleta').prop('required',true);
}

$(document).ready(function(){
    event.preventDefault();
    if($('#transportadora').val()!=0){
        $('#data_coleta').prop('required',true);
        $('#hora_coleta').prop('required',true);
    }
});

function validaCampos(){
    var volumes = ('#volumes');
    var transportadora = ('#transportadora');
    if(volumes==0||transportadora==0){
        $('#salvar').attr('disabled',true);
    }else if(volumes>0||transportadora>0){
        $('#salvar').attr('disabled',false);
    }
}