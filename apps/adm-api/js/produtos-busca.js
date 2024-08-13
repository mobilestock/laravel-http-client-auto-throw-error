Vue.component('referencias', {
  template: '#referencias',
  props: ['produto'],
})

Vue.component('transacoes', {
  template: '#transacoes',
  props: ['transacoes'],
})

Vue.component('trocas', {
  template: '#trocas',
  props: ['trocas'],

  data() {
    return {
      detalhes_trocas: [],
    }
  },

  methods: {
    buscaDetalhesTroca(uuid_produto) {
      api.get(`api_administracao/troca/detalhes_troca/${uuid_produto}`).then((resp) => {
        this.detalhes_trocas = resp.data
      })
    },
    resetaDados() {
      this.detalhes_trocas = []
    },
  },

  filters: {
    dinheiro(value) {
      return value.toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
  },
})

let app = new Vue({
  el: '#app',
  vuetify: new Vuetify(),

  data() {
    return {
      produto: '',
      tamanho: '',
      menuAtivo: 'Referencias',
      opcoesRelatorio: {
        Referencias: 0,
        Transacoes: 0,
        Trocas: 0,
      },
      busca: [],
      produtosAutocomplete: [],
      numerosAutocomplete: ['P', 'M', 'G', 'GG'],
      timeout: null,
      qtdReqAtivas: 0,
      loading: false,
      snackbar: {
        mostrar: false,
        cor: '',
        texto: '',
      },
    }
  },

  methods: {
    async buscaProduto() {
      this.loading = true
      try {
        const resposta = await api.get('api_administracao/produtos/', {
          params: { id_produto: this.produto, nome_tamanho: this.tamanho },
        })
        this.opcoesRelatorio['Transacoes'] = resposta.data.transacoes.length
        this.opcoesRelatorio['Trocas'] = resposta.data.trocas.length
        this.opcoesRelatorio['Referencias'] = resposta.data.trocas.length + resposta.data.transacoes.length
        this.busca = resposta.data
      } catch (error) {
        this.snackbar = {
          mostrar: true,
          cor: 'error',
          texto: error?.response?.data?.message || error?.message || 'Produto não encontrado',
        }
        this.opcoesRelatorio['Transacoes'] = 0
        this.opcoesRelatorio['Trocas'] = 0
        this.opcoesRelatorio['Referencias'] = 0
        this.busca = []
      } finally {
        this.loading = false
      }
    },
  },

  mounted() {
    for (i = 13; i <= 50; i++) {
      this.numerosAutocomplete.push(i)
    }

    let descricaoQuery = document.querySelector('#descricao_query').value
    if (descricaoQuery) {
      this.produto = descricaoQuery
      this.buscaProduto()
    }
  },

  watch: {
    qtdReqAtivas(newV) {
      console.log(newV)
      if (newV >= 2) {
        clearTimeout(this.timeout)
      }
    },
  },
})
