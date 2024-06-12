import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

let timeout
let interval

var app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      headersProdutosEstoque: [
        {
          text: 'ID',
          value: 'id',
        },
        { text: 'Referencia', value: 'descricao' },
        { text: 'Foto', value: 'foto' },
        {
          text: 'Estoque',
          value: 'estoque',
          align: 'center',
          sortable: false,
        },
        { text: '', value: 'acoes', sortable: false },
      ],
      loadingProdutosEstoque: false,
      carregandoZerandoEstoque: false,
      dialogConfirmarZerarEstoque: false,
      maisPaginas: false,
      pagina: 0,
      produtosEstoque: [],
      produtoModalAlteraEstoqueProduto: {},
      snackbar: {
        ativar: false,
        cor: 'error',
        texto: '',
      },
      headersMovimentacaoManual: [
        { text: 'Tamanho', value: 'tamanho', class: 'bg-secondary' },
        { text: 'Alterar estoque', value: 'acao', class: 'bg-secondary' },
        { text: 'Estoque', value: 'estoque', class: 'bg-secondary' },
        { text: 'Pagamento Pendente', value: 'vendido', class: 'bg-secondary' },
        { text: 'Total em estoque', value: 'qtd_total', class: 'bg-secondary' },
      ],
      tipoMovimentacao: '',
      loadingCorrigeEstoque: false,
      sellerBloqueado: false,
      mediaCancelamentos: null,
      isLoadingBloqueado: false,
      pesquisaProdutos: '',
    }
  },

  methods: {
    async buscaSellerEhBloqueado() {
      const id_fornecedor = document.getElementsByName('userIDCliente')[0].value

      await MobileStockApi(`api_administracao/fornecedor/verifica_seller_bloqueado/${id_fornecedor}`)
        .then((resp) => resp.json())
        .then((resp) => {
          if (!resp.status) return
          this.sellerBloqueado = resp.data
        })
    },
    async listaProdutosEstoque() {
      this.loadingProdutosEstoque = true

      try {
        if (!!this.pesquisaProdutos) {
          this.pagina = 0
        }

        const parametros = new URLSearchParams({
          pesquisa: this.pesquisaProdutos,
          pagina: this.pagina,
        })

        await MobileStockApi(`api_administracao/produtos/estoque_interno?${parametros}`)
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw new Error(resp.message)

            this.produtosEstoque = resp.data.produtos
            this.maisPaginas = resp.data.mais_paginas
          })
      } catch (erro) {
        this.enqueueSnackbar(erro)
      } finally {
        this.loadingProdutosEstoque = false
      }
    },
    async zerarEstoqueResponsavel() {
      try {
        this.carregandoZerandoEstoque = true
        await api.put('api_administracao/fornecedor/zerar_estoque_responsavel')

        this.enqueueSnackbar('Estoque zerado com sucesso', 'success')
        await this.listaProdutosEstoque()
        this.dialogConfirmarZerarEstoque = false
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao tentar zerar estoque')
      } finally {
        this.carregandoZerandoEstoque = false
      }
    },
    calculaTotalEstoque(item, val) {
      if (!this.$refs.formularioCorrigir.validate() || !this.tipoMovimentacao) {
        this.$nextTick(() => (item.qtd_movimentar = null))
        alert('Por favor, selecione o tipo de movimentação antes de alterar o estoque.')
        return
      }

      item.qtd_total = item.estoque + (!val ? 0 : parseInt(this.tipoMovimentacao === 'E' ? val : val * -1))
      item.qtd_movimentar = val
    },

    async corrigeEstoque() {
      this.loadingCorrigeEstoque = true
      try {
        if (this.sellerBloqueado) return

        const extrapolados = this.produtoModalAlteraEstoqueProduto.estoque.filter(
          (grade) => grade.qtd_movimentar > grade.limite,
        )
        if (extrapolados.length > 0) {
          const tamanhos = extrapolados.map((grade) => grade.nome_tamanho)

          throw new Error(
            `Os tamanhos ${tamanhos.join(',')} do produto estão ultrapassando o limite de reposição permitido`,
          )
        }
        if (this.produtoModalAlteraEstoqueProduto.estoque.some((grade) => grade.qtd_total < 0)) {
          throw new Error('Erro! Estoque não pode ficar negativo')
        }

        await api.post('api_administracao/produtos/movimentacao_manual', {
          tipo: this.tipoMovimentacao,
          grades: this.produtoModalAlteraEstoqueProduto.estoque.map((produtoEstoque) => ({
            id_produto: this.produtoModalAlteraEstoqueProduto.id,
            tamanho: produtoEstoque.nome_tamanho,
            qtd_movimentado: produtoEstoque.qtd_movimentar || 0,
          })),
        })

        this.listaProdutosEstoque()
        this.produtoModalAlteraEstoqueProduto = {}
        this.enqueueSnackbar('Estoque atualizado.', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao tentar movimentar estoque.')
      } finally {
        this.loadingCorrigeEstoque = false
      }
    },
    async abreModalMovimentacaoEstoque(item) {
      if (this.sellerBloqueado) {
        this.isLoadingBloqueado = true

        try {
          await MobileStockApi('api_administracao/fornecedor/busca_media_cancelamentos_seller')
            .then((resp) => resp.json())
            .then((resp) => {
              if (!resp.status) throw new Error(resp.message)
              this.mediaCancelamentos = resp.data
            })
        } catch (error) {
          alert(error)
        } finally {
          this.produtoModalAlteraEstoqueProduto = item
          this.isLoadingBloqueado = false
        }
      } else {
        this.produtoModalAlteraEstoqueProduto = item
      }
    },
    fechaModalMovimentacaoEstoque(val) {
      if (val === false) {
        this.produtoModalAlteraEstoqueProduto = {}
      }

      this.tipoMovimentacao = ''
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },
    contatoSuporte() {
      const mensagem = new MensagensWhatsApp({
        telefone: '37991122302',
      }).resultado

      window.open(mensagem, '_blank')
    },
  },

  watch: {
    pagina() {
      this.listaProdutosEstoque()
    },
    tipoMovimentacao(newV) {
      if (!this.produtoModalAlteraEstoqueProduto) return

      this.produtoModalAlteraEstoqueProduto.estoque?.forEach((estoque) => {
        if (estoque.qtd_movimentar) {
          this.calculaTotalEstoque(estoque, estoque.qtd_movimentar)
        }
      })
    },
  },

  mounted() {
    this.buscaSellerEhBloqueado()
    this.listaProdutosEstoque()
  },
})
