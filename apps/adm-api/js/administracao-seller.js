new Vue({
  el: "#administracao-seller",
  vuetify: new Vuetify({}),
  data: () => ({
    carregandoRequisicao: false,
    tokenTemporario: '',
    mostrarSnackBar: false,
    mensagemSnackBar: '',
  }),
  methods: {
    irParaTela(url) {
      window.location.href = url
    },
    async irParaVendas() {
      if (this.carregandoRequisicao) return
      try {
        if (this.tokenTemporario === '') {
          this.carregandoRequisicao = true
          const response = await MobileStockApi('api_administracao/cadastro/link_logado', { method: 'POST' })
          const json = await response.json()
          if (!json.status) throw new Error(json.message)
          this.tokenTemporario = json.data.link
        }
        const urlBase = document.getElementsByName('url-meulook')[0].value
        const token = this.tokenTemporario
        const parametro = encodeURI('/usuario/comissoes')
        const url = `${urlBase}/entrar/${token}?redirecionar_para=${parametro}`
        window.open(url, '_blank')
      } catch (error) {
        this.mensagemSnackBar = error.message || 'Erro ao acessar vendas'
        this.mostrarSnackBar = true
      } finally {
        this.carregandoRequisicao = false
      }
    },
    async irParaEstoque() {
      const resposta = await api.get('api_administracao/cadastro/busca/colaboradores')
      const idColaborador = resposta.data.id_colaborador
      this.irParaTela(`estoque-detalhado.php?id=${idColaborador}`)
    }
  }
})
