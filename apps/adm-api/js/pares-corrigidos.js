import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var paresCorrigidosVUE = new Vue({
  el: '#paresCorrigidosVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      produtos: [],
      pesquisa: '',
      loading: false,
      snackbar: false,
      snackColor: 'error',
      mensagem: '',
      headerProdutos: [
        this.campo('Data compra', 'data_compra'),
        this.campo('Cliente', 'nome_cliente'),
        this.campo('Seller', 'nome_fornecedor'),
        this.campo('Reputação do seller', 'reputacao'),
        this.campo('Id da transação', 'id_transacao'),
        this.campo('Id produto', 'id_produto'),
        this.campo('Tamanho', 'tamanho'),
        this.campo('Porque afetou reputação', 'porque_afetou_reputacao'),
        this.campo('Data correção', 'data_cancelamento'),
      ],
    }
  },
  methods: {
    async buscaLista() {
      try {
        this.loading = true
        const resposta = await api.get('api_administracao/produtos/cancelados')

        this.produtos = resposta.data
      } catch (error) {
        this.snackbar = true
        this.mensagem = error?.response?.data?.message || error?.message || 'Falha ao buscar lista'
      } finally {
        this.loading = false
      }
    },
    formataTexto(texto) {
      return texto?.replace(/_/g, ' ')
    },
    corPorReputacao(reputacao) {
      switch (reputacao) {
        case 'RUIM':
          return 'red lighten-5'
        case 'REGULAR':
          return 'amber lighten-5'
        case 'EXCELENTE':
          return 'green lighten-5'
        case 'MELHOR_FABRICANTE':
          return 'blue lighten-5'
        default:
          return
      }
    },
    campo(nome, campo) {
      return {
        text: nome,
        align: 'center',
        value: campo,
      }
    },
    removeAlerta() {
      this.snackbar = false
      this.mensagem = ''
    },
  },
  mounted() {
    this.buscaLista()
  },
})
