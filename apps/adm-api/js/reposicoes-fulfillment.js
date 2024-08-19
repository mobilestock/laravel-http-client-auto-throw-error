import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var reposicoesFulfillmentVue = new Vue({
  el: '#reposicoesFulfillmentVue',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      loading: false,
      ehPossivelVoltarAoTopo: false,
      possuiMaisPaginas: false,
      modalImpressaoEtiquetas: false,
      modalTermosCondicoes: false,
      pesquisa: '',
      pagina: 1,
      multiplicador: 1,
      paginaObserver: null,
      pesquisaObserver: null,
      produtoSelecionado: null,
      produtos: [[]],
      snackbar: {
        ativar: false,
        texto: '',
        cor: 'error',
      },
      headersGrades: [
        this.itemGrade('Tamanho', 'nome_tamanho'),
        this.itemGrade('Remover', 'remover'),
        this.itemGrade('Estoque', 'estoque'),
        this.itemGrade('Adicionar', 'adicionar'),
        this.itemGrade('Selecionado', 'quantidade_impressao'),
      ],
    }
  },

  methods: {
    itemGrade(coluna, valor) {
      return {
        text: coluna,
        value: valor,
        align: 'center',
        class: 'p-0',
      }
    },

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
          const resposta = await api.get('api_administracao/produtos_logistica/fulfillment', {
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

    reporProduto(produto) {
      this.produtoSelecionado = produto
      this.produtoSelecionado.grades = this.produtoSelecionado.grades.map((grade) => ({
        ...grade,
        quantidade_impressao: 0,
      }))
      this.modalImpressaoEtiquetas = true
      this.multiplicador = 1
    },

    remover(gradeSelecionada) {
      this.produtoSelecionado.grades.find((grade) => {
        if (grade.nome_tamanho === gradeSelecionada.nome_tamanho && grade.quantidade_impressao > 0) {
          grade.quantidade_impressao--
        }
      })
    },

    adicionar(gradeSelecionada) {
      this.produtoSelecionado.grades.find((grade) => {
        if (grade.nome_tamanho === gradeSelecionada.nome_tamanho && grade.quantidade_impressao < 999) {
          grade.quantidade_impressao++
        }
      })
    },

    incrementarMultiplicador() {
      this.multiplicador++
    },

    decrementarMultiplicador() {
      if (this.multiplicador > 1) {
        this.multiplicador--
      }
    },

    async imprimirEtiquetas() {
      try {
        this.loading = true
        const dados = {
          id_produto: this.produtoSelecionado.id_produto,
          grades: this.gradesComMultiplicador.filter((grade) => grade.quantidade_impressao > 0),
        }
        const resposta = await api.post('api_administracao/produtos_logistica/etiquetas', dados)

        const etiquetasSKU = JSON.stringify(resposta.data)
        const filename = `etiquetas_unitaria_reposicao_${this.produtoSelecionado.id_produto}_${new Date().toISOString()}`
        const blob = new Blob([etiquetasSKU], {
          type: 'json',
        })
        saveAs(blob, `${filename}.json`)
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao imprimir etiquetas')
      } finally {
        this.loading = false
        this.fecharModalImpressaoEtiquetas()
      }
    },

    fecharModalImpressaoEtiquetas() {
      this.modalImpressaoEtiquetas = false
      this.multiplicador = 1
      this.produtoSelecionado = null
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

  computed: {
    gradesComMultiplicador() {
      let grades = []
      if (this.produtoSelecionado.grades) {
        grades = this.produtoSelecionado.grades.map((grade) => ({
          ...grade,
          quantidade_impressao: grade.quantidade_impressao * this.multiplicador,
        }))
      }
      return grades
    },
  },

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
