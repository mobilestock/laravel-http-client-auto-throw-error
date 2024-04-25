<?php

use Svg\Style;

require_once 'cabecalho.php';
acessoUsuarioFornecedor(); 
?>
<div class="container-fluid">
<h4>Dúvidas de produtos</h4>
<?php
if(sizeOf($duvidas) > 0){
    echo '
    <table class="table">
        <thead class="thead-dark">
            <tr>
            <th scope="col"></th>
            <th scope="col">Produto</th>
            <th scope="col">Pergunta</th>
            <th scope="col">Data</th>
            <th scope="col">Resposta</th>
            <th scope="col">Responder<br> ou editar</th>
            </tr>
        </thead>
    <tbody>';
    foreach ($duvidas as $key => $d):
    
        ?>
    
    <style>
        .icone{
            color: #343a40
        }
        .icone:hover{
   color: lightskyblue;
} 
        .resp:hover{
            color:lightskyblue;
        }
    
    </style>
        
        <tr>
            <td><img src="<?=$d['foto'];?>" width="78px"></img></td>
            <td><?=$d['nome_comercial'];?></td>
            <td><?=$d['pergunta'];?></td>
            <td><?=$d['data_pergunta'];?></td>
            <td><?=$d['resposta'];?></td>
            <td><?php if($d['resposta']==null){ ?><span style="font-size:24px">
            <i class="fas fa-clipboard-check resp" onclick="openModalResponder('<?=$d['id'];?>','<?=$d['pergunta'];?>')"></i></span><?php }else{ ?> 
                <span style="font-size:24px;color:lightgray">
                <br>
                    <i class="fas fa-clipboard-check"></i>
                    <i class="fas fa-edit icone" onclick="openModalEdit(
                        '<?=$d['id'];?>','<?=$d['pergunta'];?>'
                        )">
                        </i></span><?php }?></a></i></a>
                <span style="font-size:24px;color:black">
                </span>
             </td>
        </tr>
        <?php
    endforeach;
}
?>
</div>
<div class="modal fade" id="modalResponder" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Responder a pergunta sobre produto</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <span>Pergunta:</span>
        <strong><div id="divPergunta"></div></strong>
        <div><input type="text" id="resposta" class="form-control"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" onclick="responder()">Responder</button>
      </div>
    </div>
  </div>
</div>
<script>
    let idMsg = 0;
    function openModalResponder(id, pergunta){
        document.querySelector("#divPergunta").textContent=pergunta;
        $("#modalResponder").modal("show");
        idMsg = id;
    }
 
    function openModalEdit(id, pergunta){
        document.querySelector("#divPergunta").textContent=pergunta;
        $("#modalResponder").modal("show");
        idMsg = id;
    }

    function responder(){
        let resp = document.querySelector("#resposta").value;
        if(idMsg>0 && resp.length>2){
            var myHeaders = new Headers();
            var myInit = { method: 'POST',
                headers: myHeaders,
                body: JSON.stringify({id:idMsg,resposta:resp})
            };
            fetch('api_cliente/produto/faq/responde',myInit).then(res=>res.json()).then(res=>{
                if(res.status==true){
                    alert('Pergunta respondida com sucesso')
                    location.reload();
                }else{
                    alert('Erro ao responder a pergunta')
                }
            });
        }else{
            alert('Pergunta não localizada ou resposta com menos de 3 caracteres.')
        }
    }

    // function editarResposta(){
    //     let resp = document.querySelector("#divResposta").value;
    //     if(idMsg>0 && resp.length>2){
    //         var myHeaders = new Headers();
    //         var myInit = { method: 'GET',
    //             headers: myHeaders,
    //             body: JSON.stringify({id:idMsg,resposta:resp})
    //         };
    //         fetch('api_cliente/produto/faq/responde',myInit).then(res=>res.json()).then(res=>{
    //             if(res.status==true){
    //                 alert(' Sua resposta foi editada com sucesso!')
    //                 location.reload();
    //             }else{
    //                 alert('Ocorreu um erro ao editar sua resposta')
    //             }
    //         });
    //     }else{
    //         alert('Resposta não localizada ou alteração com menos de 3 caracteres.')
    //     }
    // }
</script>
<?php
 require_once 'rodape.php';
?>