$('.site-header').hide()

    var contE = 0;
    var contS = 0;

    $('.email').keypress(function () {
        $(this).removeClass('valido');
        $(this).removeClass('invalido');
        contE++;
        if (contE > 10) {
            $(this).addClass('valido')
        } else {
            $(this).addClass('invalido')
        }
    })
    $('.senha').keypress(function () {
        $(this).removeClass('valido');
        $(this).removeClass('invalido');
        contS++;
        if (contS >= 4) {
            $(this).addClass('valido')
        } else {
            $(this).addClass('invalido')
        }
    })
