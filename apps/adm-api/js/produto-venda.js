
var tam_grade_produto = document.querySelectorAll("#tam_grade_produto");
var quant_produto = document.querySelectorAll("#quant_produto");

var valor_total_produto = document.querySelector("#valor_total_produto");
var quant_total_produto = document.querySelector("#quant_total_produto");
var preco = document.querySelector("#preco");

function preencheTotalProduto(){
  var calc_quant_total_produto = 0;

  for(var i=0;i<tam_grade_produto.length;i++){
    calc_quant_total_produto += parseInt(quant_produto[i].value);
  }
  quant_total_produto.textContent = calc_quant_total_produto;
  valor_total_produto.textContent = (calc_quant_total_produto*parseFloat(preco.textContent)).toFixed(2);
}

document.addEventListener("onchange",function(event){
	event.preencheTotalProduto();
});

preencheTotalProduto();

function myFunction(imgs) {
  // Get the expanded image
  var expandImg = document.getElementById("expandedImg");
  // Use the same src in the expanded image as the image being clicked on from the grid
  expandImg.src = imgs.src;
}

var modal = document.getElementById('myModal');

// Get the image and insert it inside the modal - use its "alt" text as a caption
var img = document.getElementById('expandedImg');
var modalImg = document.getElementById("img01");
var captionText = document.getElementById("caption");
img.onclick = function(){
    modal.style.display = "block";
    modalImg.src = this.src;
    captionText.innerHTML = this.alt;
}

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}
