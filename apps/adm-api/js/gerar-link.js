
function gerarLink(id) {
  /* Get the text field */
  var copyText = document.getElementById(id);

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/

  /* Copy the text inside the text field */
  document.execCommand("copy");

}

$(document).on('click', ".gerar_link", function(){
  var pagina = $(this).attr('pagina');
  var input = $(this).next();  
  var infoLink = '';
  if(pagina.lastIndexOf('index.php') != -1){
    var dadosArray = $("#form").serializeArray();
    $.each(dadosArray, function(index, value){
        if(value.value != ''){
            if(infoLink == ''){infoLink = "?"+value.name+"="+value.value;}
            else{infoLink = infoLink+"&"+value.name+"="+value.value;}
        }        
    })
  } 
  $(input).val(pagina+infoLink);  
  $(input).select(); 
  document.execCommand("copy");       
});