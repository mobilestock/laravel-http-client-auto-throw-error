import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var monitoraSellerVUE = new Vue({
  el: '#monitoraSellerVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      isLoading: false,
      isLoadingDetalhes: false,
      isLoadingProdutos: false,
      isLoadingCancelar: false,
      snackbar: false,
      expande: false,
      expandeQRCliente: false,
      modalDetalhes: false,
      modalProdutos: false,
      modalQrCode: false,
      modalQrCodeCliente: false,
      maisPaginas: false,
      pagina: 1,
      textoSnackbar: '',
      pesquisaSeller: '',
      qrCodeCliente: '',
      colorSnackbar: 'error',
      listaDeSellers: [],
      listaDetalhesSeller: [],
      listaDeProdutos: [],
      sellerSelecionado: [],
      transacaoSelecionada: [],
      headersSeller: [
        {
          text: 'ID',
          align: 'center',
          value: 'id',
        },
        {
          text: 'Nome',
          align: 'center',
          value: 'razao_social',
        },
        {
          text: 'Telefone',
          align: 'center',
          value: 'telefone',
        },
        {
          text: 'Cidade',
          align: 'center',
          value: 'cidade',
        },
        {
          text: 'Estado',
          align: 'center',
          value: 'estado',
        },
        // {
        //   text: "Data última separação",
        //   align: "center",
        //   value: "ultima_separacao",
        // },
        {
          text: 'Detalhes',
          align: 'center',
          sortable: false,
          value: 'acoes',
        },
      ],
      headersDetalhes: [
        // {
        //   text: "Data da venda",
        //   align: "center",
        //   value: "data_criacao",
        // },
        {
          text: 'Ponto de retirada',
          align: 'center',
          value: 'nome_ponto',
        },
        {
          text: 'Quantidade de pares',
          align: 'center',
          value: 'qtd_pares',
        },
        {
          text: 'Valor',
          align: 'center',
          value: 'valor_total',
        },
        // {
        //   text: "Status",
        //   align: "center",
        //   value: "status",
        // },
        {
          text: 'Produtos do pedido',
          align: 'center',
          sortable: false,
          value: 'abrir_produtos',
        },
      ],
      headersProdutos: [
        {
          text: 'ID do produto',
          align: 'center',
          value: 'id_produto',
        },
        {
          text: 'Data da venda',
          align: 'center',
          value: 'data_compra',
        },
        {
          text: 'Data da correção',
          align: 'center',
          value: 'data_correcao',
        },
        {
          text: 'Produto',
          align: 'start',
          sortable: false,
          value: 'foto',
        },
        {
          text: 'Produto',
          align: 'center',
          value: 'nome_comercial',
        },
        {
          text: 'Tamanho',
          align: 'center',
          value: 'nome_tamanho',
        },
        {
          text: 'Cliente',
          align: 'center',
          value: 'razao_social',
        },
      ],
    }
  },
  methods: {
    async buscaListaSeller() {
      this.isLoading = true
      try {
        const resposta = await api.get(`api_administracao/estoque_externo/lista_fornecedores/${this.pagina}`)
        this.listaDeSellers = this.listaDeSellers.concat(resposta.data.sellers)
        this.maisPaginas = resposta.data.mais_pags
      } catch (error) {
        this.enqueueSnackbar(
          true,
          'error',
          error?.response?.data?.message || error?.message || 'Erro na busca de sellers',
        )
      } finally {
        this.isLoading = false
      }
    },
    async buscaDetalhesSeller(item) {
      this.isLoadingDetalhes = true
      this.listaDetalhesSeller = []
      try {
        await MobileStockApi(`api_administracao/estoque_externo/busca_detalhes_por_seller/${item.id}`)
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.listaDetalhesSeller = resp.data.pedidos
              this.sellerSelecionado = resp.data.seller
              this.modalDetalhes = true
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.enqueueSnackbar(true, 'error', error.message)
      } finally {
        this.isLoadingDetalhes = false
      }
    },
    async buscaProdutos(item) {
      this.isLoadingProdutos = true
      this.transacaoSelecionada = item
      try {
        const parametros = new URLSearchParams({
          id_responsavel_estoque: this.sellerSelecionado.id,
          transacoes: JSON.stringify(item.transacoes),
        })
        const retorno = await api.get(`api_administracao/estoque_externo/produtos_fornecedor?${parametros}`)

        this.listaDeProdutos = retorno.data
        this.modalProdutos = !!retorno.data.length
      } catch (error) {
        this.enqueueSnackbar(
          true,
          'error',
          error?.response?.data?.message || error?.message || 'Erro na busca de produtos',
        )
      } finally {
        this.isLoadingProdutos = false
      }
    },
    // async corrigirPorTransacao(item) {
    //   this.isLoadingCancelar = true;
    //   try {
    //     await MobileStockApi(
    //       "api_administracao/estoque_externo/corrige_faturamento_por_transacao",
    //       {
    //         method: "POST",
    //         body: JSON.stringify({
    //           id_faturamento:
    //             item.id_faturamento !== "-" ? item.id_faturamento : 0,
    //           id_responsavel: this.sellerSelecionado.id,
    //           id_ponto: item.id_tipo_frete,
    //         }),
    //       }
    //     )
    //       .then((resp) => resp.json())
    //       .then(async (resp) => {
    //         if (resp.status) {
    //           this.enqueueSnackbar(
    //             true,
    //             "success",
    //             "Correção concluída com sucesso!"
    //           );
    //           await this.buscaDetalhesSeller(this.sellerSelecionado);
    //         } else {
    //           throw new Error(resp.message);
    //         }
    //       });
    //   } catch (error) {
    //     this.enqueueSnackbar(true, "error", error.message);
    //   } finally {
    //     this.isLoadingCancelar = false;
    //   }
    // },
    abrirModalQrCode(acao) {
      this.modalQrCode = acao
      this.expande = acao
    },
    abrirModalQrCodeCliente(item) {
      this.qrCodeCliente = item.qr_code
      this.modalQrCodeCliente = true
      this.expandeQRCliente = true
    },
    formataValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },
    formataReputacaoSeller(item) {
      const reputacao = item?.situacao?.replace(/_/, ' ')

      return reputacao
    },
    enqueueSnackbar(ativar, cor = 'error', texto = 'Erro') {
      this.snackbar = ativar
      this.colorSnackbar = cor
      this.textoSnackbar = texto
    },
  },
  mounted() {
    this.buscaListaSeller()
  },
  watch: {
    pagina() {
      this.buscaListaSeller()
    },
  },
})
