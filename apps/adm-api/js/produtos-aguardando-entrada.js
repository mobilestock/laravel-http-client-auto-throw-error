Vue.component("renderiza-compras", {
  template: "#renderiza-compras",
  props: ["compras"],
});

var produtosAguardandoEntradaVUE = new Vue({
  el: "#produtosAguardandoEntrada",
  vuetify: new Vuetify(),
  data() {
    return {
      overlay: false,
      procurarProduto: "",
      listaProdutosAguardandoEntrada: [],
      orderBy: "",
      orderBydesc: false,
      listaFotosSeparar: [],
      listaTrocas: [],
      listaCompras: [],
      listaProdutos: [],
      listaPedidosCancelados: [],
      listaDevolucoesFotos: [],
      paginacao: 1,
      dialogVoltarCompras: false,
      conteudoModal: "",
      situacaoModal: 1,
      loadingModal: false,
      snackbar: {
        mostrar: false,
        cor: "error",
        texto: "",
      },
      totalProdutoAtual: 0,
      dialogVoltarEstoque: false,
      conteudoModalVoltarEstoque: [],
      situacaoModalVoltarEstoque: 1,
      validadoModalEstoque: false,
      idProdutoSelecionado: 0,
    };
  },
  methods: {
    async buscaEntradasAguardandoEntrada() {
      this.listaCompras = [];
      this.listaFotosSeparar = [];
      this.listaPedidosCancelados = [];
      this.listaDevolucoesFotos = [];
      this.listaProdutos = [];
      this.listaProdutosAguardandoEntrada = [];

      this.overlay = true;
      await MobileStockApi(
        "api_administracao/produtos/busca_entradas_aguardando"
      )
        .then((resp) => resp.json())
        .then((resp) => {
          this.overlay = false;
          const produtos = resp.data;

          Object.values(produtos).map((item)=>{

            const tipos = item.tipo_entrada?.split(",");

            if (tipos !== undefined) {

              tipos.map((tipo)=>{
                switch (tipo) {
                  case "Compra":
                    this.listaCompras.push(item);
                  break;
                  case "Separar para foto":
                    this.listaFotosSeparar.push(item);
                  break;
                  case "Pedido cancelado":
                    item.devolucao = true;
                    this.listaPedidosCancelados.push(item);
                  break;
                  case "Foto":
                    item.devolucao = true;
                    this.listaDevolucoesFotos.push(item);
                  break;
                }
              })

            }

            this.listaProdutosAguardandoEntrada.push(item);
            this.listaProdutos.push(item);

          })

        });
    },

    classeTabela(item) {
      if (item.tipo_entrada === "FT") {
        classe = "bg-secondary theme--dark";
      } else {
        classe = "bg-danger theme--dark";
      }

      return classe;
    },

    // async separarItem(item) {
    //   this.overlay = true;

    //   await MobileStockApi(
    //     "api_administracao/produtos/separar_produto_pra_foto",
    //     {
    //       method: "POST",
    //       body: JSON.stringify({
    //         produtos: [
    //           {
    //             id_produto: item.id_produto,
    //             nome_tamanho: item.tamanho,
    //           },
    //         ],
    //       }),
    //     }
    //   )
    //     .then((resp) => resp.json())
    //     .then((resp) => {
    //       $.dialog({
    //         title: !resp.status ? "Erro!" : "Sucesso!",
    //         type: !resp.status ? "red" : "blue",
    //         content: resp.message,
    //       });

    //       this.listaProdutosAguardandoEntrada = [];
    //       this.listaCompras = [];
    //       this.listaFotosSeparar = [];
    //       this.listaPedidosCancelados = [];
    //       this.listaProdutos = [];
    //       this.listaDevolucoesFotos = [];
    //       this.buscaEntradasAguardandoEntrada();
    //       this.overlay = false;
    //     });
    // },

    // async abreModalVoltarCompras(item) {
    //   this.dialogVoltarCompras = !this.dialogVoltarCompras;

    //   this.totalProdutoAtual = this.listaProdutos
    //     .filter((el) => el.id_produto == item.id_produto)
    //     .reduce((total, item) => (total += parseInt(item.qtd)), 0);

    //   let json = await fetch(
    //     `src/controller/VoltarComprasController.php?id_produto=${item.id_produto}`
    //   ).then((r) => r.json());

    //   this.conteudoModal = json.data;
    // },

    // async confirmarVoltaCaixasCompras() {
    //   this.loadingModal = true;
    //   let form = new FormData();
    //   form.append(
    //     "codigos",
    //     JSON.stringify(
    //       this.conteudoModal
    //         .map((el) => el.caixas.filter((item) => item.voltar))
    //         .map((el) => el.map((item) => item.cod_barras))
    //         .reduce((total, item) => total.concat(item), [])
    //     )
    //   );

    //   let json = await fetch("src/controller/VoltarComprasController.php", {
    //     method: "POST",
    //     body: form,
    //   })
    //     .then((r) => r.json())
    //     .catch((err) => {
    //       this.loadingModal = false;
    //       this.snackbar = {
    //         mostrar: true,
    //         cor: "error",
    //         texto: "Erro ao voltar",
    //       };
    //     });
    //   this.loadingModal = false;

    //   this.snackbar = {
    //     mostrar: true,
    //     cor: json.status === true ? "primary" : "error",
    //     texto: json.message,
    //   };
    //   if (json.status) {
    //     this.dialogVoltarCompras = false;
    //   }
    //   this.buscaEntradasAguardandoEntrada();
    // },

    // async abreModalVoltarEstoque(item) {
    //   this.dialogVoltarEstoque = !this.dialogVoltarEstoque;
    //   this.idProdutoSelecionado = item.id_produto;

    //   let json = await fetch(
    //     `src/controller/VoltarComprasController.php?id_produto=${item.id_produto}&buscarTodas=true`
    //   ).then((r) => r.json());

    //   this.conteudoModalVoltarEstoque = json.data;
    // },

    // async confirmaProdutosEstoqueDevolvidosCompra() {
    //   let form = new FormData();

    //   form.append("id_produto", this.idProdutoSelecionado);
    //   form.append(
    //     "id_compra",
    //     this.conteudoModalVoltarEstoque
    //       .map((el) => el.caixas.filter((item) => item.voltar))
    //       .map((el) => el.map((item) => item.id_compra))
    //       .reduce((total, item) => total.concat(item), [])[0]
    //   );
    //   form.append(
    //     "grade",
    //     JSON.stringify(
    //       this.gradesEstoqueSelecionadas.map((grade) =>
    //         grade.grade.map((el) => {
    //           return { tamanho: el.tamanho, qtd: el.qtd_removida };
    //         })
    //       )[0]
    //     )
    //   );

    //   let json = await fetch(
    //     `src/controller/VoltarComprasController.php?_method=PUT`,
    //     {
    //       method: "POST",
    //       body: form,
    //     }
    //   );

    //   this.dialogVoltarEstoque = false;
    //   if (json.status === true) {
    //     this.snackbar = {
    //       mostrar: true,
    //       cor: "primary",
    //       texto: json.message,
    //     };
    //   }
    // },
  },
  computed: {
    gradesComprasSelecionadas() {
      return (
        this.conteudoModal
          .filter((el) => el.caixas.filter((item) => item.voltar).length)
          .map((el) => {
            return {
              grade: el.grade.map((item) => {
                item.qtd =
                  item.quantidade *
                  el.caixas.filter((item) => item.voltar).length;
                return item;
              }),
              id: el.id,
            };
          }) || []
      );
    },

    gradesEstoqueSelecionadas() {
      return (
        this.conteudoModalVoltarEstoque
          .filter((el) => el.caixas.filter((item) => item.voltar).length)
          .map((el) => {
            return {
              grade: el.grade.map((item) => {
                item.qtd =
                  item.quantidade *
                  el.caixas.filter((item) => item.voltar).length;
                return item;
              }),
              id: el.id,
            };
          }) || []
      );
    },
  },
  filters: {
    formataData(value) {
      if (value) {
        var data = new Date(value),
          dia = data.getDate().toString(),
          diaF = dia.length == 1 ? "0" + dia : dia,
          mes = (data.getMonth() + 1).toString(), //+1 pois no getMonth Janeiro começa com zero.
          mesF = mes.length == 1 ? "0" + mes : mes,
          anoF = data.getFullYear(),
          horasF = data.getHours(),
          minF = data.getMinutes();

        return diaF + "/" + mesF + "/" + anoF;
      }
    },
  },
  mounted() {
    cod_barras = document.querySelector("#inputCod_barras").value;

    document.querySelector("#inputCod_barras").value = "";
    window.history.pushState("", "", window.location.href);
    this.buscaEntradasAguardandoEntrada();
    if (cod_barras) this.procurarProduto = cod_barras.trim();
  },
  watch: {
    dialogVoltarCompras(newV) {
      if (newV === false) {
        this.situacaoModal = 1;
        this.conteudoModal = [];
      }
    },
  },
});
