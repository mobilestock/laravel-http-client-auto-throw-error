$('#situacao').on('change',passadoParaObrigatorio);

$('#passado_para_manual').on('blur',passadoParaManual);

function passadoParaObrigatorio(){
    if($(this).val()==2){
        $('#passado_para').prop('required',true);
    }else{
        $('#passado_para').prop('required',false);
    }
}

function passadoParaManual(){
    if($(this).val()!=''){
        $('#passado_para').prop('required',false);
        $('#passado_para').val(12);
    }else{
        $('#passado_para').prop('required',true);
        $('#passado_para').val('');
    }
}

$(document).ready(function(){
    if($('#situacao').val()==2 && $('#passado_para_manual').val()==''){
        $('#passado_para').prop('required',true);
    }else{
        $('#passado_para').prop('required',false);
    }
});