var produtoCorrigirEstoqueDetalhes = new Vue({
  el: '#produtoCorrigirEstoqueDetalhesVue',
  vuetify: new Vuetify(),
  data() {
    return {
      idProduto: document.location.search.match(/[0-9]+/)[0],
      isLoading: false,
      isLoadingMovimentar: false,
      tipo: null,
      produto: [],
      gradesProduto: [],
      headersGradeTabela: [
        {
          text: 'Tamanho',
          value: 'nome_tamanho',
          align: 'center',
          class: 'text-light grey darken-2',
        },
        {
          text: 'Alterar Estoque',
          value: 'quantidade',
          align: 'center',
          class: 'text-light grey darken-2',
        },
        {
          text: 'Estoque',
          value: 'estoque',
          align: 'center',
          class: 'text-light grey darken-2',
        },
        {
          text: 'Vendido',
          value: 'vendido',
          align: 'center',
          class: 'text-light grey darken-2',
        },
        {
          text: 'Total',
          value: 'total',
          align: 'center',
          class: 'text-light grey darken-2',
        },
      ],
      tiposMovimentacao: [
        {
          text: 'Entrada',
          value: 'E',
        },
        {
          text: 'Saída',
          value: 'X',
        },
      ],
      snackbar: {
        ativar: false,
        cor: 'error',
        texto: '',
      },
    }
  },
  methods: {
    async buscaDetalhesProduto() {
      this.isLoading = true
      try {
        await Promise.all([
          MobileStockApi(`api_administracao/produtos/busca_detalhes_pra_conferencia_estoque/${this.idProduto}`)
            .then((resp) => resp.json())
            .then((resp) => {
              if (resp.status) {
                this.produto = resp.data
              } else {
                throw new Error(resp.message)
              }
            }),
          MobileStockApi(`api_administracao/produtos/buscar_grades_do_produto/${this.idProduto}`)
            .then((resp) => resp.json())
            .then((resp) => {
              if (!resp.status) throw new Error(resp.message)

              const grades = resp.data
              if (!grades.length) {
                this.enqueueSnackbar(true, 'success', 'Produto não possui grades, verifique se está no estoque correto')
              } else {
                this.gradesProduto = resp.data
              }
            }),
        ])
      } catch (error) {
        this.enqueueSnackbar(true, 'error', error)
      } finally {
        this.isLoading = false
      }
    },

    async movimentarEstoque() {
      this.isLoadingMovimentar = true
      try {
        if (!this.tipo) throw new Error('É necessário definir um tipo de movimentação')

        if (this.gradesProduto.some((grade) => grade.total < 0)) {
          throw new Error('Erro! Estoque não pode ficar negativo')
        }

        await api.post('api_administracao/produtos/movimentacao_manual', {
          tipo: this.tipo,
          grades: this.gradesProduto.map((grade) => ({
            id_produto: this.idProduto,
            tamanho: grade.nome_tamanho,
            qtd_movimentado: grade.quantidade || 0,
          })),
        })

        location.reload()
      } catch (error) {
        this.enqueueSnackbar(true, 'error', error?.response?.data?.message || error?.message)
      } finally {
        this.isLoadingMovimentar = false
      }
    },

    mudaTipo() {
      this.gradesProduto.map((grade) => {
        grade.quantidade = 0
      })
    },
    calculaTotalEstoque(item, input) {
      if (!this.tipo) {
        this.$nextTick(() => (item.quantidade = null))
      } else {
        const quantidade = this.tipo === 'E' ? input : input * -1
        item.total = parseInt(item.estoque) + parseInt(!input ? 0 : quantidade)
        item.quantidade = input
      }
    },
    voltar() {
      window.location.href = 'produtos-corrigir-estoque.php'
    },
    enqueueSnackbar(ativar = true, cor = 'error', texto = 'Erro') {
      this.snackbar = { ativar, cor, texto }
    },
  },
  mounted() {
    this.buscaDetalhesProduto()
  },
})
