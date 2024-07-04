var comprasVue = new Vue({
  el: '#comprasVue',
  vuetify: new Vuetify(),
  data: {
    filtros: {
      fornecedor: '',
      id: '',
      referencia: '',
      data_inicial_emissao: '',
      data_fim_emissao: '',
      data_inicial_previsao: '',
      data_fim_previsao: '',
      tamanho: '',
      situacao: '',
    },
    headers: [
      {
        text: 'Número',
        align: 'start',
        value: 'id',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Fornecedor',
        value: 'fornecedor',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Situação',
        value: 'situacao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Preço Total',
        value: 'preco_total',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Emissão',
        value: 'data_emissao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Previsão',
        value: 'data_previsao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Editar',
        value: '',
        align: 'center',
        filterable: false,
        sortable: false,
      },
    ],
    menu: false,
    menu2: false,
    dialog: false,
    selectFornecedor: [],
    datesEmissao: [],
    datesPrevisao: [],
    listaSituacoes: [
      { id: 'EM_ABERTO', situacao: 'Em Aberto' },
      { id: 'PARCIALMENTE_ENTREGUE', situacao: 'Parcialmente Entregue' },
      { id: 'ENTREGUE', situacao: 'Entregue' },
    ],
    buscaFornecedor: '',
    fornecedor: false,
    filtroTabela: '',
    listaReposicoes: [],
    listaCodigoBarras: [],
    pagina: 1,
    itemsPorPagina: 25,
    loading: true,
    overlay: false,
    options: {},
    snackbar: {
      text: '',
      color: 'green accent-4',
      open: false,
    },
    valorTamanhoDescrito: '',
  },
  mounted() {
    if ($('#cabecalhoVue input[name=nivelAcesso]').val() == 30) {
      this.fornecedor = true
    }
  },
  filters: {
    moneyMask(value) {
      if (value) {
        let sinal = Math.sign(parseFloat(value)) == -1 ? '-' : ''
        return sinal + formataMoeda(value)
      }
    },
  },
  computed: {
    dateRangeText() {
      return this.filtros.data_inicial_emissao && this.filtros.data_fim_emissao
        ? this.converteData(this.filtros.data_inicial_emissao) +
            ' - ' +
            this.converteData(this.filtros.data_fim_emissao)
        : 'Selecione uma data'
    },
    dateRangeTextPrevisao() {
      return this.filtros.data_inicial_previsao && this.filtros.data_fim_previsao
        ? this.converteData(this.filtros.data_inicial_previsao) +
            ' - ' +
            this.converteData(this.filtros.data_fim_previsao)
        : 'Selecione uma data'
    },
  },
  watch: {
    buscaFornecedor(val) {
      val && val !== this.filtros.fornecedor && this.buscaFornecedorPeloNome(val)
    },
    datesEmissao(val) {
      this.filtros.data_inicial_emissao = val[0]
      this.filtros.data_fim_emissao = val[1]
    },
    datesPrevisao(val) {
      this.filtros.data_inicial_previsao = val[0]
      this.filtros.data_fim_previsao = val[1]
    },
    options: {
      handler() {
        this.buscaListaReposicoes()
      },
      deep: true,
    },
  },
  methods: {
    editarCompra(idReposicao) {
      window.location.href = `cadastrar-reposicao.php?id_reposicao=${idReposicao}`
    },
    converteData: function (data) {
      if (!data) return ''
      data = data.toString()
      return data.substring(0, 10).split('-').reverse().join('/')
    },

    async buscaListaReposicoes(clear = false) {
      this.loading = true
      try {
        const { page, itemsPerPage } = this.options
        if (clear) this.listaReposicoes = []

        const parametros = new URLSearchParams({
          id_reposicao: this.filtros.id,
          id_fornecedor: this.filtros.fornecedor,
          referencia: this.filtros.referencia,
          nome_tamanho: this.filtros.tamanho,
          situacao: this.filtros.situacao,
          data_inicial_emissao: this.filtros.data_inicial_emissao,
          data_fim_emissao: this.filtros.data_fim_emissao,
          data_inicial_previsao: this.filtros.data_inicial_previsao,
          data_fim_previsao: this.filtros.data_fim_previsao,
          itens: itemsPerPage,
          pagina: page ?? 1,
        })

        const resposta = await api.get(`api_administracao/reposicoes?${parametros}`)
        this.listaReposicoes = resposta.data.map((item) => {
          item.preco_total = formataMoeda(item.preco_total)
          return item
        })
        this.itemsPorPagina = page * itemsPerPage + itemsPerPage
      } catch (error) {
        this.snackbar = {
          open: true,
          color: 'error',
          text: error,
        }
      } finally {
        this.loading = false
      }
    },

    async buscaFornecedorPeloNome(nome) {
      try {
        this.loading = true
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })
        const resposta = await api.get(`api_administracao/fornecedor/busca_fornecedores?${parametros}`)

        this.selectFornecedor = resposta.data.map((fornecedor) => ({
          ...fornecedor,
          nome: `${fornecedor.id} - ${fornecedor.nome}`,
        }))
      } catch (error) {
        this.snackbar = {
          open: true,
          color: 'error',
          text: error?.response?.data?.message || error?.message || 'Erro ao buscar fornecedores',
        }
      } finally {
        this.loading = false
      }
    },
  },
})
