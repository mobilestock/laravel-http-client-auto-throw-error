Vue.component('referencias', {
  template: '#referencias',
  props: ['produto'],
})

Vue.component('entradas', {
  template: '#entradas',
  props: ['produtos'],
})

Vue.component('compras', {
  template: '#compras',
  props: ['compras'],
})

Vue.component('faturamentos', {
  template: '#faturamentos',
  props: ['faturamentos'],
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
        Compras: 0,
        'Ag. Entrada': 0,
        Transacoes: 0,
        Trocas: 0,
      },
      busca: [],
      produtosAutocomplete: [],
      numerosAutocomplete: ['P', 'M', 'G', 'GG'],
      timeout: null,
      qtdReqAtivas: 0,
      loading: false,
    }
  },

  methods: {
    async buscaProduto() {
      this.loading = true
      try {
        await MobileStockApi('api_administracao/produtos/busca_produtos', {
          method: 'POST',
          body: JSON.stringify({
            pesquisa: this.produto,
            nome_tamanho: this.tamanho,
          }),
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.opcoesRelatorio['Ag. Entrada'] = resp.data.aguardandoEntrada.length
              this.opcoesRelatorio['Transacoes'] = resp.data.faturamentos.length
              this.opcoesRelatorio['Compras'] = resp.data.compras.length
              this.opcoesRelatorio['Trocas'] = resp.data.trocas.length
              this.opcoesRelatorio['Referencias'] =
                resp.data.trocas.length +
                resp.data.faturamentos.length +
                resp.data.aguardandoEntrada.length +
                resp.data.compras.length
              this.busca = resp.data
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.opcoesRelatorio['Ag. Entrada'] = 0
        this.opcoesRelatorio['Transacoes'] = 0
        this.opcoesRelatorio['Compras'] = 0
        this.opcoesRelatorio['Trocas'] = 0
        this.opcoesRelatorio['Referencias'] = 0
        this.busca = []
      } finally {
        this.loading = false
      }
    },

    autocompleta() {
      this.timeout = setTimeout(async () => {
        let form = new FormData()
        form.append('action', 'buscaProdutos'), form.append('nome', this.produto)

        this.qtdReqAtivas++

        let json = await fetch('controle/indexController.php', {
          method: 'POST',
          body: form,
        }).then((r) => {
          this.qtdReqAtivas--
          return r.json()
        })

        this.produtosAutocomplete = json.produtos
      }, 1000)
    },

    selecionaProduto(selecionado) {
      clearTimeout(this.timeout)
      this.produto = selecionado
      this.buscaProduto()
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
