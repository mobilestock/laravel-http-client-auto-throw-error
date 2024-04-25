import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#datas-entrega',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      carregando: false,
      cabecalho: [
        this.itemGrade('UUID', 'uuid_produto'),
        this.itemGrade('Cliente', 'cliente', 'center'),
        this.itemGrade('Ponto', 'ponto', 'center'),
        this.itemGrade('Produto', 'produto', 'center'),
        this.itemGrade('Tamanho', 'nome_tamanho', 'center'),
        this.itemGrade('Data Retirada', 'data_retirada', 'center'),
        this.itemGrade('Data Base Troca', 'data_base_troca'),
      ],
      itens: [],
      busca: '',
      pagina: 1,
      ultimaPagina: 1,
      timer: null,
      itensPorPagina: 100,
      snackBar: {
        show: false,
        message: '',
      },
    }
  },
  computed: {
    itensDaPagina: function () {
      const inicio = this.pagina == 1 ? 0 : (this.pagina - 1) * this.itensPorPagina
      const fim = this.pagina * this.itensPorPagina
      return this.itens.slice(inicio, fim)
    },
  },
  watch: {
    busca: function (val) {
      clearTimeout(this.timer)
      this.timer = setTimeout(() => {
        this.itens = []
        this.pagina = 1
        this.ultimaPagina = 1
        this.buscarDados(val, 1)
      }, 750)
    },
    pagina: function (val) {
      if (val == this.ultimaPagina) this.buscarDados(this.busca, val)
      window.scrollTo({ top: 0 })
    },
  },
  methods: {
    onError(error) {
      this.snackBar.message = error.message
      this.snackBar.show = true
    },
    onFinally() {
      this.carregando = false
    },
    itemGrade(label, valor, alinhamento = 'start') {
      return {
        text: label,
        align: alinhamento,
        sortable: false,
        value: valor,
      }
    },
    buscarDados(pesquisa, pagina) {
      if (this.carregando) return
      this.carregando = true
      api
        .get(`api_administracao/entregas/itens_entregues?pesquisa=${pesquisa}&pagina=${pagina}`)
        .then((json) => {
          if (json.data.length == this.itensPorPagina) this.ultimaPagina += 1
          this.itens = this.itens.concat(json.data)
        })
        .catch(this.onError)
        .finally(this.onFinally)
    },
    alterarDataEntregaItem(uuidProduto, novaData) {
      if (this.carregando) return
      this.carregando = true
      api
        .patch('api_administracao/entregas/alterar_data_base_troca', {
          uuid_produto: uuidProduto,
          nova_data: novaData,
        })
        .catch(this.onError)
        .finally(this.onFinally)
    },
  },
  mounted() {
    this.buscarDados('', 1)
  },
})
