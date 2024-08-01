var reposicoesFulfillmentVue = new Vue({
  el: '#reposicoesFulfillmentVue',
  vuetify: new Vuetify(),

  data: {
    loading: false,
    fornecedor: false,
    ehPossivelVoltarAoTopo: false,
    pesquisa: '',
    pagina: 1,
    idFornecedor: null,
    produtos: [],
    snackbar: {
      ativar: false,
      texto: '',
      cor: 'error',
    },
  },

  methods: {
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },

    buscarProdutos() {
      this.debounce(async () => {
        try {
          this.loading = true
          const resposta = await api.get('api_administracao/produtos/fulfillment', {
            params: {
              id_fornecedor: this.idFornecedor,
              pesquisa: this.pesquisa,
              pagina: this.pagina,
            },
          })
          this.produtos.push(...resposta.data.produtos)
        } catch (error) {
          this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar produtos')
        } finally {
          this.loading = false
        }
      }, 100)
    },

    reporProduto(idProduto) {
      // ainda a implementar
      console.log(`Repondo produto de ID ${idProduto}...`)
    },

    verificarScroll() {
      if (window.innerHeight + window.scrollY >= document.body.offsetHeight) {
        this.debounce(async () => {
          this.pagina++
          this.ehPossivelVoltarAoTopo = true
        }, 50)
      }
    },

    voltarAoTopo() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      })

      this.ehPossivelVoltarAoTopo = false
    },

    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },
  },
  filters: {},
  computed: {},
  watch: {
    pesquisa() {
      this.debounce(() => {
        this.pagina = 1
        this.produtos = []
        this.buscarProdutos()
      }, 500)
    },
    pagina() {
      if (this.produtos.length > 0) {
        this.buscarProdutos()
      }
    },
  },

  mounted() {
    window.addEventListener('scroll', this.verificarScroll)

    const nivelAcesso = $('#cabecalhoVue input[name=nivelAcesso]').val()
    if (nivelAcesso == 30) {
      this.idFornecedor = $('#cabecalhoVue input[name=userIDCliente]').val()
    }

    this.buscarProdutos()
  },

  beforeDestroy() {
    window.removeEventListener('scroll', this.verificarScroll)
  },
})
