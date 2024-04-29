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
      alerta: false,
      mensagem: '',
      headerProdutos: [
        this.campo('Data compra', 'data_nao_formatada'),
        this.campo('Cliente', 'nome_cliente'),
        this.campo('Seller', 'seller'),
        this.campo('Reputação do seller', 'reputacao'),
        this.campo('Id da transação', 'id_transacao'),
        this.campo('Id produto', 'id_produto'),
        this.campo('Tamanho', 'tamanho'),
        this.campo('Data correção', 'data_correcao'),
      ],
    }
  },
  methods: {
    async buscaLista() {
      try {
        this.loading = true
        const resposta = await MobileStockApi('api_administracao/lista_pares_corrigidos').then((resp) => resp.json())
        this.produtos = resposta.data.produtos
      } catch (error) {
        this.alerta = true
        this.mensagem = error?.response?.data?.message || 'Falha ao buscar lista'
      } finally {
        this.loading = false
      }
    },
    campo(nome, campo) {
      return {
        text: nome,
        align: 'start',
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
