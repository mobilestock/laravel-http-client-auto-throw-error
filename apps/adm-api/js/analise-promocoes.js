import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#analisePromocoes',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      cabecalhos: [
        this.itemHeaderDataTable('Foto', 'foto_produto'),
        this.itemHeaderDataTable('Produto', 'nome_produto'),
        this.itemHeaderDataTable('Valores Antigos', 'valores_antigo'),
        this.itemHeaderDataTable('Porcentagem', 'porcentagem', 'center'),
        this.itemHeaderDataTable('Valores Novos', 'valores_novo'),
        this.itemHeaderDataTable('Data Promoção', 'data_atualizou_valor_custo', 'center'),
        this.itemHeaderDataTable('Ações', 'acoes', 'center'),
      ],
      itens: [],
      carregando: false,
      pesquisa: '',
      timer: null,
      promocaoDesativar: null,
      snack: {
        mostrar: false,
        mensagem: '',
      },
    }
  },
  watch: {
    pesquisa(novo) {
      clearTimeout(this.timer)
      this.timer = setTimeout(() => {
        this.buscaPromocoesParaAnalise(novo)
      }, 1000)
    },
  },
  methods: {
    abrirLinkWhatsapp(produto) {
      const url = new MensagensWhatsApp({
        telefone: produto.telefone_colaborador,
        mensagem:
          `Olá, ${produto.nome_colaborador}.\n` +
          `Observamos que o preço promocional do seu produto "${produto.nome_produto}" parece estar muito alto.` +
          'Pedimos que ajuste para um valor realista nos próximos 24 horas, conforme nossas políticas.\n\n ' +
          'Contamos com sua compreensão e cooperação imediata.' +
          'Obrigado',
      }).resultado
      window.open(url, '_blank')
    },
    buscaPromocoesParaAnalise(pesquisa = '') {
      this.carregando = true
      api
        .get(`api_administracao/produtos/busca_promocoes_analise?pesquisa=${pesquisa}`)
        .then((res) => {
          this.itens = res.data.map((item) => {
            if (item.porcentagem <= 40) item.estilo_porcentagem = 'promocao-baixa'
            else if (item.porcentagem <= 70) item.estilo_porcentagem = 'promocao-media'
            else item.estilo_porcentagem = 'promocao-alta'
            item.nome_produto = item.nome_produto.toLowerCase()
            item.nome_colaborador = item.nome_colaborador.toLowerCase()
            item.valores_venda.ms = formataMoeda(item.valores_venda.ms)
            item.valores_venda.ml = formataMoeda(item.valores_venda.ml)
            item.valores_venda_historico.ms = formataMoeda(item.valores_venda_historico.ms)
            item.valores_venda_historico.ml = formataMoeda(item.valores_venda_historico.ml)
            return item
          })
        })
        .catch((error) => {
          this.snack = {
            mostrar: true,
            mensagem: error.response?.data.message || error.message,
          }
        })
        .finally(() => (this.carregando = false))
    },
    itemHeaderDataTable(label, valor, alinhamento = 'left') {
      return {
        text: label,
        align: alinhamento,
        sortable: true,
        value: valor,
      }
    },
    desativarPromocao() {
      this.carregando = true
      api
        .post(`api_administracao/produtos/desativa_promocao_mantem_valores/${this.promocaoDesativar.id_produto}`)
        .then(() => this.buscaPromocoesParaAnalise())
        .catch((error) => {
          this.snack = {
            mostrar: true,
            mensagem: error.response?.data.message || error.message,
          }
        })
        .finally(() => {
          this.carregando = false
          this.promocaoDesativar = null
        })
    },
  },
  mounted() {
    this.buscaPromocoesParaAnalise()
  },
})
