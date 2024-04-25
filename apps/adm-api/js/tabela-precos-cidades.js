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
            filtro: '',
            debounce_timer_cidade: null,

            listaDeCidades: [],
            headersListaDeCidades: [
                this.itemGrade('ID Cidade', 'id', 'left', '150px'),
                this.itemGrade('Cidade', 'cidade', 'left'),
                this.itemGrade('Bônus', 'valor_comissao_bonus', 'center', '150px'),
            ],

            modalNovaCidade: {
                id: 0,
                open: false,
                items: [],
                preco: 0,
            },
            inputModalCidade: '',

            snackbar: {
                mostrar: false,
                cor: "",
                texto: ""
            },
        };
    },

    watch: {
        inputModalCidade() {
            this.buscaCidades()
        }
    },
    methods: {
        itemGrade(label, valor, align = 'center', width = null) {
            return {
              text: label,
              align: align,
              value: valor,
              width: width
            }
        },
        async buscaPrecosCidades() {
            this.loading = true;
            await MobileStockApi("api_estoque/cidades_comissao/lista")
                .then(res => res.json())
                .then(json => this.listaDeCidades = json.data)
                .catch(error => {
                    this.snackbar.mostrar = true;
                    this.snackbar.cor = "error";
                    this.snackbar.texto = error;
                    this.dialog = !this.dialog;
                })
                .finally(() => this.loading = false);
        },
        async buscaCidades() {
            if (!this.inputModalCidade || this.inputModalCidade.trim().length <= 2 || this.loading) return
            if (this.debounce_timer_cidade) {
                clearTimeout(this.debounce_timer_cidade);
            }
            this.debounce_timer_cidade = setTimeout(async () => {
                this.loading = true;
                MobileStockApi(`api_administracao/cidades?pesquisa=${this.inputModalCidade.trim()}`)
                .then((res) => res.json())
                .then((json) => {
                    this.modalNovaCidade.items = json.data.map((cidade) => {
                        return {
                            text: `${cidade.nome} - ${cidade.uf}`,
                            value: cidade.id,
                        }
                    }).sort(function(a, b){
                        return b.text.localeCompare(a.text);
                    });
                })
                .catch(error => {
                    this.snackbar.mostrar = true;
                    this.snackbar.cor = "error";
                    this.snackbar.texto = error;
                    this.dialog = !this.dialog;
                })
                .finally(() => {
                    this.loading = false;
                })
            }, 500);
        },
        async alteraPrecoCidade(item) {
            if (this.debounce_timer_cidade) {
                clearTimeout(this.debounce_timer_cidade);
            }
            this.debounce_timer_cidade = setTimeout(async () => {
                this.loading = true 
                try {
                    const req = await MobileStockApi("api_estoque/cidades_comissao/muda_bonus", {
                        method: "POST",
                        body: JSON.stringify({
                            id_cidade: item.id_cidade,
                            preco: item.preco
                        }),
                    })
                    this.buscaPrecosCidades()
                    if (!req.ok) throw new Error(req.statusText)
                } catch (err) {
                    const msg = `Erro, ${err?.message || err}`
                    this.snackbar.texto = msg;
                    this.snackbar.mostrar = true;
                } finally {
                    this.loading = false
                }
            }, 500);
        },
        async alteraPrecoModal() {
            await this.alteraPrecoCidade({
                id_cidade: this.modalNovaCidade.id,
                preco: this.modalNovaCidade.preco
            })
            this.modalNovaCidade.id = 0
            this.modalNovaCidade.preco = 0
            this.modalNovaCidade.open = false
        },
    },

    mounted() {
        this.buscaPrecosCidades();
    }
});