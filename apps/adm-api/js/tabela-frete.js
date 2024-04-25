let frete_pares = parseInt(localStorage.getItem("pares"));
let frete_uf = seller.uf;
let frete_valor_prazo = 0;
let frete_valor_vista = 0;

if(frete_uf=='MG'){
    frete_valor_vista = parseFloat(50).toFixed(2);
} else if(frete_uf=='SP'||frete_uf=='RJ'||frete_uf=='ES'){
    frete_valor_vista = parseFloat(75).toFixed(2);
} else if(frete_uf=='BA'||frete_uf=='GO'||frete_uf=='PR'||frete_uf=='DF'||frete_uf=='SC'||frete_uf=='RS'){
    frete_valor_vista = parseFloat(90).toFixed(2);
}else if(frete_uf=='SE'||frete_uf=='AL'||frete_uf=='PE'||frete_uf=='PB'||frete_uf=='RN'||frete_uf=='CE'||frete_uf=='MS'||frete_uf=='MT'){
    frete_valor_vista = parseFloat(100).toFixed(2);
}else if(frete_uf=='PA'||frete_uf=='AP'||frete_uf=='RR'||frete_uf=='AM'||frete_uf=='AC'||frete_uf=='RO'||frete_uf=='MA'||frete_uf=='TO'||frete_uf=='PI'){
    frete_valor_vista = parseFloat(120).toFixed(2);
}

let html = "";
for ( let i = frete_pares ; i < frete_pares + 5 ; i++ ){
    
    let fundo = i%2==0 ? "fundo-cinza" : "";
    let destaque = i==frete_pares ? "fundo-destaque-frete" : "";

    html += `<div class='row corpo ${fundo} ${destaque}'>`;
    html += `<div class='col-sm-2 col-2'><b>${i}</b></div>`;
    html += `<div class='col-sm-10 col-10'><b>R$ ${(frete_valor_vista/i).toFixed(2).replace('.',',')}</b></div>`;
    html += `</div>`;
}  

document.querySelector(".uf_tabela").textContent = frete_uf;
document.querySelector(".pares_tabela").textContent = frete_pares;

$("#tabela-frete").html(html);