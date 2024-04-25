import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

var entradaComprasVue = new Vue({
  el: "#entradaComprasVue",
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt'
    }
  }),
  data: ()=>{
    return {
    tipoModal: 1, // 1 - msg / 2 - detalhes / 3-imprimir
    msgModal: "",
    conteudoImpressao: {
      fornecedor: "",
      usuario: "",
      data: "",
      produto: [],
    },
    snackbar: {
      ativar: false,
      cor: "error",
      texto: "",
    },
    linhaParaRemover: "",
    inputBarCode: "",
    thead: [
      "Código de Barras",
      "Código da Compra",
      "Fornecedor",
      "Produto",
      "Quantidade"
    ],
    listaEntradas: [],
    listaEntradasErro: [],
    templistaEntradas: [],
    listaCodigoBarras: [],
    listaHistorico: [],
    tamanhoLista: 0,
    tamanhoFoto: null
  }
  },
  filters: {
    upperCase(value) {
      value = value.toString();
      return value.charAt(0).toUpperCase() + value.slice(1);
    },
    toBold(value) {
      value = value.toString();
      return value.to;
    },
  },
  mounted() {
    $("#modalAlerta").on(
      "hidden.bs.modal",
      function (e) {
        if (this.tipoModal == 3) {
          setTimeout(() => {
            this.listaEntradas = [];
            this.listaCodigoBarras = [];
            this.limpaModal();
            this.buscaHistoricoEntradaCompras();
          }, 1000);
        } else {
          this.limpaModal();
        }
      }.bind(this)
    );

    this.buscaHistoricoEntradaCompras();
  },
  directives: {
    focus: {
      inserted: function (el) {
        el.focus();
      },
      update: function (el) {
        el.focus();
      },
    },
  },
  computed: {
    quantidadeLida() {
      return this.listaEntradas.length;
    },
  },
  methods: {
    abreModal(index, target) {
      this.tipoModal = 2;
      this.msgModal = this.montaMensagem(target);
      this.linhaParaRemover = index;
      $("#modalAlerta").modal("show");
    },
    montaMensagem(objeto) {
      msg = {
        fornecedor: objeto[0].fornecedor,
        produto: objeto[0].produto,
        volumes: objeto[0].volume,
        pares: objeto[0].pares,
        grade: [],
      };
      $.each(objeto, function (index, produto) {
        msg.grade.push({
          tamanho: produto.tamanho,
          quantidade: produto.quantidade,
        });
      });
      return msg;
    },
    montaConteudoImpressao(listaEntrada, nestedArray) {
      if (listaEntrada.length == 0) return;
      if (nestedArray) listaEntrada = listaEntrada[0];
      this.tamanhoLista = listaEntrada.length;
      conteudo = {
        fornecedor: listaEntrada[0].fornecedor,
        usuario: cabecalhoVue.user.id,
        data: new Date().toLocaleString(),
        produto: [],
      };
      $.each(listaEntrada, function (index, objeto) {
        conteudo.produto.push({
          codigo_compra: objeto.cod_compra,
          produto: objeto.produto,
          quantidade: objeto.pares,
        });
      });
      this.tipoModal = 3;
      this.conteudoImpressao = conteudo;
    },
    removeLinha() {
      indexLinha = this.linhaParaRemover;
      this.listaEntradas.splice(indexLinha, 1);
      this.linhaParaRemover = "";
      $("#modalAlerta").modal("hide");
    },
    adicionaRegistro() {
      // Verifica se o codigo lido ja esta na lista
      if (this.validaCodigoDeBarras(this.inputBarCode)) {
        this.buscaDadosCompra(this.inputBarCode);
      }
    },
    buscaDadosCompra(codigoBarrasLeitor) {
      $("#input-codBarras").prop("disabled", true);
      MobileStockApi(`api_administracao/compras/busca_dados_por_codigo_barras/${codigoBarrasLeitor}`)
        .then((resp) => resp.json())
        .then((resp) => {
          if (resp.status) {
            if (this.validaFornecedor(resp)) {
              this.listaEntradas.unshift(resp.data);
              this.listaCodigoBarras.push(codigoBarrasLeitor);
            }
          } else {
            $("#modalAlerta").modal("show");
            this.msgModal = resp.message;
            $("#notificacao").trigger("play");
          }
          this.inputBarCode = "";
          $("#input-codBarras").prop("disabled", false);
          $("#input-codBarras").focus();
        });
    },
    enviar() {
      if (this.listaCodigoBarras.length > 0) {
        idUsuarioLogado = window.localStorage.getItem("idUsuarioLogado");

        MobileStockApi('api_administracao/compras/entrada?tamanho=' + this.tamanhoFoto, {
          body: JSON.stringify({
            codigos: this.listaCodigoBarras
          }),
          method: 'POST'
        }).then(res => res.json())
          .then(json => {
            if (json.status) {
              this.listaEntradasErro = json.entradas_com_erro;
              this.msgModal = "";
              this.montaConteudoImpressao(json.data.entradas);
              $("#modalAlerta").modal("show");
            }
            
            $("#conteudo-toast").empty();
            $("#conteudo-toast").append(json.message + "<br>");
            $(".toast").toast("show");
            
            this.inputBarCode = "";
            $("#input-codBarras").prop("disabled", false);
            $("#botao_enviar").prop("disabled", false);
          });

        // $.ajax({
        //   type: "POST",
        //   url: "controle/indexController.php",
        //   dataType: "json",
        //   data: {
        //     action: "entradaComprasMaisNotasFornecedor",
        //     idUsuarioLogado: idUsuarioLogado,
        //     listaCodigoBarras: this.listaCodigoBarras,
        //     tamanhoParaFoto : parseInt(this.tamanhoFoto)
        //   },
        //   beforeSend: function () {
        //     $("#input-codBarras").prop("disabled", true);
        //     $("#botao_enviar").prop("disabled", true);
        //   },
        // }).done(
        //   function (json) {
        //     if (json.status == "ok") {
        //       // this.listaEntradasErro = json.listaEntradasErro;
        //       $("#conteudo-toast").empty();
        //       if (json.mensagem == Array) {

        //         json.mensagem.forEach((mensagem) => {
        //           $("#conteudo-toast").append(mensagem + "<br>");
        //         });
        //       } else {
        //         $("#conteudo-toast").append(json.mensagem + "<br>");
        //       }
        //       $(".toast").toast("show");
        //       this.msgModal = "";
        //       this.montaConteudoImpressao(json.listaEntradas);
        //       $("#modalAlerta").modal("show");
        //     } else {
        //       $("#conteudo-toast").append(
        //         "Ocorreu um erro, aguarde um instante e tente enviar novamente!"
        //       );
        //       $(".toast").toast("show");
        //     }
        //     this.inputBarCode = "";
        //     $("#input-codBarras").prop("disabled", false);
        //     $("#botao_enviar").prop("disabled", false);
        //   }.bind(this)
        // );
      } else {
        $("#conteudo-toast").append("Nenhum código de barras encontrado!");
        $(".toast").toast("show");
      }
    },
    validaCodigoDeBarras(codigoBarrasLeitor) {
      for (const key in this.listaEntradas) {
        if (this.listaEntradas.hasOwnProperty(key)) {
          const lista = this.listaEntradas[key];
          for (const key in lista) {
            if (lista.hasOwnProperty(key)) {
              const element = lista[key];
              if (element["codigo_barras"] == codigoBarrasLeitor) {
                $("#notificacao").trigger("play");
                $("#modalAlerta").modal("show");
                this.msgModal = "Codigo ja inserido";
                this.inputBarCode = "";
                return false;
              }
            }
          }
        }
      }
      return true;
    },
    validaFornecedor(json) {
      for (const key in this.listaEntradas) {
        if (this.listaEntradas.hasOwnProperty(key)) {
          const lista = this.listaEntradas[key];
          for (const key in lista) {
            if (lista.hasOwnProperty(key)) {
              const element = lista[key];
              if (
                element["id_fornecedor"] !== json.data[0].id_fornecedor
              ) {
                $("#modalAlerta").modal("show");
                this.msgModal =
                  "Todos produtos precisam pertencer ao mesmo fornecedor";
                $("#notificacao").trigger("play");
                this.inputBarCode = "";
                return false;
              }
            }
          }
        }
      }
      return true;
    },
    printModal() {
      $(".printable").printThis({
        pageTitle: "Relatório de Entrega",
        importCSS: true,
        importStyle: true,
      });
    },
    async imprimirHistorico(index) {
      await MobileStockApi(
        "api_administracao/compras/busca_historico_dados_cod_barras",
        {
          method: "POST",
          body: JSON.stringify({
            codigos: this.listaHistorico[index].map((item) => {
              return {
                codigo_barras: item.codigo_barras,
              };
            }),
          }),
        }
      )
        .then((resp) => resp.json())
        .then((resp) => {
          if (resp.status) {
            if (this.validaFornecedor(resp)) {
              this.templistaEntradas.unshift(resp.data);
              this.montaConteudoImpressao(this.templistaEntradas, true);
              $("#modalAlerta").modal("show");
            }
          } else {
            $("#modalAlerta").modal("show");
            this.msgModal = resp.message;
            $("#notificacao").trigger("play");
          }
        });
    },
    limpaModal() {
      this.tipoModal = 1;
      this.msgModal = "";
      this.conteudoImpressao = {
        fornecedor: "",
        usuario: "",
        data: "",
        produto: [],
      };
    },
    async buscaHistoricoEntradaCompras() {
      try {
        await MobileStockApi(
          "api_administracao/compras/busca/ultimas_entradas_compra"
        )
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw new Error(resp.message);

            if (this.validaFornecedor(resp)) this.listaHistorico = resp.data;
          });
      } catch (erro) {
        this.enqueueSnackbar(erro);
      }
    },
    enqueueSnackbar(texto = "Erro, contate a equipe de T.I.", cor = "error") {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      };
    },
  },
});

jQuery(document).ready(function () {
  $("#alert-toast").on("hidden.bs.toast", function () {
    $("#conteudo-toast").empty();
  });
});
