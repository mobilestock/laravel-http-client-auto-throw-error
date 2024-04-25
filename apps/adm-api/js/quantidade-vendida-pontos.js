import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";
var app = new Vue({
    el: '#quantidadeEntregasPontos',
    vuetify: new Vuetify({
        lang: {
            locales: {
                pt
            },
            current: 'pt',
        },
    }),
    data() {
        return {
            cabecalho: [
                this.itemGrade('Ponto', 'nome'),
                this.itemGrade('Whatsapp', 'telefone'),
                this.itemGrade('Último mês', 'mes_passado', true),
                this.itemGrade('Mês atual', 'mes_atual', true)
            ],
            entregasPontos: [],
            loading: false,
            snackbar: {
                open: false,
                color: "error",
                message: "",
            }
        }
    },
    methods: {
        itemGrade(label, valor, ordenavel = false) {
            return {
                text: label,
                align: 'start',
                sortable: ordenavel,
                value: valor
            }
        },
        async buscaEntregasPontos() {
            this.loading = true
            await MobileStockApi('api_administracao/pontos_de_entrega/busca_valor_vendido_tipo_frete')
            .then ((resp) => resp.json())
            .then((json) => {
                if(!json.status) {
                    throw new Error (json.message)
                }
                this.entregasPontos = json.data;
            })
            .catch((err) => {
                this.snackbar.open = true
                this.snackbar.message = 
                    err?.message ||
                    err || 
                    "Não foi possível buscar as entregas dos pontos";
            }).finally(() => {
                this.loading = false;
            })
        },
        mensagemWhatsapp(telefone) {

            const mensagem = new MensagensWhatsApp({
                telefone: telefone
            }).resultado

            window.open(mensagem)
        }
    },
    mounted() {
        this.buscaEntregasPontos()
    }
});