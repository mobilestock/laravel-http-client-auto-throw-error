$(".liberar").on("click",function(){
    let btn = $(this);
    let id_separacao = $(this).parent().find(".separacao").val();
    $.ajax({
        type:"POST",
        url: "controle/liberar-separacao-pelo-faturamento.php",
        data: {id_separacao:id_separacao}
    }).done(function(){
        btn.remove();
    });
});