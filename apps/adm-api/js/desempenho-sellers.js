import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#desempenho-sellers',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      carregando: true,
      headers: [
        this.itemGrade('Id', 'id_colaborador'),
        this.itemGrade('Nome', 'nome', true),
        this.itemGrade('Dias Envio', 'media_envio', true),
        this.itemGrade('Valor Vendido', 'valor_vendido', true),
        this.itemGrade('Vendas', 'vendas_totais', true),
        this.itemGrade('Vendas Canceladas', 'vendas_canceladas_totais', true),
        this.itemGrade('Vendas Canceladas (3 Dias)', 'vendas_canceladas_recentes', true),
        this.itemGrade('Vendas Entregues', 'vendas_entregues', true),
        this.itemGrade('Taxa Cancelamento', 'taxa_cancelamento', true),
        this.itemGrade('Reputação', 'reputacao'),
      ],
      sellers: [],
      snack: {
        mostrar: false,
        mensagem: '',
      },
      urlMeulook: '',
      busca: '',
    }
  },
  methods: {
    buscaDesempenhoSellers() {
      api
        .get('api_administracao/fornecedor/desempenho')
        .then((json) => (this.sellers = json.data))
        .catch((error) => {
          this.snack.mensagem =
            error?.response?.data?.message || error?.message || 'Erro ao buscar desempenho dos fornecedores.'
          this.snack.mostrar = true
        })
        .finally(() => (this.carregando = false))
    },
    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        align: 'start',
        sortable: ordenavel,
        value: valor,
      }
    },
    buscaUrlMeulook() {
      const element = document.getElementsByName('url-meulook')
      this.urlMeulook = element[0].value
    },
  },
  filters: {
    formatarDinheiro: (valor) => {
      if (!valor) return ''
      return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
    },
  },
  mounted() {
    this.buscaUrlMeulook()
    this.buscaDesempenhoSellers()
  },
})
