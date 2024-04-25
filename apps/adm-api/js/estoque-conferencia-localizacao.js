var conferenciaEstoqueLocalizacao = new Vue({
  el: "#conferenciaEstoqueLocalizacaoVue",
  vuetify: new Vuetify(),
  data() {
    return {
      isLoading: false,
      isLoadingAnalise: false,
      localizacoes: [],
      codigosSelecionados: [],
      localizacao: null,
      localizacaoSelecionada: "",
      codigoDigitado: null,
      snackbar: {
        ativar: false,
        cor: "error",
        texto: "",
      },
      headersListaCodigos: [
        {
          text: "Remover da Lista",
          value: "acao",
          align: "center",
          sortable: false,
        },
        {
          text: "Código procurado",
          value: "codigo",
          align: "center",
        },
      ],
    };
  },
  methods: {
    async buscaLocalizacoes() {
      this.isLoading = true;
      try {
        await MobileStockApi("api_administracao/produtos/busca_localizacoes")
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.localizacoes = resp.data;
            } else {
              throw new Error(resp.message);
            }
          });
      } catch (error) {
        this.enqueueSnackbar(true, "error", error);
      } finally {
        this.isLoading = false;
      }
    },
    async analisarCodigos() {
      this.isLoadingAnalise = true;
      try {
        this.salvaCodigo("adicionar");
        if (this.codigosSelecionados.length < 1) {
          throw new Error("É necessário bipar um código de barras!");
        }
        const array = this.codigosSelecionados.map((item) => {
          return item.codigo;
        });
        await MobileStockApi("api_administracao/produtos/analisa_estoque", {
          method: "POST",
          body: JSON.stringify({
            local: this.localizacaoSelecionada,
            codigos: array,
          }),
        })
          .then((resp) => resp.json())
          .then(async (resp) => {
            if (resp.status) {
              await this.enqueueSnackbar(true, "success", resp.message);
              this.avanca();
            } else {
              throw new Error(resp.message);
            }
          });
      } catch (error) {
        this.enqueueSnackbar(true, "error", error);
        this.isLoadingAnalise = false;
      }
    },
    limpaLocal() {
      this.localizacao = "";
      this.localizacaoSelecionada = "";
      this.codigoDigitado = null;
      this.codigosSelecionados = [];
    },
    salvaCodigo(input) {
      if ((input.which === 13 || input.key === "Enter" || input === "adicionar") && this.codigoDigitado) {
        this.codigosSelecionados.push({
          acao: this.codigosSelecionados.length,
          codigo: this.codigoDigitado,
        });
        this.codigoDigitado = null;
      }
    },
    removeCodigo(item) {
      const index = this.codigosSelecionados.findIndex(
        (selecionado) =>
          selecionado.acao === item.acao && selecionado.codigo === item.codigo
      );
      this.codigosSelecionados.splice(index, 1);
    },
    avanca() {
      document.location.href = "estoque-resultado-analise.php";
    },
    enqueueSnackbar(ativar = false, cor = "error", texto = "Erro") {
      this.snackbar = { ativar, cor, texto };
    },
  },
  mounted() {
    this.buscaLocalizacoes();
  },
});
