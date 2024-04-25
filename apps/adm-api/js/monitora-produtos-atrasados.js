import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

const monitoraProdutosAtrasadoVUE = new Vue({
  el: '#monitoraProdutosAtrasadoVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      isLoading: false,
      isLoadingEntregando: false,
      abreModalMaisDetalhes: false,
      abreModalQrCode: false,
      filtro: {
        todos: true,
        mobileStock: false,
        meuLook: false,
        pendente: false,
      },
      maisDetalhes: null,
      codigoQR: '',
      pesquisa: '',
      listaProdutos: [],
      itemSelecionado: [],
      headerProdutos: [
        this.itemGrades('Data adicionado na entrega', 'data_adicionado_a_entrega', true),
        this.itemGrades('Situação atual', 'situacao_atual'),
        this.itemGrades('ID entrega', 'id_entrega'),
        this.itemGrades('ID transação', 'id_transacao'),
        this.itemGrades('Foto', 'foto_produto'),
        this.itemGrades('Produto', 'id_produto'),
        this.itemGrades('Seller', 'fornecedor'),
        this.itemGrades('Transportador', 'transportador'),
        this.itemGrades('Mais Detalhes', 'mais_detalhes'),
        this.itemGrades('Forçar entrega', 'forcar_entrega'),
      ],
      snackbar: {
        ativar: false,
        texto: '',
        cor: 'error',
      },
    }
  },
  methods: {
    itemGrades(campo, valor, ordernavel = false, classe = 'text-light grey darken-3') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        class: classe,
        align: 'center',
      }
    },
    formataIndiceDetalhes(campo) {
      const novoNome = campo.replace(/_/g, ' ')

      return novoNome
    },
    iconesDetalhes(chave) {
      const lista = [
        {
          chave: 'ENTREGA',
          icone: 'mdi-package-variant-closed',
        },
        {
          chave: 'CLIENTE',
          icone: 'mdi-account',
        },
        {
          chave: 'PONTO_COLETA',
          icone: 'mdi-office-building',
        },
        {
          chave: 'ENVIO_TRANSPORTADORA',
          icone: 'mdi-dolly',
        },
        {
          chave: 'ENTREGADOR',
          icone: 'fas fa-motorcycle',
        },
        {
          chave: 'PONTO_RETIRADA',
          icone: 'mdi-city-variant',
        },
        {
          chave: 'CENTRAL',
          icone: 'mdi-store',
        },
      ]
      const icone = lista.find((itemLista) => itemLista.chave === chave)?.icone

      return icone
    },
    ordenamentoCustomizado(itens, ordenamento, decrecente) {
      if (!ordenamento.includes('data_adicionado_a_entrega')) return itens

      itens.sort((a, b) => {
        const timeStampA = this.converteTimeStamp(a.data_adicionado_a_entrega)
        const timeStampB = this.converteTimeStamp(b.data_adicionado_a_entrega)

        if (decrecente[0]) return timeStampB - timeStampA
        return timeStampA - timeStampB
      })

      return itens
    },
    converteTimeStamp(dataOriginal) {
      if (!dataOriginal) return 0

      const [data, hora] = dataOriginal.split(/\s/)
      const [dia, mes, ano] = data.split(/\//)
      const formatada = `${ano}-${mes}-${dia} ${hora}`
      const timeStamp = new Date(formatada).getTime()

      return timeStamp
    },
    analisaArea(item) {
      if (item.origem === 'ML') {
        return 'red lighten-5'
      }
      if (item.origem === 'MS') {
        return 'blue lighten-5'
      }
    },
    async buscaProdutosAtrasandoPonto() {
      this.isLoading = true
      try {
        const response = await api.get('api_administracao/entregas/busca_produtos_entrega_atrasada')

        this.listaProdutos = response.data
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Erro ao buscar produtos atrasando ponto',
        )
      } finally {
        this.isLoading = false
      }
    },
    async forcarEntrega(item) {
      if (this.isLoadingEntregando) return
      this.isLoadingEntregando = true

      try {
        await api.post(`api_administracao/entregas/forcar_entrega/${item.uuid_produto}`)

        this.enqueueSnackbar('Entrega realizada com sucesso!', 'success')
        await this.buscaProdutosAtrasandoPonto()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao forçar entrega')
      } finally {
        this.isLoadingEntregando = false
      }
    },
    selecionaFiltro(item) {
      this.filtro = {
        todos: item === 'TODOS',
        mobileStock: item === 'MS',
        meuLook: item === 'ML',
        pendente: item === 'PE',
      }
    },
    pegaListaItens() {
      switch (true) {
        case this.filtro.todos:
          return this.listaProdutos

        case this.filtro.mobileStock:
          return this.listaProdutos.filter((produto) => produto.origem === 'MS')

        case this.filtro.meuLook:
          return this.listaProdutos.filter((produto) => produto.origem === 'ML')

        case this.filtro.pendente:
          return this.listaProdutos.filter((produto) => produto.origem === 'PE')
      }
    },
    gerirModalMaisDetalhes(item = null) {
      this.abreModalMaisDetalhes = !!item
      if (item) {
        const detalhesEntrega = {
          ID_Entrega: item.id_entrega,
          Quem_Enviou: !!item.id_usuario ? `(${item.id_usuario}) ${item.nome_usuario}` : null,
          Data_Enviada: item.data_envio,
          Data_Ultima_Atualização: item.data_atualizacao_entrega,
        }

        const cliente = item.cliente
        const detalhesCliente = {
          Nome: `(${cliente.id_colaborador}) ${cliente.nome}`,
          Telefone: formataTelefone(cliente.telefone || ''),
          WhatsApp: cliente.whatsapp,
          Cidade: `${cliente.cidade} - ${cliente.uf}`,
          Cep: formataCep(cliente.cep || ''),
          Endereco: cliente.endereco || null,
          Bairro: cliente.bairro || null,
          Complemento: cliente.complemento || null,
        }

        const transportador = item.transportador
        const detalhesTransportador = {
          Nome: `(${transportador.id_colaborador}) ${transportador.nome}`,
          Telefone: formataTelefone(transportador?.telefone || ''),
          WhatsApp: transportador.whatsapp,
          Titulo: transportador.titulo,
          Devoluções_Pendentes: transportador.devolucoes_pendentes,
        }

        const pontoColeta = item.ponto_coleta
        const detalhesPontoColeta = {
          Nome: `(${pontoColeta.id_colaborador}) ${pontoColeta.nome}`,
          Telefone: formataTelefone(pontoColeta?.telefone || ''),
          WhatsApp: pontoColeta.whatsapp,
        }
        this.maisDetalhes = {
          entrega: detalhesEntrega,
          cliente: detalhesCliente,
          transportador: detalhesTransportador,
          ponto_coleta: detalhesPontoColeta,
        }

        return
      }
      this.maisDetalhes = null
    },
    abrirModalQrCode(codigoQrTelefone) {
      this.abreModalQrCode = true
      this.expandeImagem = true
      this.codigoQR = codigoQrTelefone
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },

    filtroTabela(valor, pesquisa) {
      if (!valor || !pesquisa) return false

      const valorTratado = typeof valor !== 'string' ? JSON.stringify(valor) : valor
      const regexp = new RegExp(pesquisa, 'i')

      return valorTratado?.match(regexp)
    },
  },
  mounted() {
    this.buscaProdutosAtrasandoPonto()
  },
})
