$('.formulario').on('submit', function (e) {
    $('.envio').attr('disabled', true).text('Enviando...');
}); 

$('remove-par').on('click',function(e){
    e.preventDefault();
    $(this).removeClass('remove-par');
    $(this).addClass('disabled').text('...');
});

$('adiciona-par').on('click',function(e){
    e.preventDefault();
    $(this).removeClass('adiciona-par');
    $(this).addClass('disabled').text('...');
});