<?php

use MobileStock\repository\FotosRepository;

require_once 'cabecalho.php';
acessoUsuarioFornecedor();
$fotos = FotosRepository::buscaFotosDeSugestoes();
?>

<div class="container-fluid">
<style>
.header {
  text-align: center;
  padding: 32px;
}
div.gallery {
  border: 1px solid #ccc;
}

div.gallery:hover {
  border: 1px solid #777;
}

div.gallery img {
  width: 100%;
  height: auto;
}

div.desc {
  padding: 15px;
  text-align: center;
}

* {
  box-sizing: border-box;
}

.responsive {
  padding: 0 6px;
  float: left;
  width: 24.99999%;
}

@media only screen and (max-width: 700px) {
  .responsive {
    width: 49.99999%;
    margin: 6px 0;
  }
}

@media only screen and (max-width: 500px) {
  .responsive {
    width: 100%;
  }
}

.clearfix:after {
  content: "";
  display: table;
  clear: both;
}
</style>
</head>
<body>

<!-- Header -->
<div class="header" id="myHeader">
  <h1>Sugestões de produtos</h1>
  <p>Veja os produtos que os clientes procuram encontrar no Mobile Stock</p>
</div>

<div class="container">
    <!-- Photo Grid -->
    <?php
        if (sizeof($fotos)>0) {
            foreach ($fotos as $key => $f) {
              ?>
              <div class="responsive">
                <div class="gallery">
                  <a target="_blank" href="<?=$f['foto_produto'];?>">
                    <img src="<?=$f['foto_produto'];?>" width="600" height="400"/>
                  </a>
                </div>
              </div>
              <?php
            }
        }
    ?>
</div>
<script>
// Get the elements with class="column"
var elements = document.getElementsByClassName("column");

// Declare a loop variable
var i;


for (i = 0; i < elements.length; i++) {
elements[i].style.msFlex = "25%";  // IE10
elements[i].style.flex = "25%";
}


// Add active class to the current button (highlight it)
var header = document.getElementById("myHeader");
var btns = header.getElementsByClassName("btn");
for (var i = 0; i < btns.length; i++) {
  btns[i].addEventListener("click", function() {
    var current = document.getElementsByClassName("active");
    current[0].className = current[0].className.replace(" active", "");
    this.className += " active";
  });
}
</script>
</div>

<?php
require_once 'rodape.php';