$("#btn-enviar-mensagem").on("click",abrirMensagem);

function abrirMensagem(){
    if($("#usuario").val()!=""){
        $('#corpo-mensagem').append("<textarea class='form-control' rows='5' style='resize:none'/>");
    }else{
        alert("Informe um usário válido");
    }
}