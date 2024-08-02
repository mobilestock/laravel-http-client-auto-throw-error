var reposicoesFulfillmentVue = new Vue({
  el: '#reposicoesFulfillmentVue',
  vuetify: new Vuetify(),

  data: {
    loading: false,
    ehPossivelVoltarAoTopo: false,
    pesquisa: '',
    pagina: 1,
    possuiMaisPaginas: false,
    paginaObserver: null,
    pesquisaObserver: null,
    produtos: [[]],
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

    async buscarProdutos() {
      this.debounce(async () => {
        try {
          this.loading = true
          const resposta = await api.get('api_administracao/produtos/fulfillment', {
            params: {
              pesquisa: this.pesquisa,
              pagina: this.pagina,
            },
          })
          this.produtos.push(...resposta.data.produtos)
          this.possuiMaisPaginas = resposta.data.possui_mais_paginas
        } catch (error) {
          this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar produtos')
        } finally {
          this.loading = false
        }
      }, 100)
    },

    reporProduto(idProduto) {
      window.open(`/reposicoes-etiquetas?id=${idProduto}`, '_blank')
    },

    verificarScroll(entries) {
      entries.forEach((entry) => {
        if (entry.isIntersecting && this.possuiMaisPaginas) {
          this.pagina++
        }
      })
    },

    verificarPesquisa(entries) {
      entries.forEach((entry) => {
        this.ehPossivelVoltarAoTopo = !entry.isIntersecting
      })
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
        this.possuiMaisPaginas = false
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
    this.paginaObserver = new IntersectionObserver(this.verificarScroll, {
      root: null,
      rootMargin: '0px',
      threshold: 1.0,
    })

    this.pesquisaObserver = new IntersectionObserver(this.verificarPesquisa, {
      root: null,
      rootMargin: '0px',
      threshold: 0.1,
    })

    this.$nextTick(() => {
      const finalPagina = this.$refs.finalPagina
      if (finalPagina) {
        this.paginaObserver.observe(finalPagina)
      }

      const blocoPesquisa = this.$refs.blocoPesquisa
      if (blocoPesquisa) {
        this.pesquisaObserver.observe(blocoPesquisa)
      }
    })

    this.buscarProdutos()
  },

  beforeDestroy() {
    const finalPagina = this.$refs.observeElement
    if (finalPagina) {
      this.paginaObserver.unobserve(finalPagina)
    }

    const blocoPesquisa = this.$refs.blocoPesquisa
    if (blocoPesquisa) {
      this.pesquisaObserver.unobserve(blocoPesquisa)
    }
  },
})
