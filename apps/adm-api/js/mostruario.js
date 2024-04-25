$("#imprimir_etiquetas").on("click",imprimirEtiquetas);

function imprimirEtiquetas(){
    event.preventDefault();
    var indice = 0;
    var quant = 0;

    $(".linha").each(function(){
        if($(this).find(".imprimir").is(":checked")){
            quant++;
        }
    });

    var linhas = $(".linha").length;

    var json = '[';
    if(quant>0){
        $(".linha").each(function(){
            if($(this).find(".imprimir").is(":checked")){

                indice++;

                var referencia = $(this).find(".referencia").val();
                var varejo_prazo = $(this).find(".varejo_prazo").val();
                var atacado_prazo = $(this).find(".atacado_prazo").val();
                var atacado_vista = $(this).find(".atacado_vista").val();

                json = json+'{"referencia":"'+referencia+'",';
                json = json+'"varejo_prazo":"'+varejo_prazo+'",';
                json = json+'"atacado_prazo":"'+atacado_prazo+'",';
                json = json+'"atacado_vista":"'+atacado_vista+'"}';
                json = json+',';
                json = json+'{"referencia":"'+referencia+'",';
                json = json+'"varejo_prazo":"'+varejo_prazo+'",';
                json = json+'"atacado_prazo":"'+atacado_prazo+'",';
                json = json+'"atacado_vista":"'+atacado_vista+'"}';

                if(indice<quant)
                    json = json+',';      
            }        
        });
    }else{
        $(".linha").each(function(){
            indice++;

            var referencia = $(this).find(".referencia").val();
            var varejo_prazo = $(this).find(".varejo_prazo").val();
            var atacado_prazo = $(this).find(".atacado_prazo").val();
            var atacado_vista = $(this).find(".atacado_vista").val();

            json = json+'{"referencia":"'+referencia+'",';
            json = json+'"varejo_prazo":"'+varejo_prazo+'",';
            json = json+'"atacado_prazo":"'+atacado_prazo+'",';
            json = json+'"atacado_vista":"'+atacado_vista+'"}';
            json = json+',';
            json = json+'{"referencia":"'+referencia+'",';
            json = json+'"varejo_prazo":"'+varejo_prazo+'",';
            json = json+'"atacado_prazo":"'+atacado_prazo+'",';
            json = json+'"atacado_vista":"'+atacado_vista+'"}';

            if(indice<linhas)
                json = json+',';      
                   
        });
    }
    

    json = json+']';
    var filename = "etiqueta_unitaria";
    var blob = new Blob([json], {type: "application/json;charset=utf-8"});
    saveAs(blob, filename+".json");
}