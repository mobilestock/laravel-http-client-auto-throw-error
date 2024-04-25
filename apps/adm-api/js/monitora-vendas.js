import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

const monitoraVendasProdutosExternos = new Vue({
    el: "#monitoraVendasProdutosExternosVUE",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt",
        },
    }),
    data() {
        return {
            isLoading: false,
            pesquisa: "",
            produtos: [],
            qtdProdutos: 0,
            filtros: {
                menuData: false,
                pagina: 1,
                data: "",
            },
            modalQr: {
                ativar: false,
                nome: "",
                reputacao: "",
                codigo: "",
            },
            snackbar: {
                ativar: false,
                texto: "",
                cor: "",
            },
            headerProdutos: [
                this.itemGrades("ID", "id", true),
                this.itemGrades("ID produto", "id_produto"),
                this.itemGrades("Produto", "foto_produto"),
                this.itemGrades("Situação atual", "situacao"),
                this.itemGrades("Preço", "preco"),
                this.itemGrades("Cliente", "cliente.nome"),
                this.itemGrades("Seller", "fornecedor.nome"),
                this.itemGrades("Data Liberação", "data_liberacao"),
                this.itemGrades("Data Validade", "data_validade"),
            ],
        };
    },
    methods: {
        async buscaProdutos() {
            this.isLoading = true;

            try {
                this.produtos = [];

                const parametros = new URLSearchParams({
                    pagina: this.filtros.pagina || 1,
                    data: this.filtros.data || "",
                });

                await MobileStockApi(
                    `api_administracao/estoque_externo/busca/monitoramento_vendidos?${parametros}`
                )
                    .then((resp) => resp.json())
                    .then((resp) => {
                        if (!resp.status) throw new Error(resp.message);

                        this.produtos = resp.data.produtos;
                        this.qtdProdutos = resp.data.qtd_produtos;
                    });
            } catch (erro) {
                this.enqueueSnackbar(erro);
            } finally {
                this.isLoading = false;
            }
        },
        converteData(data) {
            if (!data) return "";

            const dataFormatada = data
                .toString()
                .substring(0, 10)
                .split("-")
                .reverse()
                .join("/");

            return dataFormatada;
        },
        ativarModalQrCode(item) {
            this.modalQr = {
                ativar: true,
                nome: item.nome,
                reputacao: item.reputacao_atual || "",
                codigo: item.telefone,
            };

            return;
        },
        corPorReputacao(reputacao = "NOVATO") {
            switch (reputacao) {
                case "RUIM":
                    return "error";
                case "REGULAR":
                    return "amber";
                case "EXCELENTE":
                    return "success";
                case "MELHOR_FABRICANTE":
                    return "primary";
                default:
                    return;
            }
        },
        converteValorEmReais(valor = 0) {
            const reais = valor.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
            });

            return reais;
        },
        itemGrades(
            campo,
            valor,
            ordernavel = false,
            classe = "text-light grey darken-3"
        ) {
            return {
                text: campo,
                value: valor,
                sortable: ordernavel,
                align: "center",
                class: classe,
            };
        },
        enqueueSnackbar(texto = "Erro, contate a equipe de T.I.", cor = "error") {
            this.snackbar = {
                ativar: true,
                texto: texto,
                cor: cor,
            };
        },
    },
    computed: {
        textoFiltroData() {
            const texto = this.filtros.data
                ? `Filtrando data: ${this.converteData(this.filtros.data)}`
                : "Selecionar data";

            return texto;
        },
    },
    mounted() {
        this.buscaProdutos();
    },
    watch: {
        "filtros.pagina"(valor) {
            this.filtros = {
                menuData: false,
                pagina: valor < 1 ? 1 : valor,
                data: "",
            };
            this.buscaProdutos();
        },
        "filtros.data"(valor) {
            this.filtros = {
                menuData: false,
                pagina: 1,
                data: valor,
            };
            this.buscaProdutos();
        },
    },
});
