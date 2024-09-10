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
      mostrarRelatorio: false,
      pesquisa: '',
      pagina: 1,
      multiplicador: 1,
      quantidadeMaximaImpressao: 999,
      paginaObserver: null,
      pesquisaObserver: null,
      produtoSelecionado: null,
      produtoRelatorio: [],
      produtos: [],
      snackbar: {
        ativar: false,
        texto: '',
        cor: 'error',
      },
      headersRelatorio: [],
      headersGrades: [
        this.itemGrade('Tamanho', 'nome_tamanho'),
        this.itemGrade('Remover', 'remover'),
        this.itemGrade('Adicionar', 'adicionar'),
        this.itemGrade('Selecionado', 'quantidade_impressao'),
      ],
      endpoint: '',
      parametros: {},
    }
  },

  methods: {
    itemGrade(coluna, valor) {
      return {
        text: coluna,
        value: valor,
        align: 'center',
        class: ['p-0', 'm-0'],
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
          this.pagina === 1
            ? this.produtos.push(...[[], ...resposta.data.produtos])
            : this.produtos.push(...resposta.data.produtos)
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
      this.gerarRelatorio(produto.id_produto)
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
        if (
          grade.nome_tamanho === gradeSelecionada.nome_tamanho &&
          gradeSelecionada.quantidade_impressao < this.quantidadeMaximaImpressao
        ) {
          grade.quantidade_impressao++
        }
      })

      if (gradeSelecionada.quantidade_impressao >= this.quantidadeMaximaImpressao) {
        this.$nextTick(() => (gradeSelecionada.quantidade_impressao = this.quantidadeMaximaImpressao))
      }
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

        this.endpoint = 'api_administracao/produtos_logistica/etiquetas'
        this.parametros = {
          id_produto: this.produtoSelecionado.id_produto,
          grades: this.gradesComMultiplicador.filter((grade) => grade.quantidade_impressao > 0),
          formato_saida: 'ZPL',
        }

        window.open('', 'popup', 'width=500,height=500')

        this.$nextTick(() => {
          this.$refs.formularioImpressao.target = 'popup'
          this.$refs.formularioImpressao.submit()
        })
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
      this.produtoRelatorio = []
      this.mostrarRelatorio = false
      this.headersRelatorio = []
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

    async gerarRelatorio(idProduto) {
      const resposta = await api.get(`api_administracao/produtos/relatorio/${idProduto}`)

      const tamanhos = resposta.data.map((produto) => produto.nome_tamanho)

      const categorias = [
        'estoque',
        'vendidos',
        'no_carrinho',
        'clientes_distintos',
        'devolucao_normal',
        'devolucao_defeito',
      ]

      const dadosCategorias = Object.fromEntries(
        categorias.map((categoria) => [categoria, Object.fromEntries(tamanhos.map((tamanho) => [tamanho, 0]))]),
      )

      resposta.data.forEach((item) => {
        dadosCategorias.estoque[item.nome_tamanho] += item.estoque
        dadosCategorias.vendidos[item.nome_tamanho] += item.vendidos
        dadosCategorias.no_carrinho[item.nome_tamanho] += item.no_carrinho
        dadosCategorias.clientes_distintos[item.nome_tamanho] += item.vendas_diferentes_clientes
        dadosCategorias.devolucao_normal[item.nome_tamanho] += item.devolucao_normal
        dadosCategorias.devolucao_defeito[item.nome_tamanho] += item.devolucao_defeito
      })

      this.produtoRelatorio = categorias.map((categoria) => ({
        categoria: categoria
          .replace('estoque', 'Estoque disponível')
          .replace('vendidos', 'Vendas')
          .replace('no_carrinho', 'No carrinho')
          .replace('clientes_distintos', 'Clientes distintos')
          .replace('devolucao_normal', 'Devolução normal')
          .replace('devolucao_defeito', 'Devolução defeito'),
        ...dadosCategorias[categoria],
        total: Object.values(dadosCategorias[categoria]).reduce((a, b) => a + b, 0),
      }))

      this.headersRelatorio = [
        {
          text: 'Tamanho',
          value: 'categoria',
          align: 'center',
          sortable: false,
          class: ['bg-black', 'text-white'],
        },
        ...tamanhos.map((tamanho) => ({
          text: tamanho,
          value: tamanho,
          sortable: false,
          class: ['bg-black', 'text-white'],
        })),
        { text: 'Total', value: 'total', sortable: false, class: ['bg-black', 'text-white'] },
      ]
    },

    validarInput(gradeSelecionada) {
      const quantidadeImpressao = parseInt(gradeSelecionada.quantidade_impressao)
      if (isNaN(quantidadeImpressao) || quantidadeImpressao < 0) {
        gradeSelecionada.quantidade_impressao = 0
        return
      }

      if (quantidadeImpressao <= this.quantidadeMaximaImpressao) {
        this.produtoSelecionado.grades.find((grade) => {
          if (grade.nome_tamanho === gradeSelecionada.nome_tamanho) {
            grade.quantidade_impressao = Math.abs(quantidadeImpressao)
          }
        })
      } else {
        this.$nextTick(() => (gradeSelecionada.quantidade_impressao = this.quantidadeMaximaImpressao))
      }
    },
  },

  computed: {
    gradesComMultiplicador() {
      let grades = []
      if (this.produtoSelecionado.grades) {
        grades = this.produtoSelecionado.grades.map((grade) => ({
          ...grade,
          quantidade_impressao:
            grade.quantidade_impressao * this.multiplicador > this.quantidadeMaximaImpressao
              ? this.quantidadeMaximaImpressao
              : grade.quantidade_impressao * this.multiplicador,
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
