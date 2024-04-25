$("#cpf").blur(function(){
    var cpf = $(this).val();
    VerificaCPF(cpf);
});

function VerificaCPF(strCpf) {
    strCpf = strCpf.replace(/\D/g, "");
    var soma = 0;
    var resto = 0;
    if (strCpf == "00000000000" || strCpf.length<11) {
        $( '#cpf_status' ).html("CPF inválido."); 
        $("#cpf").css("background-color","#F6CECE"); 
        $("#enviar").attr("disabled",true);
    return false;
    }
    for (i = 1; i <= 9; i++) {
        soma = soma + parseInt(strCpf.substring(i - 1, i)) * (11 - i);
    }
    resto = soma % 11;
    if (resto == 10 || resto == 11 || resto < 2) {
        resto = 0;
    } else {
        resto = 11 - resto;
    }
    if (resto != parseInt(strCpf.substring(9, 10))) {
        return false;
    }
    soma = 0;
    for (i = 1; i <= 10; i++) {
        soma = soma + parseInt(strCpf.substring(i - 1, i)) * (12 - i);
    }
    resto = soma % 11;
    if (resto == 10 || resto == 11 || resto < 2) {
        resto = 0;
    } else {
        resto = 11 - resto;
    }
    if (resto != parseInt(strCpf.substring(10, 11))) {
        $( '#cpf_status' ).html("CPF inválido.");
        $("#cpf").css("background-color","#F6CECE");
        $("#enviar").attr("disabled",true);
        return false;
    }
    $( '#cpf_status' ).html("CPF válido");
    $("#cpf").css("background-color","#CEF6D8");
    $("#enviar").attr("disabled",false);
    return true;
}
