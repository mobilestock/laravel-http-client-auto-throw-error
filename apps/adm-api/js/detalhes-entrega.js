import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      loading: false,
      id_entrega: document.getElementById('id-entrega').value,
      id_produto_frete: parseInt(document.getElementById('id-produto-frete').value),
      snackbar: {
        ativar: false,
        cor: '',
        texto: '',
      },
      detalhes_entrega: [],
      lista_produtos: [],
      lista_etiquetas: [],
      nome_recebedor: [],
      lista_produtos_headers: [
        this.itemGrades('Transação', 'id_transacao'),
        this.itemGrades('Data Pagamento', 'data_transacao', 'center'),
        this.itemGrades('Origem', 'origem'),
        this.itemGrades('Cliente', 'razao_social'),
        this.itemGrades('Preço', 'preco'),
        this.itemGrades('Forçar Troca', 'troca', false),
        this.itemGrades('Produto', 'descricao'),
        this.itemGrades('Tamanho', 'nome_tamanho'),
        this.itemGrades('Adicionado por', 'historico_logistica'),
        this.itemGrades('Entregue para', 'recebedor'),
        this.itemGrades('QRCode', 'qrcode'),
        this.itemGrades('Forçar Entrega', 'entrega', false),
      ],
      produtoForcarTroca: null,
      produtoForcarEntrega: null,
      qrcodeProduto: null,
      carregandoForcarEntrega: false,
      exibirQrcodeProduto: false,
      exibirEtiquetasVolume: false,
      ENTREGADOR_exibirModalMudarEntregador: false,
      ENTREGADOR_loadingAlterarEntregador: false,
      ENTREGADOR_loadingSalvarEntregador: false,
      ENTREGADOR_listaPontosEncontrados: [],
      ENTREGADOR_buscarPontosPorNome: '',
      ENTREGADOR_nomeTipoFrete: '',
      ENTREGADOR_novoTipoFrete: '',
      ENTREGADOR_mensagemAlerta: '',
      ENTREGADOR_itemSelecionado: null,
      imagemEtiquetaVolume: null,
      detalhes_relatorio_aberto: false,
      detalhes_relatorio: [],
      detalhes_relatorio_headers: [
        this.itemGrades('Cliente', 'razao_social', true),
        this.itemGrades('Telefone', 'telefone', false),
        this.itemGrades('Cidade', 'cidade', false),
        this.itemGrades('UF', 'uf', false),
        this.itemGrades('Endereço', 'endereco', false),
        this.itemGrades('Número', 'numero', false),
        this.itemGrades('Bairro', 'bairro', false),
        this.itemGrades('Complemento', 'complemento', false),
        this.itemGrades('Produtos para entregas', 'qtd_produtos', true),
        this.itemGrades('Tem troca?', 'tem_troca', true),
      ],
    }
  },

  methods: {
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },
    buscarEntrega() {
      this.loading = true
      api.get(`api_administracao/entregas/${this.id_entrega}`).then((json) => {
        const consulta = json.data
        this.detalhes_entrega = {
          data_criacao: consulta.data_atualizacao,
          destino: consulta.destino,
          volumes: consulta.volumes,
          situacao: consulta.situacao,
          saldo_cliente: consulta.saldo_cliente,
          data_expedicao: consulta?.detalhes_expedicao?.data_expedicao || '',
          usuario_expedicao: consulta?.detalhes_expedicao?.usuario_expedicao || '',
          valor_total: consulta.valor_total,
          nome_ponto: consulta.nome_ponto,
          tipo_ponto: consulta.tipo_ponto,
          tem_devolucao_pendente: consulta?.tem_devolucao_pendente,
        }
        this.lista_produtos = consulta.produtos || []

        this.lista_etiquetas = consulta.etiquetas || []
        this.loading = false
      })
    },
    async buscaInformacoesParaEntregador() {
      try {
        this.loading = true

        if (this.detalhes_relatorio.length) {
          this.detalhes_relatorio_aberto = true
          return
        }

        const resposta = await api.get(`api_administracao/entregas/busca_detalhes_entrega/${this.id_entrega}`)
        this.detalhes_relatorio = resposta.data?.map((item) => ({ ...item, telefone: formataTelefone(item.telefone) }))
        this.detalhes_relatorio_aberto = true
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar informações')
      } finally {
        this.loading = false
      }
    },
    buscarPontosFn(pesquisa) {
      this.ENTREGADOR_loadingAlterarEntregador = true
      this.ENTREGADOR_listaPontosEncontrados = []

      MobileStockApi(`api_administracao/tipo_frete/buscar?pesquisa=${pesquisa}&tipo_ponto=PM`)
        .then((res) => res.json())
        .then((response) => {
          if (!response.status) throw new Error(response.message)

          this.ENTREGADOR_listaPontosEncontrados = response.data.map((item) => {
            return {
              value: item.id,
              text: item.nome,
            }
          })
        })
        .catch((error) => {
          this.enqueueSnackbar(error.message)
        })
        .finally(() => {
          this.ENTREGADOR_loadingAlterarEntregador = false
        })
    },
    selecionaPonto(pontoSelecionado) {
      const filtro = this.ENTREGADOR_listaPontosEncontrados.filter((ponto) => ponto.value == pontoSelecionado)

      this.ENTREGADOR_novoTipoFrete = {
        nome: filtro[0].text,
        id: filtro[0].value,
      }

      this.ENTREGADOR_listaPontosEncontrados = []
      this.ENTREGADOR_mensagemAlerta = ''
    },
    limparDadosEntregador() {
      this.ENTREGADOR_novoTipoFrete = ''
      this.ENTREGADOR_nomeTipoFrete = ''
      this.ENTREGADOR_buscarPontosPorNome = ''
      this.ENTREGADOR_mensagemAlerta = ''
      this.ENTREGADOR_listaPontosEncontrados = []
      this.ENTREGADOR_loadingAlterarEntregador = false
      this.ENTREGADOR_loadingSalvarEntregador = false
      this.ENTREGADOR_itemSelecionado = null
      this.ENTREGADOR_exibirModalMudarEntregador = false
    },
    alterarEntregador(id_entrega) {
      this.ENTREGADOR_loadingSalvarEntregador = true

      api
        .patch('api_administracao/entregas/alterar_tipo_frete', {
          id_tipo_frete: this.ENTREGADOR_novoTipoFrete.id,
          id_entrega,
        })
        .then(() => {
          this.buscarEntrega()
          this.enqueueSnackbar('Entregador alterado com sucesso!', 'success')
          this.limparDadosEntregador()
        })
        .catch((error) => {
          this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao alterar entregador')
        })
        .finally(() => {
          this.ENTREGADOR_loadingSalvarEntregador = false
        })
    },
    calculaValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },
    async forcarEntrega() {
      if (this.carregandoForcarEntrega) return

      try {
        this.carregandoForcarEntrega = true

        await api.post(`api_administracao/entregas/forcar_entrega/${this.produtoForcarEntrega.uuid_produto}`)

        this.buscarEntrega()
        this.enqueueSnackbar('Forçar entrega realizado com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao forçar entrega')
      } finally {
        this.carregandoForcarEntrega = false
        this.produtoForcarEntrega = null
      }
    },
    async forcarTroca() {
      if (this.produtoForcarTroca?.loading) return

      try {
        this.produtoForcarTroca.loading = true

        await api.post('api_administracao/troca/forcar_troca', {
          uuid: this.produtoForcarTroca.uuid_produto,
          id_cliente: this.produtoForcarTroca.id_cliente,
        })

        this.buscarEntrega()
        this.enqueueSnackbar('Forçar troca realizado com sucesso!', 'success')
        this.produtoForcarTroca = null
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao forçar troca')
      } finally {
        this.produtoForcarTroca.loading = false
      }
    },
    imprimirRelatorio() {
      $('#imprimivel').printThis({
        pageTitle: `Relatório Detalhes da Entrega ${this.id_entrega}`,
        importCSS: true,
        importStyle: true,
      })
    },
    mostrarEtiquetaVolume(qrcode, id) {
      this.imagemEtiquetaVolume = {
        id,
        qrcode,
      }
    },
    itemGrades(campo, valor, ordenavel = false, estilizacao = '') {
      return {
        text: campo,
        value: valor,
        sortable: ordenavel,
        align: 'center',
        class: estilizacao,
      }
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },
  },

  watch: {
    pesquisa(valor) {
      if (valor) this.pesquisar()
    },
    exibirEtiquetasVolume: function (valor) {
      if (!valor) this.imagemEtiquetaVolume = null
    },
    qrcodeProduto: function (valor) {
      valor ? (this.exibirQrcodeProduto = true) : (this.exibirQrcodeProduto = false)
    },
    ENTREGADOR_buscarPontosPorNome(pesquisa) {
      if (!pesquisa) return
      this.loadingBuscandoPontosPorNome = true
      this.debounce(() => this.buscarPontosFn(pesquisa), 1000)
    },
  },

  mounted() {
    this.buscarEntrega()
  },
})
