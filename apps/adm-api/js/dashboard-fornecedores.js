import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#dashboard-fornecedores',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      headerProdutosCancelados: [
        this.itemGrades('ID produto', 'id_produto'),
        this.itemGrades('Produto', 'foto_produto'),
        this.itemGrades('Tamanho', 'nome_tamanho'),
        this.itemGrades('Data cancelamento', 'data_cancelamento'),
        this.itemGrades('Produto foi cancelado', 'acao'),
      ],
      listaProdutosCancelados: [],
      menuProdutoAberto: '',
      gradeDetalhada: '',
      produtoTirarDeLinha: '',
      carregandoCancelados: false,
      modalProdutosCancelados: false,
      carregandoTirarProdutoLinha: false,
      mostrarSnackbar: false,
      mensagemSnackBar: '',
      corSnackbar: 'error',
      produtos: null,
      tempoParaImpulsionarProdutos: 0,
      pagina: 1,
      carregandoProdutos: false,
      ultimaPagina: false,
      seller: {
        objetivos: {
          dias_despacho_concluido: false,
          taxa_cancelamento_concluido: false,
          valor_vendido_concluido: false,
        },
        dias_despacho: 0,
        porcentagem_barra: 0,
        reputacao: 'INDEFINIDA',
        taxa_cancelamento: 0,
        valor_vendido: 0,
      },
      requisitos: {
        media_dias_envio_melhor_fabricante: 0,
        taxa_cancelamento_melhor_fabricante: 0,
        valor_vendido_melhor_fabricante: 0,
        dias_mensurar_vendas: 0,
      },
    }
  },
  computed: {
    cumpriuPeriodoEntrega: function () {
      return this.seller.objetivos.dias_despacho_concluido
    },
    cumpriuCancelamentos: function () {
      return this.seller.objetivos.taxa_cancelamento_concluido
    },
    cumpriuValorVenda: function () {
      return this.seller.objetivos.valor_vendido_concluido
    },
    corReputacaoSeller: function () {
      switch (this.seller.reputacao) {
        case 'EXCELENTE':
          return 'success'
        case 'REGULAR':
          return 'warning'
        case 'RUIM':
          return 'danger'
        case 'MELHOR_FABRICANTE':
          return 'primary'
        case 'INDEFINIDA':
          return 'info'
      }
    },
  },
  methods: {
    carregarProdutos() {
      if (this.carregandoProdutos || this.ultimaPagina) return
      this.carregandoProdutos = true
      MobileStockApi(`api_administracao/fornecedor/saldo_produtos?pagina=${this.pagina}`)
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.data?.length) return (this.ultimaPagina = true)
          if (!this.produtos?.length) this.produtos = json.data
          else this.produtos = this.produtos.concat(json.data)
          this.pagina += 1
        })
        .catch((error) => this.mostrarAviso(error.message))
        .finally(() => setTimeout(() => (this.carregandoProdutos = false), 1000))
    },
    abrirMenuProduto(idProduto) {
      this.menuProdutoAberto = idProduto
    },
    abrirGradeProduto(gradeProduto) {
      this.menuProdutoAberto = ''
      this.gradeDetalhada = gradeProduto
    },
    abrirTela(produto) {
      let url = document.getElementsByName('url-mobile')[0].value
      if (produto.permitido_reposicao) url += 'compras.php'
      else url += 'fornecedor-estoque-interno-controle-estoque.php'
      this.menuProdutoAberto = ''
      window.open(url, '_blank')
    },
    abrirModalTirarDeLinha(idProduto) {
      this.menuProdutoAberto = ''
      this.produtoTirarDeLinha = idProduto
    },
    tirarProdutoDeLinha(idProduto) {
      this.carregandoTirarProdutoLinha = true
      api
        .patch(`api_administracao/produtos/tirar_de_linha/${idProduto}`)
        .then(() => {
          this.produtos = this.produtos.filter((produto) => produto.id != idProduto)
          this.mostrarAviso('Produto tirado de linha com sucesso', 'primary')
        })
        .catch((error) =>
          this.mostrarAviso(error?.response?.data?.message || error?.message || 'Erro ao tirar produto de linha'),
        )
        .finally(() => {
          this.carregandoTirarProdutoLinha = false
          this.produtoTirarDeLinha = ''
        })
    },
    corNumero(quantidade = 0) {
      if (quantidade > 0) return 'primary'
      else if (quantidade === 0) return 'secondary'
      else return 'danger'
    },
    mostrarAviso(mensagem, cor = 'error') {
      this.mensagemSnackBar = mensagem
      this.corSnackbar = cor
      this.mostrarSnackbar = true
    },
    impulsionarProdutos() {
      MobileStockApi('api_administracao/cadastro/produtos_data_entrada_todos', { method: 'PUT' })
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw Error(json.message)
          this.mostrarAviso('Produtos impulsionados com sucesso!', 'primary')
          this.buscaDiasParaDesbloquearBotaoUp()
        })
        .catch((error) => this.mostrarAviso(error.message))
    },
    buscaDiasParaDesbloquearBotaoUp() {
      MobileStockApi('api_administracao/fornecedor/busca_dias_para_desbloquear_botao_up')
        .then(async (response) => await response.json())
        .then((json) => {
          this.tempoParaImpulsionarProdutos = json.data.dias
        })
        .catch((error) => this.mostrarAviso(error.message))
    },
    buscaDadosSeller() {
      api
        .get('api_administracao/fornecedor/dados_dashboard')
        .then((json) => {
          if (json.data) this.seller = json.data
        })
        .catch((error) =>
          this.mostrarAviso(error?.response?.data?.message || error?.message || 'Erro ao buscar dados do fornecedor'),
        )
    },
    buscaRequisitosMelhoresFabricantes() {
      api
        .get('api_meulook/colaboradores/requisitos_melhores_fabricantes')
        .then((json) => (this.requisitos = json.data))
        .catch((error) =>
          this.mostrarAviso(error?.response?.data?.message || error?.message || 'Erro ao buscar requisitos'),
        )
    },
    onIntersect() {
      this.carregarProdutos()
    },
    async buscaTemCancelados() {
      this.carregandoCancelados = true

      try {
        await MobileStockApi('api_administracao/fornecedor/busca/lista_produtos_cancelados')
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw Error(resp.message)

            if (resp.data.length) {
              this.listaProdutosCancelados = resp.data
              this.modalProdutosCancelados = true
            } else {
              this.listaProdutosCancelados = []
              this.modalProdutosCancelados = false
            }
          })
      } catch (error) {
        this.mostrarAviso(error)
      } finally {
        this.carregandoCancelados = false
      }
    },
    async estouCiente(item) {
      if (this.carregandoCancelados) return

      this.carregandoCancelados = true
      try {
        await MobileStockApi(`api_administracao/fornecedor/estou_ciente_cancelamento/${item.id}`, {
          method: 'DELETE',
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw Error(resp.message)

            this.buscaTemCancelados()
          })
      } catch (error) {
        this.mostrarAviso(error)
        this.carregandoCancelados = false
      }
    },
    itemGrades(campo, valor, ordernavel = false, estilizacao = '') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        align: 'center',
        class: estilizacao,
      }
    },
  },
  filters: {
    formatarDinheiro: function (dinheiro) {
      if (!dinheiro) return ''
      return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(dinheiro)
    },
  },
  mounted() {
    this.buscaTemCancelados()
    this.buscaDiasParaDesbloquearBotaoUp()
    this.buscaRequisitosMelhoresFabricantes()
    this.buscaDadosSeller()
  },
})
