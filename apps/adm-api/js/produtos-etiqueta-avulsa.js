$(".imprimir-etiqueta-avulsa").on("click",imprimirEtiquetas);

function imprimirEtiquetas(){
  event.preventDefault();
  var qte = $(this).parent().parent().find("#qte_etiqueta_avulsa").val();
  console.log(qte);
  var etiqueta = $(this).parent().parent().find(".etiqueta_avulsa").val();
  console.log(etiqueta);
  var nome_tiqueta = $(this).parent().parent().find(".nome_etiqueta").val();
  if(qte=='' || qte==0){
    alert('Informe alguma quantidade de etiqueta.');
  }else{
    var json = '[';
      for(var i=1;i<=qte;i++){
        json = json+etiqueta;
        if(i<qte){
          json = json+',';
        }
      }
    json = json+']';
    var filename = "etiqueta_unitaria_"+nome_tiqueta;
    var blob = new Blob([json], {type: "json"});
    saveAs(blob, filename+".json");
  }
}


$(".imprimir-grade-etiqueta_avulsa").on("click",imprimirEtiquetasGrade);
function imprimirEtiquetasGrade(){
  event.preventDefault();
  var etiquetas = $(".etiqueta_avulsa");
  console.log(etiquetas);
  var lnome_arquivo = $('#nome_arquivo').text();
  var json = '[';
    $.each(etiquetas,function(){
      if($(this).is(":checked") == true){
        /*var qte = $(this).parent().parent().find("#qte_etiqueta_avulsa").val();
        if(qte>0){
          for(var i=1;i<=qte;i++){*/
            if(json != '['){
              json = json+','+$(this).val();
            }else{
              json = json+$(this).val();
            }
          /*  if(i<qte){
              json = json+',';
            }
          }
        }*/
      }
    });

  json = json+']';
  var filename = "etiqueta_unitaria_grade"+lnome_arquivo;
  var blob = new Blob([json], {type: "json"});
  saveAs(blob, filename+".json");
}

$(document).on('click','.marcar_checkbox', function(){
  var cod = $(this).attr('id').replace(/_/g, "");
  if(cod == 'TD'){
    if($(this).is(":checked") == true){
      $(".etiqueta_avulsa").each(function() { this.checked = true; });
    }
    else{
      $("input[type=checkbox]").each(function() { this.checked = false; });      
    }
  }else{
    if($(this).is(":checked") == true){
      $("."+cod).each(function() { this.checked = true; });
    }
    else{
      $("."+cod).each(function() { this.checked = false; });      
    }
  }
})