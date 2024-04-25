import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";
new Vue({
    el: "#pontuacaoprodutos",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt"
        }
    }),
    data() {
        return {
            cabecalho: [
                this.itemGrade('Foto', 'foto'),
                this.itemGrade('Produto', 'produto'),
                this.itemGrade('Avaliações', 'pontuacao_avaliacoes', true),
                this.itemGrade('Seller', 'pontuacao_seller', true),
                this.itemGrade('Fullfillment', 'pontuacao_fullfillment', true),
                this.itemGrade('Vendas', 'quantidade_vendas', true),
                this.itemGrade('Devolução', 'pontuacao_devolucao_normal', true),
                this.itemGrade('Defeito', 'pontuacao_devolucao_defeito', true),
                this.itemGrade('Cancelamento', 'cancelamento_automatico', true),
                this.itemGrade('Total', 'total', true),
                this.itemGrade('Total Normalizado', 'total_normalizado', true)
            ],
            produtos: [],
            urlBaseMeulook: '',
            pesquisa: '',
            carregando: false,
            timer: null,
            pagina: 1,
            snack: {
                mensagem: '',
                mostrar: false
            },
            pontuacoes: {
                ATRASO_SEPARACAO: 0,
                AVALIACAO_4_ESTRELAS: 0,
                AVALIACAO_5_ESTRELAS: 0,
                CANCELAMENTO_AUTOMATICO: 0,
                DEVOLUCAO_DEFEITO: 0,
                DEVOLUCAO_NORMAL: 0,
                POSSUI_FULLFILLMENT: 0,
                REPUTACAO_EXCELENTE: 0,
                REPUTACAO_MELHOR_FABRICANTE: 0,
                REPUTACAO_REGULAR: 0,
                REPUTACAO_RUIM: 0,
                PONTUACAO_VENDA: 0
            },
            ultimaPagina: false,
            mostrarTodosSellers: false,
            dialog: {
                mostrar: false
            }
        }
    },
    watch: {
        pesquisa() {
            clearTimeout(this.timer)
            this.timer = setTimeout(async () => this.resetar(), 750)
        },
        mostrarTodosSellers() {
            this.resetar()
        }
    },
    methods: {
        buscaExplicacoesPontuacaoProduto() {
            MobileStockApi('api_administracao/produtos/busca_explicacoes_pontuacao_produtos')
            .then(async response => await response.json())
            .then(json => {
                if (!json.status) throw new Error(json.message)
                this.pontuacoes = json.data
            })
            .catch(error => {
                this.snack.mensagem = error.message || 'Erro ao carregar explicações'
                this.snack.mostrar = true
            })
        },
        buscaListaPontuacoes() {
            if (this.carregando) return
            this.carregando = true
            MobileStockApi(
                `api_administracao/produtos/busca_lista_pontuacoes?pesquisa=${this.pesquisa}&pagina=${this.pagina}&listar_todos=${this.mostrarTodosSellers}`
            )
                .then(async response => await response.json())
                .then(json => {
                    if (!json.status) throw new Error(json.message)
                    if (!json.data?.length) return this.ultimaPagina = true
                    this.produtos = this.produtos.concat(json.data)
                    this.pagina += 1
                })
                .catch(error => {
                    this.snack.mensagem = error.message || 'Erro ao carregar produtos'
                    this.snack.mostrar = true
                })
                .finally(() => this.carregando = false)
        },
        buscaUrlMeulook() {
            const element = document.getElementsByName('url-meulook')[0]
            this.urlBaseMeulook = element.value
        },
        itemGrade(label, valor, ordenavel = false) {
            return {
                text: label,
                align: 'start',
                sortable: ordenavel,
                value: valor
            }
        },
        onIntersect() {
            this.buscaListaPontuacoes()
        },
        resetar() {
            this.pagina = 1
            this.produtos = []
            this.ultimaPagina = false
            this.buscaListaPontuacoes()
        }
    },
    filters: {
        pontuacao: (valor) => {
            if (valor < 0) return `${valor} pts`
            else if (valor > 0) return `+${valor} pts`
            else return '—'
        }
    },
    mounted() {
        this.buscaExplicacoesPontuacaoProduto()
        this.buscaUrlMeulook()
    }
})