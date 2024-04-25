var comprasVue = new Vue({
  el: '#comprasVue',
  vuetify: new Vuetify(),
  data: {
    filtros: {
      fornecedor: '',
      id: '',
      referencia: '',
      data_inicial_emissao: '',
      data_fim_emissao: '',
      data_inicial_previsao: '',
      data_fim_previsao: '',
      tamanho: '',
      situacao: '',
    },
    headers: [
      {
        text: 'Número',
        align: 'start',
        value: 'id',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Fornecedor',
        value: 'fornecedor',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Situação',
        value: 'situacao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Valor Total',
        value: 'valor_total',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Emissão',
        value: 'data_emissao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Previsão',
        value: 'data_previsao',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Editar',
        value: '',
        align: 'center',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Códigos de Barras',
        value: '',
        align: 'center',
        filterable: false,
        sortable: false,
      },
    ],
    menu: false,
    menu2: false,
    dialog: false,
    selectFornecedor: [],
    datesEmissao: [],
    datesPrevisao: [],
    listaSituacoes: [
      { id: 1, situacao: 'Em Aberto' },
      { id: 14, situacao: 'Parcialmente Entregue' },
      { id: 3, situacao: 'Em Aberto / Parcialmente' },
      { id: 2, situacao: 'Entregue' },
    ],
    buscaFornecedor: '',
    fornecedor: false,
    filtroTabela: '',
    listaCompras: [],
    listaCodigoBarras: [],
    pagina: 1,
    itemsPorPagina: 25,
    loading: true,
    overlay: false,
    options: {},
    snackbar: {
      text: '',
      color: 'green accent-4',
      open: false,
    },
    valorTamanhoDescrito: '',
  },
  mounted() {
    if ($('#cabecalhoVue input[name=nivelAcesso]').val() == 30) {
      this.fornecedor = true
      this.buscaIdFornecedorUsuario($('#cabecalhoVue input[name=userID]').val())
    }
  },
  filters: {
    moneyMask(value) {
      //converte string em formato moeda
      if (value) {
        let sinal = Math.sign(parseFloat(value)) == -1 ? '-' : ''
        var v = value.replace(/\D/g, '')
        v = (v / 100).toFixed(2) + ''
        v = v.replace('.', ',')
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, '$1.$2.$3,')
        v = v.replace(/(\d)(\d{3}),/g, '$1.$2,')
        return 'R$ ' + sinal + v
      }
    },
  },
  computed: {
    dateRangeText() {
      return this.filtros.data_inicial_emissao && this.filtros.data_fim_emissao
        ? this.converteData(this.filtros.data_inicial_emissao) +
            ' - ' +
            this.converteData(this.filtros.data_fim_emissao)
        : 'Selecione uma data'
    },
    dateRangeTextPrevisao() {
      return this.filtros.data_inicial_previsao && this.filtros.data_fim_previsao
        ? this.converteData(this.filtros.data_inicial_previsao) +
            ' - ' +
            this.converteData(this.filtros.data_fim_previsao)
        : 'Selecione uma data'
    },
  },
  watch: {
    buscaFornecedor(val) {
      val && val !== this.filtros.fornecedor && this.buscaFornecedorPeloNome(val)
    },
    datesEmissao(val) {
      this.filtros.data_inicial_emissao = val[0]
      this.filtros.data_fim_emissao = val[1]
    },
    datesPrevisao(val) {
      this.filtros.data_inicial_previsao = val[0]
      this.filtros.data_fim_previsao = val[1]
    },
    options: {
      handler() {
        this.buscaListaCompras()
      },
      deep: true,
    },
  },
  methods: {
    converteData: function (data) {
      if (!data) return ''
      data = data.toString()
      return data.substring(0, 10).split('-').reverse().join('/')
    },
    async buscaListaCompras(clear = false) {
      this.loading = true
      try {
        const { page, itemsPerPage } = this.options
        if (clear) this.listaCompras = []

        await MobileStockApi('api_administracao/compras/busca_lista_compras', {
          method: 'POST',
          body: JSON.stringify({
            id_compra: this.filtros.id,
            id_fornecedor: this.filtros.fornecedor,
            referencia: this.filtros.referencia,
            nome_tamanho: this.filtros.tamanho,
            situacao: this.filtros.situacao,
            data_inicial_emissao: this.filtros.data_inicial_emissao,
            data_fim_emissao: this.filtros.data_fim_emissao,
            data_inicial_previsao: this.filtros.data_inicial_previsao,
            data_fim_previsao: this.filtros.data_fim_previsao,
            itens: itemsPerPage,
            pagina: page ?? 1,
          }),
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.listaCompras = resp.data
              this.itemsPorPagina = page * itemsPerPage + itemsPerPage
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.snackbar = {
          open: true,
          color: 'error',
          text: error,
        }
      } finally {
        this.loading = false
      }
    },
    async buscaFornecedorPeloNome(nome) {
      try {
        this.loading = true
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })
        const resposta = await api.get(`api_administracao/fornecedor/busca_fornecedores?${parametros}`)

        this.selectFornecedor = resposta.data.map((fornecedor) => ({
          ...fornecedor,
          nome: `${fornecedor.id} - ${fornecedor.nome}`,
        }))
      } catch (error) {
        this.snackbar = {
          open: true,
          color: 'error',
          text: error?.response?.data?.message || error?.message || 'Erro ao buscar fornecedores',
        }
      } finally {
        this.loading = false
      }
    },
    async buscaIdFornecedorUsuario(idFornecedor) {
      $.ajax({
        type: 'POST',
        url: 'controle/indexController.php',
        dataType: 'json',
        data: {
          action: 'buscaIdFornecedorUsuario',
          userId: idFornecedor,
        },
      }).done(
        function (json) {
          if (json.status == 'ok') {
            this.$set(this.filtros, 'fornecedor', json.idFornecedor)
            this.buscaListaCompras(true)
          } else {
            console.log(json.mensagem)
          }
        }.bind(this),
      )
    },
    async buscaCodigoBarrasCompra(idCompra) {
      try {
        await MobileStockApi(`api_administracao/compras/busca_codigo_barras_compra/${idCompra}`, {
          method: 'GET',
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.listaCodigoBarras = resp.data
              this.dialog = true
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.snackbar.open = true
        this.snackbar.color = 'error'
        this.snackbar.text = error
      }
    },
    // async buscaCodigoBarrasCompra(idCompra) {
    //   $.ajax({
    //     type: "POST",
    //     url: "controle/indexController.php",
    //     dataType: "json",
    //     data: {
    //       action: "buscaCodigoBarrasCompra",
    //       id: idCompra
    //     },
    //     beforeSend: function () {
    //       this.overlay = true;
    //     }.bind(this),
    //   }).done(
    //     function (json) {
    //       this.overlay = false;
    //       if (json.status == "ok") {
    //         this.listaCodigoBarras = json.codigoBarras;
    //         this.dialog = true;
    //       } else {
    //         console.log(json.mensagem);
    //       }
    //     }.bind(this)
    //   );
    // },
    async baixarItemCompra(tamanhoParaFoto, item, index) {
      if (item.caminho === '' && item.situacao == 1 && !tamanhoParaFoto) {
        this.snackbar.open = true
        this.snackbar.color = 'error'
        this.snackbar.text = 'Informe o tamanho para foto'
        return
      }

      tamanhoParaFoto = tamanhoParaFoto ? tamanhoParaFoto : 90

      idUsuarioLogado = window.localStorage.getItem('idUsuarioLogado')
      MobileStockApi(`api_administracao/compras/entrada?tamanho=${tamanhoParaFoto}`, {
        method: 'POST',
        body: JSON.stringify({
          codigos: [item.codigo_barras],
        }),
      })
        .then((res) => res.json())
        .then((json) => {
          this.overlay = false

          if (json.status) {
            this.listaCodigoBarras[index].situacao = 2
          }

          this.snackbar.color = json.status ? 'green accent-4' : 'error'
          this.snackbar.text = json.message
          this.snackbar.open = true
        })
        .catch((err) => {
          console.error(err)
          this.snackbar.color = 'error'
          this.snackbar.text = 'Erro ao tentar dar entrada na compra'
          this.snackbar.open = true
        })
    },

    // atualizaNumeracaoProdutosIguais(id_produto, val) {
    //   this.listaCodigoBarras.filter(cod => cod.situacao == 1 && cod.id_produto == id_produto)
    //     .forEach((caixa, key) => {
    //       this.$set(this.listaCodigoBarras[key], 'tamanhoParaFoto', val);
    //     });
    // },
  },
})
