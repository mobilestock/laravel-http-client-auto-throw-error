import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js"
var app = new Vue({
    el: "#menuClientes",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt"
        }
    }),
    data() {
        return {
            cabecalho: [
                this.itemGrade('Id', 'id'),
                this.itemGrade('Nome do cliente', 'razao_social'),
                this.itemGrade('Data da compra', 'data_primeira_compra', true),
                this.itemGrade('Valor da compra', 'valor_liquido', true)
            ],
            colaboradores: [],
        }
    },
    methods: {
        async buscaNovosClientes() {
            this.colaboradores = []
            await MobileStockApi('api_administracao/novos/clientes')
                .then(response => response.json())
                .then(json => {
                    this.colaboradores = json.data
                })
        },
        itemGrade(label, valor, ordenavel = false) {
            return {
                text: label,
                align: 'start',
                sortable: ordenavel,
                value: valor
            }
        }
    },
    async mounted() {
        await this.buscaNovosClientes()
    }
})