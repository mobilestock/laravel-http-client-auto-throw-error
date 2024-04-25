import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

var app = new Vue({
    el: "#app",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt",
        },
    }),

    data() {
        return {
            loading: false,
            quantidade_vendas: 0,
            valor_vendas: 0,
            snackbar: {
                mostra: false,
                texto: '',
            },
            lista_mais_vendidos: [],
            lista_mais_vendidos_headers: [
                { text: 'ID', value: 'id' },
                { text: 'Referencia', value: 'descricao' },
                { text: 'Fornecedor', value: 'razao_social' },
                { text: 'Pares', value: 'pares' },
                { text: 'Valor Total', value: 'valor' },
                { text: 'Média Preço', value: 'preco_medio' },
                { text: 'Custo', value: 'custo' },
                { text: 'Custo Total', value: 'custo_total' }
            ],
            anos_select: [2021, 2022, 2023, 2024, 2025, 2026, 2027, 2028, 2029, 2030],
            meses_select: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            ano_default: new Date().getFullYear(),
            mes_default: new Date().getMonth() + 1,
        }
    },

    methods: {
        formatarNumero(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        async buscaDados() {
            this.loading = true;
            MobileStockApi(`api_administracao/produtos/mais_vendidos?ano=${this.ano_default}&mes=${this.mes_default}`)
                .then(res => res.json())
                .then(json => {
                    this.lista_mais_vendidos = json.data.lista_mais_vendidos || [];
                    this.quantidade_vendas = json.data.vendas.quantidade || -1;
                    this.valor_vendas = json.data.vendas.valor || -1;
                })
                .catch(error => {
                    this.lista_mais_vendidos = [];
                    this.quantidade_vendas = -1;
                    this.valor_vendas = -1;
                    this.snackbar.texto = 'Ocorreu um erro ao buscar os dados!';
                    this.snackbar.mostra = true;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },

    mounted() {
        this.buscaDados();
    },
});