$(".troca-pendente").on("change",".defeito",function(){
	if($(this).prop("checked")==true){
	  $(this).parent().parent().find(".descricao-defeito").show();
	} else {
      $(this).parent().parent().find(".descricao-defeito").hide();
	}
  });
  

    $('.simula-troca').click(function(){
        let existecampo =false;
        $(".troca-pendente").each(function(){
            let campoDescricao = $(this).find(".descricao-defeito-campo").val();
            let elemCampoDescricao = $(this).find(".descricao-defeito-campo").is( ":visible" );
            let chDefeito =  $(this).find(".defeito").is(':checked');
            if(campoDescricao=="" && elemCampoDescricao && chDefeito){
                existecampo=true;
                return false;
            }
        })
        if(existecampo==true){
            event.preventDefault();
            alert("Preencha todos os campos!");
        }
    }
    );
