$(document).ready(function(){
    $("#enviar").attr("disabled",true);
    $("#cnpj").attr("disabled",true);
    $("#cpf").attr("disabled",true);
});

function mascaraDeTelefone(telefone){
	const textoAtual = telefone.value;
	const isTel = textoAtual.length === 8;
	const isCelular = textoAtual.length === 9;
	const isTelComDDD = textoAtual.length === 10;
    const isCelularComDDD = textoAtual.length === 11;
let textoAjustado;
	if(isCelularComDDD){
		const parte1 = textoAtual.slice(0,2);
		const parte2 = textoAtual.slice(2,7);
		const parte3 = textoAtual.slice(7,11);
		textoAjustado = `(${parte1})${parte2}-${parte3}`
	}else if(isCelular) {
        const parte1 = textoAtual.slice(0,5);
        const parte2 = textoAtual.slice(5,9);
        textoAjustado = `${parte1}-${parte2}`        
    } else if(isTelComDDD){
		const parte1 = textoAtual.slice(0,2);
		const parte2 = textoAtual.slice(2,6);
		const parte3 = textoAtual.slice(6,10);
		textoAjustado = `(${parte1})${parte2}-${parte3}`
	}else if(isTel){
        const parte1 = textoAtual.slice(0,4);
        const parte2 = textoAtual.slice(4,8);
        textoAjustado = `${parte1}-${parte2}`
    }else{
    textoAjustado = ``;
	}
    telefone.value = textoAjustado;
}


function tiraHifen(telefone) {
    const textoAtual = telefone.value;
    const textoAjustado = textoAtual.replace(/\-/g, '');

    telefone.value = textoAjustado;
}

function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;   
    if((tecla>47 && tecla<58)) return true;
    else{
    	if (tecla==8 || tecla==0) return true;
	else  return false;
    }
}
  var pessoa_fisica = document.querySelector("#pessoa_fisica");
  var pessoa_juridica = document.querySelector("#pessoa_juridica");
  var div_cnpj = document.querySelector("#div_cnpj");
  var div_cpf = document.querySelector("#div_cpf");
  var campo_cnpj = document.querySelector("#cnpj");
  var campo_cpf = document.querySelector("#cpf");

  pessoa_juridica.onclick = verificaRegime;
  pessoa_fisica.onclick = verificaRegime;

    function verificaRegime(){
        if(pessoa_juridica.checked === true){
            div_cnpj.style.display = "block";
            div_cpf.style.display = "none";
            campo_cpf.value = "";
            campo_cpf.disabled = true;
            campo_cnpj.disabled = false;
            $("#enviar").attr("disabled",false);
        }else if(pessoa_fisica.checked === true){
            div_cnpj.style.display = "none";
            div_cpf.style.display = "block";
            campo_cnpj.value = "";
            campo_cpf.disabled = false;
            campo_cnpj.disabled = true;
            $("#cpf").css("background-color","white");
            $( '#cpf_status' ).html("");
            $("#enviar").attr("disabled",false);
        }
    }