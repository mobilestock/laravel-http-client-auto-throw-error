var cartoes = {
  Amex: /^(?:3[47][0-9]{13})$/,
  Diners: /^(?:3(?:0[0-5]|[68][0-9])[0-9]{11})$/,
  Elo: /^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/,
  Discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
  Hipercard: /^(606282\d{10}(\d{3})?)|(3841\d{15})/,
  JCB: /^(?:(?:2131|1800|35\d{3})\d{11})$/,
  Master: /^(?:5[1-5][0-9]{14})$/,
  Visa: /^(?:4[0-9]{12}(?:[0-9]{3})?)$/,
};

var symbol = {
  Amex: '<i class="fab fa-cc-amex h4"></i>',
  Diners: '<i class="fab fa-cc-diners-club h4"></i>',
  Elo: '<i class="fab fa-ello h4"></i>',
  Discover: '<i class="fab fa-cc-discover"></i>',
  Hipercard: '<i class="fas fa-credit-card h4"></i>',
  JCB: '<i class="fab fa-cc-jcb h4"></i>',
  Master: '<i class="fab fa-cc-mastercard h4"></i>',
  Visa: '<i class="fab fa-cc-visa h4"></i>',
}

function testarCC(nr, cartoes) {
  for (var cartao in cartoes) if (nr.match(cartoes[cartao])) return cartao;
  return false;
}

function retornaBrand(brand, symbol){
  for(var s in symbol) if (brand==s) return symbol[s];
  return false;
}

$("#cc-number").on("keyup", validaCartao);

function validaCartao() {
  var number = $(this)
    .val()
    .replace(/([^\d])+/gim, "");
  if (number.length > 0) {
    var cartao = testarCC(number, cartoes);
    var brand = retornaBrand(cartao, symbol);
    if (cartao == false) {
      $("#cc-number").val();
      $("#bandeira").val(0);
      $("#label-bandeira").text(" ");
    } else {
      $("#label-bandeira").html("");
      $("#label-bandeira").append(brand);
      $("#bandeira").val(cartao);
    }
  } else {
    $("#label-bandeira").text(" ");
    $("#bandeira").val(0);
  }
}

$('[data-toggle="popover"]').popover({
    //trigger: 'focus',
    trigger: "hover",
    html: true,
    content: function () {
      return '<img width="250px" src="' + $(this).data("img") + '" />';
    },
  });