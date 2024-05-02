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
        this.itemGrade('Tag', 'tag'),
        this.itemGrade('Grade Disponivel', 'grade'),
        this.itemGrade('Seller', 'fornecedor'),
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
        const resp = await api.get(
          `api_administracao/produtos/pesquisa_produto_lista?` +
            `codigo=${this.filtros.codigo}` +
            `&tag=${this.filtros.tag}` +
            `&descricao=${this.filtros.descricao}` +
            `&categoria=${this.filtros.categoria}` +
            `&fornecedor=${this.filtros.fornecedor}` +
            `&nao_avaliado=${this.filtros.naoAvaliado}` +
            `&bloqueados=${this.filtros.bloqueados}` +
            `&fotos=${this.filtros.fotos}` +
            `&sem_foto_pub=${this.filtros.sem_foto_pub}` +
            `&pagina=${this.filtros.pagina || 1}`,
        )

        console.log(resp.data)

        const consulta = resp.data
        this.itens = consulta.produtos
        this.qtd_produtos = consulta.qtd_produtos
      } catch (error) {
        this.onCatch
      } finally {
        this.carregando = false
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

    async atualizaTag(produto) {
      try {
        this.carregando = true
        const dados = {
          tag: produto.tag === 'TRADICIONAL' ? 'MODA' : 'TRADICIONAL',
        }
        console.log(dados)
        await api.put(`api_administracao/produtos/tag/${produto.id}`, dados)
        this.itens = this.itens.map((item) => {
          if (item.id === produto.id) item.tag = dados.tag
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
