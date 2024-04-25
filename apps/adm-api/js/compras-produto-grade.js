/* preenchimento de grade resumo na compra*/
var tam_grade = document.querySelectorAll("#tam_grade");
var tam_resumo = document.querySelectorAll("#tam_resumo");
var quant = document.querySelectorAll("#quant");
var calc_quant = document.querySelectorAll("#calc_quant");

var quant_total = document.querySelector("#quant_total");
var preco_unit = document.querySelector("#preco_unit");
var valor_total = document.querySelector("#valor_total");

function preencheGradeResumo(){
		var caixas = document.querySelector("#caixas").value;
		var calc_quant_total = 0;
		for(var i=0;i<tam_grade.length;i++){
			tam_resumo[i].textContent = tam_grade[i].textContent;
			calc_quant[i].textContent = quant[i].value*caixas;
			calc_quant_total += parseInt(quant[i].value*caixas);
		}
		quant_total.textContent = calc_quant_total;
		valor_total.textContent = "R$ "+(preco_unit.value*calc_quant_total).toFixed(2);
}

document.addEventListener("onchange",function(event){
	event.preencheGradeResumo();
});

preencheGradeResumo();

$(".estoque-pares").each(function (index) {
	var pares = $(this).text();
	var tamanho = $(this).parent().parent().parent().find(".estoque-tamanho").text();
	pares = parseInt(pares);

	$(".reservado-pares").each(function(){
			var tReservado = $(this).parent().parent().parent().find(".reservado-tamanho").text();
			if(tReservado==tamanho){
				pares = pares-parseInt($(this).text());
			}
	});

	$(".previsao-pares").each(function(){
			var tReservado = $(this).parent().parent().parent().find(".previsao-tamanho").text();
			if(tReservado==tamanho){
				pares = pares+parseInt($(this).text());
			}
	});


  var campo1 = $("<kbd>").text(tamanho);
	var campo2 = $("<b>").text(pares);

	var div1 = $("<div>").append(campo1).css("text-align","center");
	if(pares<0){
		var div2 = $("<div>").css("border-radius","5px").css("background-color","red").css("color","white").css("text-align","center").append(campo2);
	}else{
		var div2 = $("<div>").append(campo2).css("text-align","center");
	}
	var coluna = $("<div>").addClass("col-sm-1");

	coluna.append(div1);
	coluna.append(div2);

	$("#saldo-grade").append(coluna);

});

