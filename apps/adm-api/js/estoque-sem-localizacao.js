$(document).ready(function () {
  $("input[name='pesquisa']").focus();

  $(".modal_produto").on("click", function () {
    var local = $(this).attr("paramentro");
    var num_pares = $(this).attr("num_pares");
    var abre_cod = $(this).attr("data-target");
  });

  $("#gera_relatorio").on("click", function () {
    $("#abre_relatorio").html($("#carrega_").html());
    $.get("carrega_relatorio_estoque.php", { item: 1 }).done(function (data) {
      $("#abre_relatorio").html(data);
    });
  });

  $("#gera_lista_local").on("click", function () {
    $("#abre_lista_local").html($("#carrega_").html());
    $.get("carrega_relatorio_estoque.php", { item: 2 }).done(function (data) {
      $("#abre_lista_local").html(data);
    });
  });

  $("#gera_lista_log").on("click", function () {
    $("#abre_lista_log").html($("#carrega_").html());
    var pesquisa = $("input[id='pesquisa_log']").val();
    $.get("carrega_relatorio_estoque.php", {
      item: 3,
      pesquisa: pesquisa,
    }).done(function (data) {
      $("#abre_lista_log").html(data);
    });
  });

  $(".pesquisa").on("keyup", function () {
    var cont = 0;
    var value = $(this).val().toLowerCase();
    $(".itens .corpo").filter(function () {
      if ($(this).text().toLowerCase().indexOf(value) > -1) {
        cont++;
      }
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
    if (cont == 0) {
      $(".enviar").show();
    } else {
      $(".enviar").hide();
    }
  });

  let local = $(".select_local").val();
  if (local == undefined || local == "local" || local == 0) {
    $(".btn-salvar-mudanca").prop("disabled", true);
  }
});

$(".select_local").on("change", function () {
  let local = $(this).val();
  if (local != undefined && local != "local" && local != 0) {
    $(".btn-salvar-mudanca").prop("disabled", false);
  } else {
    $(".btn-salvar-mudanca").prop("disabled", true);
  }
});

$(".myInput").on("keyup", function () {
  id = $(this).attr("id");
  var value = $(this).val().toUpperCase();
  $(`.select_local[id=${id}]`).val(
    $(`.select_local[id=${id}] option:contains(${value})`).val()
  );
  local = $(`.select_local[id=${id}]`).val();
  if (local != undefined && local != "local" && local != 0) {
    $(".btn-salvar-mudanca").prop("disabled", false);
  } else {
    $(".btn-salvar-mudanca").prop("disabled", true);
  }
});

$(document).on("click", ".imprimir-etiqueta", imprimirEtiquetasGrade);
function imprimirEtiquetasGrade() {
  event.preventDefault();
  var etiquetas = $(".etiqueta_avulsa");
  var json = "[";
  $.each(etiquetas, function () {
    if (json != "[") {
      json = json + "," + $(this).attr("parjson");
    } else {
      json = json + $(this).attr("parjson");
    }
  });

  json = json + "]";
  var filename = "etiqueta_estante";
  var blob = new Blob([json], { type: "json" });
  saveAs(blob, filename + ".json");
}

$(document).on("click", ".etiqueta_avulsa", function () {
  event.preventDefault();
  var json = "[" + $(this).attr("parjson") + "]";
  var filename = "etiqueta_unitaria" + $(this).text();
  var blob = new Blob([json], { type: "json" });
  saveAs(blob, filename + ".json");
});
var listaTipos = [];

function marcaTodosTipoAtual(event, index, documento) {
  let tipo = event.target.parentNode.parentNode
    .querySelector(`#tipo_entrada_${index}_${documento}`)
    .textContent.split(" ")
    .join("_");
  let divTamanhos = event.target.parentNode.parentNode.parentNode.parentNode.querySelector(
    `#numeracoes_${index}_${documento}_${tipo}`
  );
  divTamanhos.querySelectorAll('input[type="checkbox"]').forEach((el) => {
    el.checked = event.target.checked;
    mudaNumeracoesSelecionadas({ target: el }, documento, false);
  });
}

function abrePainelNumeracao(event, index, documento) {
  let tipo = event.target.parentNode.parentNode
    .querySelector(`#tipo_entrada_${index}_${documento}`)
    .textContent.split(" ")
    .join("_");
  let divTamanhos = event.target.parentNode.parentNode.parentNode.parentNode.querySelector(
    `#numeracoes_${index}_${documento}_${tipo}`
  );
  divTamanhos.classList.contains("d-none")
    ? divTamanhos.classList.remove("d-none")
    : divTamanhos.classList.add("d-none");
}

function mudaNumeracoesSelecionadas(e, ehReativoComCheckBoxPai = true) {
  const inpNumeracoesSelecionadas = e.target.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.querySelector(
    'input[name="numeracoes_id"]'
  );
  let numeracoesSelecionadas = Array.prototype.slice
    .call(document.querySelectorAll(".checkbox-individual"))
    .filter((numeracao) => numeracao.checked === true)
    .map((numeracao) => numeracao.value);
  inpNumeracoesSelecionadas.value = numeracoesSelecionadas;

  let pillNumeracao = e.target.parentNode;

  if (e.target.checked) {
    pillNumeracao.classList.remove("border-dark");
    pillNumeracao.classList.add("border-primary");
    pillNumeracao.classList.add("text-primary");
  } else {
    pillNumeracao.classList.remove("border-primary");
    pillNumeracao.classList.add("border-dark");
    pillNumeracao.classList.remove("text-primary");
  }

  const tabelaAtual = e.target.parentNode.parentNode;

  if (ehReativoComCheckBoxPai === true) {
    let tudoSelecionado = !Array.prototype.slice
      .call(tabelaAtual.querySelectorAll('input[type="checkbox"]'))
      .some((item) => item.checked === false);

    tabelaAtual.parentNode.querySelector(
      'input[type="checkbox"]'
    ).checked = tudoSelecionado;
  }
}

// async function separarProdutoFoto(id, usuarioId, tamanho) {
//   await MobileStockApi("api_administracao/produtos/separar_produto_pra_foto", {
//     method: "POST",
//     body: JSON.stringify({
//       produtos: [
//         {
//           id_produto: id,
//           nome_tamanho: tamanho,
//         },
//       ],
//     }),
//   })
//     .then((resp) => resp.json())
//     .then((resp) => {
//       if (resp.status) {
//         let botaoSeparar = document.querySelector("#separarProdutoParaFoto");
//         botaoSeparar.innerHTML =
//           '<i class="fas fa-check-circle"></i> Produto separado com sucesso!';
//         botaoSeparar.disabled = true;
//       }
//     });
// }

function overlayModal() {
  document.querySelector(
    "#overlayModal"
  ).innerHTML = `<div class="v-overlay v-overlay--active theme--dark" style="z-index: 5;"><div class="v-overlay__scrim" style="opacity: 0.46; background-color: rgb(33, 33, 33); border-color: rgb(33, 33, 33);"></div><div class="v-overlay__content"><div role="progressbar" aria-valuemin="0" aria-valuemax="100" class="v-progress-circular v-progress-circular--indeterminate" style="height: 64px; width: 64px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="21.333333333333332 21.333333333333332 42.666666666666664 42.666666666666664" style="transform: rotate(0deg);"><circle fill="transparent" cx="42.666666666666664" cy="42.666666666666664" r="20" stroke-width="2.6666666666666665" stroke-dasharray="125.664" stroke-dashoffset="125.66370614359172px" class="v-progress-circular__overlay"></circle></svg><div class="v-progress-circular__info"></div></div></div></div>`;
}

var intervalSelecionarItemTamanho;
function seguraItemTamanho(e) {
  intervalSelecionarItemTamanho = setInterval(() => {
    let event = document.createEvent("Event");
    event.initEvent("build", true, true);

    [...e.target.children].forEach((pill) => {
      console.log(pill, e.pageX);
    });
  }, 100);

  e.target.addEventListener("build", (ev) => {
    console.log(ev.path);
    let pill = ev.path.find((pathItem) => {
      return pathItem.classList
        ? pathItem.classList.contains("pill-tamanho")
        : "";
    });
    console.log(pill);
    pill.querySelector('input[type="checkbox"]').checked = !pill.querySelector(
      'input[type="checkbox"]'
    ).checked;
    mudaNumeracoesSelecionadas({ target: pill });
  });
}

function soltaItemTamanho(e) {
  clearInterval(intervalSelecionarItemTamanho);
  console.log("soltando");
}
