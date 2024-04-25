var estoqueResultadoAnalise = new Vue({
  el: "#estoqueResultadoAnaliseVue",
  vuetify: new Vuetify(),
  data() {
    return {
      isLoading: false,
      isLoadingMovimentacao: false,
      snackbar: {
        ativar: false,
        cor: "error",
        texto: "",
      },
      informacoesGerais: {
        localizacao: null,
        pares: 0,
      },
      listaProdutos: [],
      listaEtiquetas: [],
      headersProdutos: [
        {
          text: "ID do produto",
          value: "id_produto",
          align: "center",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Produto",
          value: "referencia",
          align: "center",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Tamanho",
          value: "nome_tamanho",
          align: "center",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Resultado",
          value: "descricao",
          align: "center",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Quantidade em estoque",
          value: "estoque",
          align: "center",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Etiqueta",
          value: "baixar_etiqueta",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Adicionar Estoque",
          value: "adicionar",
          sortable: false,
          class: "text-light grey darken-2",
        },
        {
          text: "Remover Estoque",
          value: "remover",
          sortable: false,
          class: "text-light grey darken-2",
        },
      ],
    };
  },
  methods: {
    async buscaDados() {
      this.isLoading = true;
      try {
        await MobileStockApi(
          "api_administracao/produtos/busca_resultado_analise"
        )
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.informacoesGerais = resp.data.geral;
              this.listaProdutos = resp.data.itens.map((item, index) => {
                return {
                  index,
                  ...item,
                };
              });
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
    async movimentaPar(produto, movimentacao) {
      if (this.isLoadingMovimentacao) return;
      this.isLoadingMovimentacao = true;
      try {
        switch (true) {
          case movimentacao === "A" && produto.tipo !== "PS":
            throw new Error("Não é possível adicionar esse produto");
          case movimentacao === "R" && (produto.tipo === "PS" || produto.estoque < 1):
            throw new Error("Não é possível remover esse produto");
          default:
            break;
        }
        await MobileStockApi(
          "api_administracao/produtos/movimenta_estoque_par",
          {
            method: "POST",
            body: JSON.stringify({
              id_produto: produto.id_produto,
              nome_tamanho: produto.nome_tamanho,
              sequencia: produto.sequencia,
              movimentacao: movimentacao,
            }),
          }
        )
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.enqueueSnackbar(
                true,
                "success",
                movimentacao === "A"
                  ? "Estoque adicionado com sucesso!"
                  : "Estoque removido com sucesso!"
              );
              window.location.reload();
            } else {
              throw new Error(resp.message);
            }
          });
      } catch (error) {
        this.enqueueSnackbar(true, "error", error);
      } finally {
        this.isLoadingMovimentacao = false;
      }
    },
    selecionarProduto(index) {
      const indexEtiqueta = this.listaEtiquetas.findIndex(
        (etiqueta) => etiqueta.index === index
      );
      if (indexEtiqueta >= 0) {
        this.listaEtiquetas.splice(indexEtiqueta, 1);
      } else {
        this.listaEtiquetas.push(
          this.listaProdutos.find((produto) => produto.index === index)
        );
      }
    },
    gerarEtiquetas(produtos = this.listaProdutos) {
      this.isLoading = true;
      try {
        if (!produtos.length) throw new Error("Nenhuma etiqueta encontrada");
        const listaEtiquetas = produtos
          .filter((produto) => produto.cod_barras.length)
          .map((produto) => {
            return {
              referencia: `${produto.id_produto} - ${produto.referencia}`
                .normalize("NFD")
                .replace(/([\u0300-\u036f])/g, ""),
              tamanho: produto.nome_tamanho,
              cod_barras: produto.cod_barras,
              localizacao: produto.localizacao,
              consumidor: "",
            };
          });
        if (listaEtiquetas.length) {
          const blob = new Blob([JSON.stringify(listaEtiquetas)], {
            type: "text/plain;charset=utf-8",
          });
          saveAs(
            blob,
            produtos.length === this.listaProdutos.length
              ? "etiquetas_completas_analise_estoque.json"
              : "etiquetas_parciais_analise_estoque.json"
          );
        } else {
          throw new Error("Nenhuma etiqueta encontrada");
        }
      } catch (error) {
        this.enqueueSnackbar(true, "error", error);
      } finally {
        this.isLoading = false;
      }
    },
    novaConferencia() {
      const ultimaPag = document.referrer.search(/estoque-conferencia-produtos.php/g);
      const telaPraRetorno = ultimaPag > 0 ? "estoque-conferencia-referencia.php" : "estoque-conferencia-localizacao.php";
      document.location.href = telaPraRetorno;
    },
    enqueueSnackbar(ativar = false, cor = "error", texto = "Erro") {
      this.snackbar = { ativar, cor, texto };
    },
  },
  mounted() {
    this.buscaDados();
  },
});
