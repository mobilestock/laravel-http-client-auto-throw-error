import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#promocoesVue',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data: {
    buscaDisponiveis: '',
    buscaAtivos: '',
    rules: {
      valorMin(value, min, campo) {
        return (value) => (!!value && parseInt(value) >= min) || `O valor mínimo para ${campo} é ${min}`
      },
    },
    cabecalhoProdutosAtivos: [
      {
        text: 'Situação',
        value: 'promocao',
        sortable: false,
      },
      {
        text: 'ID',
        value: 'id',
      },
      {
        text: 'Data de cadastro',
        value: 'descricao',
      },
      {
        text: 'Grade',
        value: 'grade',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Foto',
        value: 'fotoUrl',
        filterable: false,
        sortable: false,
      },
    ],
    cabecalhoProdutosDisponivel: [
      {
        text: 'Situação',
        value: 'promocao',
        sortable: false,
      },
      {
        text: 'ID',
        value: 'id',
      },
      {
        text: 'Data de cadastro',
        value: 'descricao',
      },
      {
        text: 'Grade',
        value: 'grade',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Foto',
        value: 'fotoUrl',
        filterable: false,
        sortable: false,
      },
    ],
    produtos: {
      disponiveis: [],
      ativos: [],
    },
    gradeXtamanhosDisponivel: [],
    gradeXtamanhosAtivo: [],
    exibeModal: false,
    overlay: false,
    loading: false,
    modalDeAlerta: false,
    loadingSalvaPromocao: false,
    loadingRemovePromocao: false,
    travaRemocaoDeValores: false,
    mensagemDeAlerta: '',
    produtosSelecionadosParaPromocao: [],
    conteudoModal: {},
    parametrosModal: {
      valorVendaMS: 0,
      valorVendaHistoricoMS: 0,
      valorVendaML: 0,
      valorVendaHistoricoML: 0,
      valorBase: 0,
      valorBaseHistorico: 0,
      pontuacao: 0,
      porcentagemComissaoMS: 0,
      porcentagemComissaoML: 0,
      porcentagemPromocao: 0,
      tempoRestanteAtivarPromocao: 0,
      faltaUmaEntregaParaAtivarPromocao: false,
      fotoProduto: '',
    },
    informacoesAplicarPromocao: {
      porcentagemMinimaDescontoPromocaoTemporaria: 0,
      horasDuracaoPromocaoTemporaria: 0,
      horasEsperaReativarPromocao: 0,
    },
    slider: 0,
  },
  methods: {
    goBack() {
      history.back()
    },
    removerSelecioados() {
      this.produtos.disponiveis.forEach((item, index) => {
        item.promocao = false
      })
      this.produtosSelecionadosParaPromocao = []
    },
    removePromocao() {
      this.travaRemocaoDeValores = true
      this.produtosSelecionadosParaPromocao.push({
        promocao: 0,
        id: this.conteudoModal.id,
      })
      this.enviaDados().then(() => {
        this.produtosSelecionadosParaPromocao.pop()
        this.conteudoModal = {}
        this.travaRemocaoDeValores = false
      })
    },
    removeTodasAsPromocoes() {
      this.travaRemocaoDeValores = true
      this.loadingRemovePromocao = true
      this.produtos.ativos.forEach((item, index) => {
        this.produtosSelecionadosParaPromocao.push({
          promocao: 0,
          id: item.id,
        })
      })
      this.enviaDados()
        .then(() => {
          this.produtosSelecionadosParaPromocao = []
        })
        .finally(() => {
          this.loadingRemovePromocao = false
          this.travaRemocaoDeValores = false
        })
    },
    salvaConteudo() {
      if (this.slider == 0) {
        this.modalDeAlerta = true
        this.mensagemDeAlerta = 'Para salvar a promoção você deve escolher um valor acima de 0.'
        return false
      }
      this.produtosSelecionadosParaPromocao.push({
        promocao: this.slider,
        id: this.conteudoModal.id,
      })
      this.produtos.disponiveis.forEach((item, index) => {
        if (item.id == this.conteudoModal.id) {
          item.promocao = true
        }
      })
      this.exibeModal = false
    },
    validaPromocao() {
      this.loadingSalvaPromocao = true
      this.enviaDados()
        .then(() => {
          this.produtosSelecionadosParaPromocao = []
        })
        .finally(() => {
          this.loadingSalvaPromocao = false
        })
    },
    async enviaDados() {
      try {
        await api.post('api_administracao/produtos/salva_promocao', this.produtosSelecionadosParaPromocao)
        this.buscaDados()
      } catch (error) {
        this.onCatchCompartilhado(error)
      }
    },
    async montaConteudoModal() {
      try {
        const resposta = await api.get(`api_administracao/produtos/busca_avaliacacoes_produto/${this.conteudoModal.id}`)
        this.parametrosModal = resposta.data
        this.parametrosModal.valorBaseHistorico = resposta.data.valorBase
        this.parametrosModal.valorVendaHistoricoMS = resposta.data.valorVendaMS
        this.parametrosModal.valorVendaHistoricoML = resposta.data.valorVendaML
        this.parametrosModal.fotoProduto = this.conteudoModal.fotoUrl
        this.slider = this.parametrosModal.porcentagemPromocao
      } catch (error) {
        this.onCatchCompartilhado(error)
      }
    },
    async buscaDados() {
      await Promise.all([
        api
          .get('api_administracao/produtos/busca_produtos_disponiveis')
          .then((resp) => {
            const produtos = resp.data || []
            let disponiveis = []
            this.produtos.disponiveis = produtos
            for (let i in produtos) {
              disponiveis.push(produtos[i].gradeTotal)
            }
            this.gradeXtamanhosDisponivel = disponiveis
          })
          .catch(this.onCatchCompartilhado),
        api
          .get('api_administracao/produtos/busca_produtos_promovidos')
          .then((resp) => {
            const produtos = resp.data || []
            let ativos = []
            this.produtos.ativos = produtos
            for (let i in produtos) {
              ativos.push(produtos[i].gradeTotal)
            }
            this.gradeXtamanhosAtivo = ativos
          })
          .catch(this.onCatchCompartilhado),
        api
          .get('api_administracao/configuracoes/busca_informacoes_aplicar_promocao')
          .then((resp) => {
            const {
              PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA,
              HORAS_DURACAO_PROMOCAO_TEMPORARIA,
              HORAS_ESPERA_REATIVAR_PROMOCAO,
            } = resp.data
            this.informacoesAplicarPromocao.porcentagemMinimaDescontoPromocaoTemporaria =
              PORCENTAGEM_MINIMA_DESCONTO_PROMOCAO_TEMPORARIA
            this.informacoesAplicarPromocao.horasDuracaoPromocaoTemporaria = HORAS_DURACAO_PROMOCAO_TEMPORARIA
            this.informacoesAplicarPromocao.horasEsperaReativarPromocao = HORAS_ESPERA_REATIVAR_PROMOCAO
          })
          .catch(this.onCatchCompartilhado),
      ]).finally(() => {
        this.overlay = false
      })
    },
    onCatchCompartilhado(error) {
      this.modalDeAlerta = true
      this.mensagemDeAlerta = error.response?.data.message || error.message
    },
  },
  watch: {
    exibeModal(val) {
      if (val == false) {
        this.conteudoModal = {}
        this.exibeModal = false
        this.parametrosModal = this.parametrosModalVazio
      } else {
        this.produtos.disponiveis.forEach((item, index) => {
          if (item.id == this.conteudoModal.id) {
            item.promocao = false
          }
        })
      }
    },
    slider(val) {
      const multiplicadorDesconto = 1 - val / 100
      this.parametrosModal.valorBase = this.parametrosModal.valorBaseHistorico * multiplicadorDesconto
      this.parametrosModal.valorVendaMS = this.parametrosModal.valorVendaHistoricoMS * multiplicadorDesconto
      this.parametrosModal.valorVendaML = this.parametrosModal.valorVendaHistoricoML * multiplicadorDesconto
    },
  },
  async mounted() {
    this.overlay = true
    this.parametrosModalVazio = this.parametrosModal
    this.buscaDados()
  },
  computed: {
    formataValorBase() {
      return this.$options.filters.formataValor(this.parametrosModal.valorBase)
    },
    formataValorVendaMS() {
      return this.$options.filters.formataValor(this.parametrosModal.valorVendaMS)
    },
    formataValorVendaML() {
      return this.$options.filters.formataValor(this.parametrosModal.valorVendaML)
    },
    podeAplicarPromocao() {
      return (
        this.parametrosModal.tempoRestanteAtivarPromocao === '' &&
        this.parametrosModal.faltaUmaEntregaParaAtivarPromocao === false
      )
    },
    botaoSalvarDesabilitado() {
      return !this.podeAplicarPromocao || this.slider === this.parametrosModal.porcentagemPromocao
    },
  },
  filters: {
    formataValor(valor) {
      if (!valor) {
        valor = 0
      }
      return valor.toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
  },
})
