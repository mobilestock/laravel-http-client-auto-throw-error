new Vue({
  el: "#marketplace-fornecedor",
  vuetify: new Vuetify({}),
  data: () => ({
    dataInicial: '',
    dataInicialFormatada: '',
    dataFinal: '',
    dataFinalFormatada: '',
    menuDataInicial: false,
    menuDataFinal: false,
    cabecalho: [
      { text: 'Data', align: 'left', value: 'data', sortable: false },
      { text: 'Origem', align: 'center', value: 'origem', sortable: false },
      { text: 'Valor', align: 'center', value: 'valor', sortable: false },
      { text: 'Saldo', align: 'right', value: 'saldo', sortable: false }
    ],
    itens: null,
    carregando: false,
    mostrarSnackBar: false,
    mensagemSnackBar: ''
  }),
  computed: {
    dataInicialProcessada() {
      return this.formatarData(this.dataInicial)
    },
    dataFinalProcessada() {
      return this.formatarData(this.dataFinal)
    }
  },
  watch: {
    dataInicial() {
      this.dataInicialFormatada = this.formatarData(this.dataInicial)
      this.buscaLancamentos()
    },
    dataFinal() {
      this.dataFinalFormatada = this.formatarData(this.dataFinal)
      this.buscaLancamentos()
    }
  },
  methods: {
    formatarDinheiro(valor = 0) {
      return Intl.NumberFormat('pt-br', {
        currency: 'BRL',
        style: 'currency'
      }).format(valor)
    },
    corDinheiro(valor = 0) {
      if (valor < 0) return 'text-danger'
      else if (valor > 0) return 'text-success'
      else return 'text-secondary'
    },
    formatarData(data) {
      if (!data) return null
      const dataAux = data.replace(/([0-9]{4})-([0-9]{2})-([0-9]{2})/, '$3-$2-$1')
      return dataAux
    },
    buscaLancamentos() {
      if (this.carregando) return
      this.carregando = true
      this.itens = null
      MobileStockApi(`api_administracao/fornecedor/extrato?data_inicial=${this.dataInicial}&data_final=${this.dataFinal}`)
        .then(async response => response.json())
        .then(response => {
          if (!response.status) throw new Error(response.message)
          this.itens = response.data
        })
        .catch(error => {
          this.mensagemSnackBar = error.message || 'Erro ao carregar dados'
          this.mostrarSnackBar = true
        })
        .finally(() => this.carregando = false)
    },
    limparFiltros() {
      this.dataInicial = ''
      this.dataFinal = ''
      this.buscaLancamentos()
    }
  },
  mounted() {
    this.buscaLancamentos()
  }
})
