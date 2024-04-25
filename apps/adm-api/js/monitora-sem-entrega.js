import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

new Vue({
    el: "#monitoraSemEntregaVUE",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt",
        },
    }),
    data() {
        return {
            produtos: [],
            headerProdutos: [
                this.itemGrades("ID cliente", "id_cliente"),
                this.itemGrades("Nome cliente", "razao_social"),
                this.itemGrades("Tipo de frete", "tipo_frete"),
                this.itemGrades("Embalagem", "tipo_embalagem"),
            ],
            carregandoProdutos: false,
            pesquisa: "",
            snackbar: {
                ativar: false,
                cor: "",
                tempo: 5000,
                texto: "",
            },
        };
    },
    methods: {
        async buscaProdutosSemEntrega() {
            try {
                this.carregandoProdutos = true;

                const response = await MobileStockApi(
                    "api_administracao/produtos/busca/produtos_sem_entrega"
                ).then((resp) => resp.json());
                if (response?.message !== undefined) {
                    throw new Error(response.message);
                }

                this.produtos = response;
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregandoProdutos = false;
            }
        },
        itemGrades(
            campo,
            valor,
            ordernavel = false,
            estilizacao = "text-light grey darken-3"
        ) {
            return {
                text: campo,
                value: valor,
                sortable: ordernavel,
                class: estilizacao,
                align: "center",
            };
        },
        enqueueSnackbar(
            texto = "Erro, contate a equipe de T.I.",
            cor = "error",
            tempo = 5000
        ) {
            this.snackbar = {
                ativar: true,
                cor: cor,
                tempo: tempo,
                texto: texto,
            };
        },
    },
    mounted() {
        this.buscaProdutosSemEntrega();
    },
});
