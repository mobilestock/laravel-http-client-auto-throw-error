import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#meudesempenhomeulook',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      idCliente: null,
      dados: {
        vendas_totais: 0,
        vendas_entregues: 0,
        vendas_canceladas_totais: 0,
        taxa_cancelamento: 0,
        media_envio: 0,
        vendas_canceladas_recentes: 0,
        valor_vendido: 0,
        reputacao: 0,
      },
      snack: {
        mostrar: false,
        mensagem: '',
      },
    }
  },
  methods: {
    buscarDados() {
      const elements = document.getElementsByName('userIDCliente')
      this.idCliente = elements[0].value
      api
        .get(`api_administracao/fornecedor/desempenho/${this.idCliente}`)
        .then((json) => (this.dados = json.data))
        .catch((error) => {
          this.snack.mensagem = error?.response?.data?.message || error?.message || 'Erro ao buscar desempenho.'
          this.snack.mostrar = true
        })
    },
  },
  filters: {
    formatarDinheiro: (valor) => {
      if (!valor) return ''
      return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
    },
  },
  mounted() {
    this.buscarDados()
  },
})
