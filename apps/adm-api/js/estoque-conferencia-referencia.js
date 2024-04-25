var conferenciaEstoqueReferencia = new Vue({
    el: "#conferenciaEstoqueReferenciaVue",
    vuetify: new Vuetify(),
    data() {
        return {
            pesquisa: document.location.search.match(/[0-9]+/),
            isLoading: false,
            listaProdutos: [],
            headerInformacoes: [
                {
                    text: "ID",
                    value: "id",
                    align: "center",
                    class: "text-uppercase text-light grey darken-2",
                },
                {
                    text: "Referência",
                    value: "descricao",
                    align: "center",
                    class: "text-uppercase text-light grey darken-2",
                },
                {
                    text: "Localização",
                    value: "localizacao",
                    align: "center",
                    class: "text-uppercase text-light grey darken-2",
                },
                {
                    text: "Mais Detalhes",
                    value: "acao",
                    align: "center",
                    class: "text-uppercase text-light grey darken-2",
                    sortable: false,
                },
            ],
            snackbar: {
                ativar: false,
                cor: "error",
                texto: "",
            },
        };
    },
    methods: {
        async buscaProduto(pesquisa = null) {
            if (pesquisa) {
                this.isLoading = true;
                try {
                    await MobileStockApi(
                        `api_administracao/produtos/busca_lista_produtos_conferencia_referencia?pesquisa=${pesquisa}`
                    )
                        .then((resp) => resp.json())
                        .then((resp) => {
                            if (resp.status) {
                                this.listaProdutos = resp.data;
                            } else {
                                throw new Error(resp.message);
                            }
                        });
                } catch (error) {
                    this.enqueueSnackbar(true, "error", error);
                } finally {
                    this.pesquisa = null;
                    this.isLoading = false;
                }
            }
        },
        pesquisaRapida(input) {
            if (input.which === 13 || input.key === "Enter") {
                this.buscaProduto(this.pesquisa);
            }
        },
        redireciona(item) {
            document.location.href = `estoque-conferencia-produtos.php?id=${item.id}`;
        },
        retornar() {
            document.location.href = "estoque-config.php";
        },
        enqueueSnackbar(ativar = false, cor = "error", texto = "Erro") {
            this.snackbar = { ativar, cor, texto };
        },
    },
    mounted() {
        if (this.pesquisa) this.pesquisa = this.pesquisa[0];
        const pesquisa = this.pesquisa;
        this.pesquisa = null;
        this.buscaProduto(pesquisa);
    },
});
