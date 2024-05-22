const estoqueDetalhadoVue = new Vue({
  el: '#estoqueDetalhadoVue',
  vuetify: new Vuetify(),
  data() {
    return {
      isLoading: false,
      idColaborador: 0,
      quantidadeTotal: {
        fulfillment: 0,
        externo: 0,
        aguardEntrada: 0,
        pontoRetirada: 0,
      },
      valorTotal: {
        fulfillment: 0,
        externo: 0,
        aguardEntrada: 0,
        pontoRetirada: 0,
      },
      estoqueAtual: 'FULFILLMENT',
      listaProdutos: {
        fulfillment: [],
        externo: [],
        aguardEntrada: [],
        pontoRetirada: [],
      },
      maisItens: {
        fulfillment: false,
        externo: false,
        aguardEntrada: false,
        pontoRetirada: false,
      },
      pagina: {
        fulfillment: 1,
        externo: 1,
        aguardEntrada: 1,
        pontoRetirada: 1,
      },
      snackbar: {
        ativar: false,
        cor: '',
        texto: '',
      },
    }
  },
  methods: {
    async buscaEstoque(inicial = true, pagina = 1) {
      try {
        this.isLoading = true

        switch (this.estoqueAtual) {
          case 'FULFILLMENT':
            if (this.listaProdutos.fulfillment.length > 0 && pagina === this.pagina.fulfillment) {
              return
            }
            if (this.listaProdutos.fulfillment.length > 0 && this.listaProdutos.fulfillment.length < 50) {
              this.enqueueSnackbar('Não possui mais produtos para mostrar', 'amber')
              return
            }
            this.pagina.fulfillment = pagina
            break
          case 'EXTERNO':
            if (this.listaProdutos.externo.length > 0 && pagina === this.pagina.externo) {
              return
            }
            if (this.listaProdutos.externo.length > 0 && this.listaProdutos.externo.length < 50) {
              this.enqueueSnackbar('Não possui mais produtos para mostrar', 'amber')
              return
            }
            this.pagina.externo = pagina
            break
          case 'AGUARD_ENTRADA':
            if (this.listaProdutos.aguardEntrada.length > 0 && pagina === this.pagina.aguardEntrada) {
              return
            }
            if (this.listaProdutos.aguardEntrada.length > 0 && this.listaProdutos.aguardEntrada.length < 50) {
              this.enqueueSnackbar('Não possui mais produtos para mostrar', 'amber')
              return
            }
            this.pagina.aguardEntrada = pagina
            break
          case 'PONTO_RETIRADA':
            if (this.listaProdutos.pontoRetirada.length > 0 && pagina === this.pagina.pontoRetirada) {
              return
            }
            if (this.listaProdutos.pontoRetirada.length > 0 && this.listaProdutos.pontoRetirada.length < 50) {
              this.enqueueSnackbar('Não possui mais produtos para mostrar', 'amber')
              return
            }
            this.pagina.pontoRetirada = pagina
            break
          default:
            throw new Error('Nenhum produto pode se encontrar nessa localização')
        }

        const parametros = new URLSearchParams({
          estoque: this.estoqueAtual,
          pagina: pagina,
          id_fornecedor: this.idColaborador,
        })

        const retorno = await api.get(`api_administracao/fornecedor/estoques_detalhados?${parametros}`)
        const consulta = retorno.data

        switch (this.estoqueAtual) {
          case 'FULFILLMENT':
            if (inicial) {
              this.listaProdutos.fulfillment = consulta.produtos
            } else {
              this.listaProdutos.fulfillment = this.listaProdutos.fulfillment.concat(consulta.produtos)
            }
            this.quantidadeTotal.fulfillment = consulta.quantidade_total
            this.valorTotal.fulfillment = consulta.valor_total
            this.maisItens.fulfillment = consulta.mais_pags
            break
          case 'EXTERNO':
            if (inicial) {
              this.listaProdutos.externo = consulta.produtos
            } else {
              this.listaProdutos.externo = this.listaProdutos.externo.concat(consulta.produtos)
            }
            this.quantidadeTotal.externo = consulta.quantidade_total
            this.valorTotal.externo = consulta.valor_total
            this.maisItens.externo = consulta.mais_pags
            break
          case 'AGUARD_ENTRADA':
            if (inicial) {
              this.listaProdutos.aguardEntrada = consulta.produtos
            } else {
              this.listaProdutos.aguardEntrada = this.listaProdutos.aguardEntrada.concat(consulta.produtos)
            }
            this.quantidadeTotal.aguardEntrada = consulta.quantidade_total
            this.valorTotal.aguardEntrada = consulta.valor_total
            this.maisItens.aguardEntrada = consulta.mais_pags
            break
          case 'PONTO_RETIRADA':
            if (inicial) {
              this.listaProdutos.pontoRetirada = consulta.produtos
            } else {
              this.listaProdutos.pontoRetirada = this.listaProdutos.pontoRetirada.concat(consulta.produtos)
            }
            this.quantidadeTotal.pontoRetirada = consulta.quantidade_total
            this.valorTotal.pontoRetirada = consulta.valor_total
            this.maisItens.pontoRetirada = consulta.mais_pags
            break
        }
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro, contate a equipe de T.I.')
      } finally {
        this.isLoading = false
      }
    },
    proximaPag(estoque) {
      let pagina = 1
      switch (estoque) {
        case 'FULFILLMENT':
          pagina = this.pagina.fulfillment
          break
        case 'EXTERNO':
          pagina = this.pagina.externo
          break
        case 'AGUARD_ENTRADA':
          pagina = this.pagina.aguardEntrada
          break
        case 'PONTO_RETIRADA':
          pagina = this.pagina.pontoRetirada
          break
      }

      this.buscaEstoque(false, pagina + 1)
    },
    verEstoque(estoque) {
      this.estoqueAtual = estoque
      this.buscaEstoque()
    },
    calculaValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },
  },
  mounted() {
    const parametrosUrl = new URLSearchParams(window.location.search)
    this.idColaborador = parametrosUrl.get('id')
    this.buscaEstoque()
  },
  watch: {},
})
