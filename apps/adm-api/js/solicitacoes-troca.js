import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'
new Vue({
  el: '#solicitacoes-troca',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data: () => ({
    produtos: [],
    carregando: false,
    pagina: 1,
    ultimaPagina: false,
    usuarioInterno: false,

    produtoSelecionado: null,

    mostrarModalTroca: false,
    mostrarModalDisputa: false,
    mostrarDialogConfirmarTroca: false,
    mostrarDialogConfirmarDisputa: false,
    dialogConfirmarTrocaAprovar: false,
    dialogConfirmarDisputaAprovar: false,
    preencheTextareaTexto: null,
    dialogQrCode: false,
    dialogSolicitarNovasFotos: false,
    telefoneQrCode: 0,
    motivoReprovacao: '',
    motivoNovasFotos: '',

    mostrarSnackBar: null,
    mensagemSnackBar: '',

    pesquisa: '',
    timerPesquisa: null,
  }),
  watch: {
    pesquisa() {
      if (this.timerPesquisa) {
        clearTimeout(this.timerPesquisa)
        this.timer = null
      }
      this.timerPesquisa = setTimeout(() => {
        this.produtos = []
        this.ultimaPagina = false
        this.pagina = 1
        this.buscaProdutosTroca()
      }, 1000)
    },
  },
  computed: {
    botaoConfirmarDesabilitado() {
      return (
        this.carregando ||
        (this.mostrarDialogConfirmarTroca &&
          this.dialogConfirmarTrocaAprovar === false &&
          this.dialogSolicitarNovasFotos === false &&
          this.motivoReprovacao.trim().length < 10) ||
        (this.mostrarDialogConfirmarDisputa &&
          this.dialogConfirmarDisputaAprovar === false &&
          this.dialogSolicitarNovasFotos === false &&
          this.motivoReprovacao.trim().length < 10) ||
        (this.dialogSolicitarNovasFotos === true &&
          this.dialogConfirmarDisputaAprovar === false &&
          this.dialogConfirmarTrocaAprovar === false &&
          this.motivoNovasFotos.trim().length < 10)
      )
    },
  },
  methods: {
    async buscaProdutosTroca() {
      if (this.carregando) return
      try {
        this.carregando = true

        const parametros = new URLSearchParams({
          pagina: this.pagina,
          pesquisa: this.pesquisa,
        })

        const resposta = await api.get(`api_administracao/troca/produtos_troca?${parametros}`)
        const produtosAux = resposta.data?.map((produto) => ({
          ...produto,
          link_whatsapp_cliente: new MensagensWhatsApp({ telefone: produto.telefone_cliente }).resultado,
          link_whatsapp_vendedor: new MensagensWhatsApp({ telefone: produto.telefone_vendedor }).resultado,
        }))

        this.ultimaPagina = produtosAux.length < 100
        this.produtos = [...this.produtos, ...produtosAux]
      } catch (error) {
        this.mensagemSnackBar = error?.response?.data?.message || error?.message || 'Erro ao buscar lista de produtos'
        this.mostrarSnackBar = true
      } finally {
        this.carregando = false
      }
    },
    abrirModalTroca(produto) {
      this.produtoSelecionado = produto
      this.mostrarModalTroca = true
    },
    abrirModalDisputa(produto) {
      this.produtoSelecionado = produto
      this.mostrarModalDisputa = true
    },
    abrirDialogConfirmarTroca(aprovar = true) {
      this.mostrarDialogConfirmarTroca = true
      this.dialogConfirmarTrocaAprovar = aprovar
    },
    abrirDialogConfirmarDisputa(aprovar = true) {
      this.mostrarDialogConfirmarDisputa = true
      this.dialogConfirmarDisputaAprovar = aprovar
    },
    fecharModais() {
      this.produtoSelecionado = null
      this.mostrarModalTroca = false
      this.mostrarModalDisputa = false
      this.mostrarDialogConfirmarTroca = false
      this.dialogSolicitarNovasFotos = false
      this.motivoReprovacao = ''
    },
    aprovarSolicitacaoTroca() {
      if (this.carregando) return
      this.carregando = true
      api
        .post('api_administracao/troca/aprovar_solicitacao_troca', {
          id_troca: this.produtoSelecionado?.id_solicitacao,
        })
        .then(() => {
          this.mensagemSnackBar = 'Troca aprovada com sucesso!'
          this.mostrarSnackBar = true
          this.fecharModais()
          this.recarregarProdutos()
        })
        .catch(this.catchAux)
        .finally(this.finallyAux)
    },
    recusarSolicitacaoTroca() {
      if (this.carregando) return
      this.carregando = true
      MobileStockApi('api_administracao/troca/recusar_solicitacao_troca', {
        method: 'POST',
        body: JSON.stringify({
          id_troca: this.produtoSelecionado?.id_solicitacao,
          motivo: this.motivoReprovacao,
          origem: this.produtoSelecionado?.origem,
        }),
      })
        .then(this.thenAux)
        .then(() => {
          this.mensagemSnackBar = 'Troca recusada com sucesso!'
          this.mostrarSnackBar = true
          this.fecharModais()
          this.recarregarProdutos()
        })
        .catch(this.catchAux)
        .finally(this.finallyAux)
    },
    resolverDisputa() {
      if (this.carregando) return
      this.carregando = true
      api
        .post('api_administracao/troca/resolver_disputa', {
          id_troca: this.produtoSelecionado?.id_solicitacao,
          acao: this.dialogConfirmarDisputaAprovar ? 'APROVADO' : 'REPROVADA_NA_DISPUTA',
          motivo: this.motivoReprovacao,
        })
        .then(() => {
          this.mensagemSnackBar = `Disputa ${
            this.dialogConfirmarDisputaAprovar ? 'aprovada' : 'reprovada'
          } com sucesso!`
          this.mostrarSnackBar = true
          this.fecharModais()
          this.recarregarProdutos()
        })
        .catch(this.catchAux)
        .finally(this.finallyAux)
    },
    recarregarProdutos() {
      this.pagina = 1
      this.produtos = []
      this.carregando = false
      this.buscaProdutosTroca()
    },
    async thenAux(response) {
      return await response.json()
    },
    catchAux(error) {
      this.mensagemSnackBar = error?.response?.data?.message || error?.message
      this.mostrarSnackBar = true
    },
    finallyAux() {
      this.carregando = false
    },
    atualizarSituacaoProduto(produto, novaSituacao) {
      const index = this.produtos?.findIndex((p) => p.uuid_produto === produto.uuid_produto)
      if (index < 0) return
      this.produtos[index].situacao_solicitacao = novaSituacao
    },
    formatarTelefone(telefone = '') {
      return telefone.replace(/[^0-9]/g, '').replace(/([0-9]{2})([0-9]{5})([0-9]{4})/, '($1) $2-$3')
    },
    abreModalQrCode(ehSeller, produto) {
      this.dialogQrCode = true
      this.telefoneQrCode = ehSeller
        ? produto.link_whatsapp_vendedor
        : (this.telefoneQrCode = produto.link_whatsapp_cliente)
    },
    verMais() {
      this.pagina += 1
      this.buscaProdutosTroca()
    },
    preencherTextArea() {
      let texto = 'Para produto que não serviu ou não tenha gostado, você pode escolher a opção '
      texto += this.produtoSelecionado?.origem === 'ML' ? "'Não serviu' ou 'não gostou'" : 'Troca Normal'
      texto +=
        'quando for solicitar a devolução. Lembre-se que o cliente tem até 7 dias após a retirada do produto para solicitar a devolução. Esta área é somente para análise de Defeitos de Fabricação.'
      this.motivoReprovacao = texto
    },
    abreModalSolicitarNovasFotos() {
      this.dialogSolicitarNovasFotos = true
    },
    preencherTextAreaNovasFotos() {
      this.motivoNovasFotos =
        'As fotos enviadas não apresentam os defeitos relatados ou não estão legíveis. Por favor, envie novas fotos detalhando os defeitos do produto.'
    },
    enviarSolicitacaoNovasFotos() {
      if (this.carregando) return
      this.carregando = true
      MobileStockApi(`api_administracao/troca/reprova_por_foto`, {
        method: 'POST',
        body: JSON.stringify({
          id_troca: this.produtoSelecionado?.id_solicitacao,
          motivo: this.motivoNovasFotos,
          origem: this.produtoSelecionado?.origem,
        }),
      })
        .then(this.thenAux)
        .then(() => {
          this.mensagemSnackBar = 'Solicitação de novas fotos enviada com sucesso!'
          this.mostrarSnackBar = true
          this.fecharModais()
          this.recarregarProdutos()
        })
        .catch(this.catchAux)
        .finally(this.finallyAux)
    },
  },
  mounted() {
    const nivelAcesso = document.getElementsByName('nivelAcesso')[0].value
    this.usuarioInterno = nivelAcesso.match(/50|51|52|53|54|55|56|57/)?.length
    this.buscaProdutosTroca()
  },
})
