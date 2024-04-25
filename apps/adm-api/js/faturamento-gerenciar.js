$(".preco").on("change",atualizaValor);
$(".preco_devolucao").change(atualizaValorDevolucao);
$(".desconto").change(atualizaDescontoUnitario);
$("#frete").change(atualizaValorTotal);
$("#desconto_total").change(atualizaValorDescontoTotal);

$(document).on("ready",atualizaValor);

var faturamento = $("#faturamento").val();

function atualizaValor(){
	event.preventDefault();
	var preco = $(this).val();
	preco = parseFloat(preco);

	var id_produto = $(this).parent().parent().find(".id_produto").val();
	id_produto = parseInt(id_produto);
	var situacao = $(this).parent().parent().find(".situacao").val();
	situacao = parseInt(situacao);

	$.ajax({
	 type: "POST",
	 url: "controle/faturamento-pedido-atualiza-valor-unitario.php",
	 data: {
		 faturamento : faturamento,
 		 preco : preco,
		 id_produto : id_produto,
		 situacao : situacao
	 }
 }).done(function(data){
		$('body').html(data);
 });
}

function atualizaValorDevolucao(){
	event.preventDefault();
	var preco = $(this).val();
	preco = parseFloat(preco);

	var id_produto = $(this).parent().parent().find(".id_produto").val();
	id_produto = parseInt(id_produto);
	var situacao = $(this).parent().parent().find(".situacao").val();
	situacao = parseInt(situacao);

	$.ajax({
	 type: "POST",
	 url: "controle/faturamento-pedido-atualiza-valor-unitario-devolucao.php",
	 data: {
		 faturamento : faturamento,
 		 preco : preco,
		 id_produto : id_produto,
		 situacao : situacao
	 }
 }).done(function(data){
		$('body').html(data);
 });
}

function atualizaDescontoUnitario(){
	event.preventDefault();
	var desconto_unit = $(this).val();
	desconto_unit = parseFloat(desconto_unit);

	console.log(desconto_unit);
	
	var id_produto = $(this).parent().parent().find(".id_produto").val();
	id_produto = parseInt(id_produto);
	var situacao = $(this).parent().parent().find(".situacao").val();
	situacao = parseInt(situacao);
	var quantidade = $(this).parent().parent().find(".quantidade").text();
	quantidade = parseInt(quantidade);

	$.ajax({
	 type: "POST",
	 url: "controle/faturamento-pedido-atualiza-desconto-unitario.php",
	 data: {
		 faturamento : faturamento,
 		 desconto_unit : desconto_unit,
		 quantidade : quantidade,
		 id_produto : id_produto,
		 situacao : situacao
	 }
 }).done(function(data){
		$('body').html(data);
 });
}

function atualizaValorTotal(){

	var frete = $("#frete").val();
	frete = parseFloat(frete);
	$.ajax({
		 type: "POST",
		 url: "controle/faturamento-pedido-atualiza-valor-total.php",
		 data: {
			 faturamento : faturamento,
 			 frete : frete
		 }
	 }).done(function(data){
			$('body').html(data);
	 });
}

function atualizaValorDescontoTotal(){

		var pares=0;
		$(".quantidade").each(function(){
			 var parTemp = parseInt($(this).parent().parent().find(".quantidade").text());
			 pares = pares+parTemp;
		 });
		var desconto = $("#desconto_total").val();
		$.ajax({
	 		 type: "POST",
	 		 url: "controle/faturamento-pedido-atualiza-desconto-rateio.php",
	 		 data: {
	 			faturamento : faturamento,
	  		 	desconto : desconto,
				pares: pares
	 		 }
	 	}).done(function(data){
			 $('body').html(data);
		});

}