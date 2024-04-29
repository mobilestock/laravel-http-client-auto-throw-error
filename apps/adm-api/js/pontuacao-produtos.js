import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'
new Vue({
  el: '#pontuacaoprodutos',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
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
        this.itemGrade('Total Normalizado', 'total_normalizado', true),
      ],
      produtos: [],
      urlBaseMeulook: '',
      pesquisa: '',
      carregando: false,
      timer: null,
      pagina: 1,
      snack: {
        mensagem: '',
        mostrar: false,
      },
      pontuacoes: {
        atraso_separacao: 0,
        avaliacao_4_estrelas: 0,
        avaliacao_5_estrelas: 0,
        pontuacao_cancelamento: 0,
        devolucao_defeito: 0,
        devolucao_normal: 0,
        possui_fulfillment: 0,
        reputacao_excelente: 0,
        reputacao_melhor_fabricante: 0,
        reputacao_regular: 0,
        reputacao_ruim: 0,
        pontuacao_venda: 0,
      },
      ultimaPagina: false,
      mostrarTodosSellers: false,
      dialog: {
        mostrar: false,
      },
    }
  },
  watch: {
    pesquisa() {
      clearTimeout(this.timer)
      this.timer = setTimeout(async () => this.resetar(), 750)
    },
    mostrarTodosSellers() {
      this.resetar()
    },
  },
  methods: {
    buscaExplicacoesPontuacaoProduto() {
      MobileStockApi('api_administracao/produtos/busca_explicacoes_pontuacao_produtos')
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw new Error(json.message)
          this.pontuacoes = json.data
        })
        .catch((error) => {
          this.snack.mensagem = error.message || 'Erro ao carregar explicações'
          this.snack.mostrar = true
        })
    },
    buscaListaPontuacoes() {
      if (this.carregando) return
      this.carregando = true
      MobileStockApi(
        `api_administracao/produtos/busca_lista_pontuacoes?pesquisa=${this.pesquisa}&pagina=${this.pagina}&listar_todos=${this.mostrarTodosSellers}`,
      )
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw new Error(json.message)
          if (!json.data?.length) return (this.ultimaPagina = true)
          this.produtos = this.produtos.concat(json.data)
          this.pagina += 1
        })
        .catch((error) => {
          this.snack.mensagem = error.message || 'Erro ao carregar produtos'
          this.snack.mostrar = true
        })
        .finally(() => (this.carregando = false))
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
        value: valor,
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
    },
  },
  filters: {
    pontuacao: (valor) => {
      if (valor < 0) return `${valor} pts`
      else if (valor > 0) return `+${valor} pts`
      else return '—'
    },
  },
  mounted() {
    this.buscaExplicacoesPontuacaoProduto()
    this.buscaUrlMeulook()
  },
})
