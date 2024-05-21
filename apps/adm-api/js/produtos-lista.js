import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#produtosListaVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      categorias: [],
      fornecedores: [],
      filtros: {
        tag: '',
        codigo: '',
        descricao: '',
        categoria: '',
        fornecedor: '',
        naoAvaliado: false,
        bloqueados: false,
        fotos: '',
        pagina: 1,
        sem_foto_pub: false,
        qtd_produtos: 0,
      },
      carregando: false,
      cabecalho: [
        this.itemGrade('Fotos', 'fotos'),
        this.itemGrade('ID', 'id'),
        this.itemGrade('Data Cadastro', 'data_cadastro'),
        this.itemGrade('Descrição', 'nome'),
        this.itemGrade('Tag', 'eh_moda'),
        this.itemGrade('Grade Disponivel', 'grade'),
        this.itemGrade('Seller', 'fornecedor'),
        this.itemGrade('Permissão Fulfillment', 'eh_permitido_reposicao'),
        this.itemGrade('Editar', 'editar'),
      ],
      itens: [],
      snackBar: {
        mostrar: false,
        mensagem: '',
      },
    }
  },

  methods: {
    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        align: 'start',
        sortable: ordenavel,
        value: valor,
      }
    },

    onCatch(error) {
      this.snackBar.mensagem = error.message || 'Erro ao carregar Produtos'
      this.snackBar.mostrar = true
    },

    carregarCategorias() {
      MobileStockApi('api_administracao/categorias/tipos')
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw new Error(json.message)
          this.categorias = json.data
        })
        .catch(this.onCatch)
    },

    carregarFornecedores() {
      api
        .get('api_administracao/fornecedor/busca_fornecedores')
        .then((json) => (this.fornecedores = json.data))
        .catch(this.onCatch)
    },

    async carregarProdutos() {
      if (this.carregando) return
      this.carregando = true
      this.itens = []
      try {
        const resp = await api.get(`api_administracao/produtos/pesquisa_produto_lista`, {
          params: {
            codigo: this.filtros.codigo,
            eh_moda: this.converteTag(),
            descricao: this.filtros.descricao,
            categoria: this.filtros.categoria,
            fornecedor: this.filtros.fornecedor,
            nao_avaliado: this.filtros.naoAvaliado,
            bloqueados: this.filtros.bloqueados,
            fotos: this.filtros.fotos,
            sem_foto_pub: this.filtros.sem_foto_pub,
            pagina: this.filtros.pagina || 1,
          },
        })

        const consulta = resp.data
        this.itens = consulta.produtos
        this.qtd_produtos = consulta.qtd_produtos
      } catch (error) {
        this.onCatch
      } finally {
        this.carregando = false
      }
    },

    converteTag() {
      switch (this.filtros.tag) {
        case 'moda':
          return true
        case 'tradicional':
          return false
        default:
          return null
      }
    },

    limparFiltros() {
      this.filtros = {
        codigo: '',
        descricao: '',
        categoria: '',
        fornecedor: '',
        naoAvaliado: false,
        bloqueados: false,
        fotos: '',
        pagina: 1,
        sem_foto_pub: false,
        qtd_produtos: 0,
      }
      this.carregarProdutos()
    },
    converteValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },

    alterarPermissaoReporFulfillment(idProduto, permitir) {
      if (this.carregando) return
      this.carregando = true
      api
        .patch(`api_administracao/produtos/permissao_repor_fulfillment/${idProduto}`, {
          permitir_reposicao: permitir,
        })
        .then(() => {
          const indexProduto = this.itens.findIndex((item) => item.id === idProduto)
          this.itens[indexProduto].eh_permitido_reposicao = permitir
          this.snackBar.mensagem = 'Permissão alterada com sucesso'
          this.snackBar.mostrar = true
        })
        .catch(this.onCatch)
        .finally(() => (this.carregando = false))
    },

    async atualizaTag(idProduto) {
      try {
        this.carregando = true
        await api.patch(`api_administracao/produtos/moda/${idProduto}`)
        this.itens = this.itens.map((item) => {
          if (item.id === idProduto) item.eh_moda = !item.eh_moda
          return item
        })
      } catch (error) {
        this.onCatch(error)
      } finally {
        this.carregando = false
      }
    },
  },
  watch: {
    'filtros.sem_foto_pub'(valor) {
      if (valor) this.filtros.fotos = ''
    },
    'filtros.pagina'() {
      this.carregarProdutos()
    },
  },
  mounted() {
    this.carregarCategorias()
    this.carregarFornecedores()
    this.carregarProdutos()
  },
})
