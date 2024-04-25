function mascaraDeTelefone(telefone){
	const textoAtual = telefone.value;
	const isTelComDDD = textoAtual.length === 10;
    const isCelularComDDD = textoAtual.length === 11;
let textoAjustado;
	if(isCelularComDDD){
		const parte1 = textoAtual.slice(0,2);
		const parte2 = textoAtual.slice(2,7);
		const parte3 = textoAtual.slice(7,11);
		textoAjustado = `(${parte1})${parte2}-${parte3}`      
    } else if(isTelComDDD){
		const parte1 = textoAtual.slice(0,2);
		const parte2 = textoAtual.slice(2,6);
		const parte3 = textoAtual.slice(6,10);
		textoAjustado = `(${parte1})${parte2}-${parte3}`
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