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
      debounce_timer: null,
      modal_detalhes_destino_entrega: false,
      tela_atual: 'ENTREGAS',
      cor_botao_menu: (tela) => {
        return tela == this.tela_atual ? 'primary' : 'secondary'
      },

      snackbar: {
        mostra: false,
        texto: '',
      },

      ENTREGAS_tipos_embalagens: [
        { item: 'Caixa', value: 'CA' },
        { item: 'Sacola', value: 'SA' },
      ],
      ENTREGAS_ponto_entrega_selecionada: 0,
      idColaborador: document.querySelector('input[name="userIDCliente"]').value,

      ENTREGAS_modal_titulo: '',
      ENTREGAS_loading: false,
      ENTREGAS_loadingMaisDetalhes: false,
      ENTREGAS_modal_produtos_pendentes: false,
      ENTREGAS_modal_relatorio: false,
      ENTREGAS_modal_transacoes: {
        aberto: false,
        transacoes: [],
      },
      ENTREGAS_totalComEntregas: 0,
      ENTREGAS_pesquisa: '',
      ENTREGAS_filtro: '',
      ENTREGAS_relatorio_data: new Date().toLocaleString('pt-br'),
      ENTREGAS_situacao: 'AB',
      ENTREGAS_disabled_botao_acompanhar: false,
      ENTREGAS_dialog_destinos_grupos: false,
      ENTREGAS_dialog_destinos_agrupados: false,
      ENTREGAS_dialog_relatorio_entregadores: false,
      ENTREGAS_loading_acompanhar_grupo: false,
      ENTREGAS_disabled_acompanhar_grupo: false,
      ENTREGAS_carregando_relatorio_entregadores: false,
      ENTREGAS_id_grupo_origem: null,
      ENTREGAS_destino_origem: null,
      ENTREGAS_relatorio_entregadores: [],
      ENTREGAS_modalDetalhes: [],
      ENTREGAS_lista_entregas: [],
      ENTREGAS_lista_entregas_backup: [],
      ENTREGAS_lista_entregas_filtrar_entregador: false,
      ENTREGAS_lista_produtos_pendentes: [],
      ENTREGAS_relatorio_infos: [],
      ENTREGAS_entregas_frete_custeado: [],
      ENTREGAS_entregas_frete_nao_custeado: [],
      ENTREGAS_lista_relatorios: [],
      ENTREGAS_relatorio_entregadores_headers: [
        { text: 'Cliente', value: 'razao_social', align: 'center' },
        { text: 'Telefone', value: 'telefone', align: 'center' },
        { text: 'Cidade', value: 'cidade', align: 'center' },
        { text: 'UF', value: 'uf', align: 'center' },
        { text: 'Endereço', value: 'logradouro', align: 'center' },
        { text: 'Número', value: 'numero', align: 'center' },
        { text: 'Bairro', value: 'bairro', align: 'center' },
        { text: 'Complemento', value: 'complemento', align: 'center' },
        { text: 'Produtos para entregas', value: 'qtd_produtos', align: 'center' },
        { text: 'Tem troca?', value: 'tem_troca', align: 'center' },
      ],
      ENTREGAS_relatorio_headers: [
        { text: 'Entrega', value: 'id' },
        { text: 'Raio', value: 'apelido_raio' },
        { text: 'Destino', value: 'destino' },
        { text: 'Cidade', value: 'cidade' },
        { text: 'Volumes', value: 'volumes' },
        { text: 'Situação', value: 'situacao' },
        { text: 'Tipo Entrega', value: 'tipo_entrega' },
      ],
      ENTREGAS_lista_entregas_frete_custeado: [
        { text: 'Entrega', value: 'id' },
        { text: 'Destino', value: 'destino' },
        { text: 'Cidade', value: 'cidade' },
        { text: 'Volumes', value: 'volumes' },
        { text: 'Situação', value: 'situacao' },
        { text: 'Categoria', value: 'categoria' },
        { text: 'Tipo Entrega', value: 'tipo_entrega' },
        { text: 'Custo Frete', value: 'valor_custo_frete' },
      ],
      ENTREGAS_situacoes: [
        { text: 'Em Abertos', value: 'AB' },
        { text: 'Na expedição', value: 'EX' },
        { text: 'Em transporte', value: 'PT' },
        { text: 'Entregue', value: 'EN' },
        { text: 'Todas situações', value: 'TD' },
      ],
      ENTREGAS_lista_entregas_headers: [
        { text: 'Entrega', value: 'id_entrega' },
        {
          text: 'Relatório',
          value: 'relatorio',
          sortable: false,
        },
        { text: 'Destino', value: 'destino' },
        {
          text: 'Apelido raio',
          value: 'apelido_raio',
          align: 'center',
          width: '12rem',
        },
        {
          text: 'Criação Entrega',
          value: 'data_criacao',
          align: 'center',
          width: '12rem',
        },
        { text: 'Produtos', value: 'qtd_produtos' },
        {
          text: 'Transportador',
          value: 'transportador',
          align: 'center',
        },
        { text: 'Cidade', value: 'cidade', align: 'center' },
        { text: 'Acompanhar', value: 'acompanhar', sortable: false, align: 'center', width: '10rem' },
        { text: 'Situação', value: 'situacao', align: 'center' },
        {
          text: 'Mais Produtos',
          value: 'tem_mais_produtos',
          align: 'center',
          sortable: false,
        },
        {
          text: 'Mais Detalhes',
          value: 'mais_detalhes',
          align: 'center',
          sortable: false,
        },
      ],
      ENTREGAS_lista_produtos_pendentes_header: [
        {
          text: 'ID produto',
          value: 'id_produto',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Produto',
          value: 'produto_foto',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Tamanho',
          value: 'nome_tamanho',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Estoque',
          value: 'responsavel_estoque',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Localização',
          value: 'localizacao',
          sortable: false,
          align: 'center',
        },
        {
          text: 'ID transação',
          value: 'id_transacao',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Fornecedor',
          value: 'nome_fornecedor',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Usuário',
          value: 'nome_usuario',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Data',
          value: 'data_atualizacao',
          sortable: false,
          align: 'center',
        },
        {
          text: 'Situação atual',
          value: 'situacao',
          sortable: false,
          align: 'center',
        },
      ],
      ENTREGAS_grupos: [],
      ENTREGAS_grupos_destinos: [],
      ENTREGAS_grupos_destinos_acompanhar: [],

      TRANSACOES_pesquisa: '',
      TRANSACOES_lista: [],
      TRANSACOES_lista_headers: [
        { text: 'Transação', value: 'id_transacao' },
        { text: 'Data', value: 'data_criacao' },
        { text: 'Colaborador', value: 'razao_social' },
        { text: 'Pagamento', value: 'metodo_pagamento' },
        { text: 'Crédito', value: 'valor_credito' },
        { text: 'Acréscimo', value: 'valor_acrescimo' },
        { text: 'Liquido', value: 'valor_liquido' },
        { text: 'Items', value: 'valor_itens' },
        { text: 'Juros', value: 'juros_pago_split' },
        { text: 'Comissão Fornecedor', value: 'valor_comissao_fornecedor' },
      ],

      PENDENTES_pesquisa: '',
      PENDENTES_lista_transacoes: [],
      PENDENTES_lista_transacoes_headers: [
        { text: 'Transação', value: 'id' },
        { text: 'Cliente', value: 'pagador' },
        { text: 'Tipo', value: 'tipo_item' },
        { text: 'URL', value: 'url_boleto' },
        { text: 'Total', value: 'valor_total' },
        { text: 'Crédito', value: 'valor_credito' },
        { text: 'Acrescimo', value: 'valor_acrescimo' },
        { text: 'Comissão Fornecedor', value: 'valor_comissao_fornecedor' },
        { text: 'Liquido', value: 'valor_liquido' },
        { text: 'Items', value: 'valor_itens' },
        { text: 'Taxa', value: 'valor_taxas' },
        { text: 'Pagamento', value: 'metodo_pagamento' },
        { text: 'Data', value: 'data_criacao' },
      ],
      ENTREGAS_valor_total_frete: 0,
      ENTREGAS_valor_total_volumes: 0,
      ENTREGAS_qtd_total_destinos: 0,
      ENTREGAS_total_volumes_custeados: 0,
      ENTREGAS_total_volumes_nao_custeados: 0,
    }
  },

  methods: {
    enqueueSnackbar(texto, cor = 'error') {
      this.snackbar = {
        mostra: true,
        cor,
        texto,
      }
    },
    alteraTelaSelecionada(tela) {
      this.tela_atual = tela
    },
    formatarNumero(x) {
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')
    },
    mensagem_mais_produtos_entrega(item) {
      let mensagem = 'Existem produtos que podem ser adicionados a esta entrega.'
      switch (true) {
        case !item.id_entrega && item.eh_retirada_cliente:
          mensagem = 'Existem produtos que podem ser adicionados a este pedido.'
          break
        case !item.id_entrega && item.id_tipo_frete > 4:
          mensagem = 'Produtos que já estão nesse pedido.'
          break
        case item.categoria === 'MS':
          mensagem = 'Esta entrega Mobile Stock tem mais produtos que podem ser agrupados.'
          break
        case ['ML', 'MS'].includes(item.categoria) && item.tipo_entrega === 'PP':
          mensagem = 'Essa entrega para esse ponto de retirada tem mais produtos que podem ser agrupados.'
          break
        case item.categoria === 'PE' && item.tipo_entrega === 'PP':
          mensagem = 'Essa entrega para esse ponto de retirada DESATIVADO tem mais produtos que podem ser agrupados.'
          break
        case item.categoria === 'ML' && item.tipo_entrega === 'PM':
          mensagem = `Existem produtos para ${item.destino} que podem ser agrupados com esta entrega.`
          break
        case item.categoria === 'PE' && item.tipo_entrega === 'PM':
          mensagem =
            'Esta entrega está programada para ser entregue por um entregador inativo e contém produtos adicionais que podem ser agrupados.'
          break
      }

      return mensagem
    },
    pesquisaDebouncer(valor) {
      if (this.debounce_timer) {
        clearTimeout(this.debounce_timer)
      }
      this.debounce_timer = setTimeout(() => {
        switch (this.tela_atual) {
          case 'ENTREGAS':
            this.buscarEntregas()
            break
          case 'PENDENTES':
            this.buscarTransacoesPendentes()
            break
          case 'TRANSACOES':
            this.buscarTransacoes()
            break
        }
      }, 500)
    },
    abrirModalDetalhesDestinoEntrega() {
      this.modal_detalhes_destino_entrega = true
    },
    fecharModalDetalhesDestinoEntrega() {
      this.ENTREGAS_modalDetalhes = []
      this.modal_detalhes_destino_entrega = false
    },
    async buscarEntregas() {
      try {
        this.loading = true

        const parametros = new URLSearchParams({
          pesquisa: this.ENTREGAS_pesquisa.trim(),
          situacao: this.ENTREGAS_situacao,
        })

        const resultado = await api.get(`api_administracao/tipo_frete/busca_lista_pedidos?${parametros}`)

        const lista = resultado.data.map((item) => {
          if (!item.eh_retirada_cliente && item.tipo_entrega === 'PP') {
            item.tipo_ponto = 'Ponto de Retirada'
          } else if (item.tipo_entrega === 'PM') {
            item.tipo_ponto = 'Entregador'
          }

          return item
        })

        this.ENTREGAS_totalComEntregas = resultado.data.reduce((acumulador, item) => {
          if (item.id_entrega) {
            acumulador++
          }
          return acumulador
        }, 0)

        this.ENTREGAS_lista_entregas = lista || []
      } catch (error) {
        this.ENTREGAS_lista_entregas = []
        this.snackbar.texto = error?.response?.data?.message || error.message || 'Ocorreu um erro ao buscar entregas!'
        this.snackbar.mostra = true
      } finally {
        this.loading = false
      }
    },
    async ENTREGAS_buscarMaisDetalhes(item) {
      try {
        this.loading = true
        this.ENTREGAS_loadingMaisDetalhes = true
        const parametros = new URLSearchParams({
          id_destinatario: item.destinatario.id_colaborador,
          id_tipo_frete: item.id_tipo_frete,
          id_entrega: item.id_entrega || 0,
        })

        const resultado = await api.get(`api_administracao/tipo_frete/busca_mais_detalhes_pedido?${parametros}`)
        const consulta = resultado.data

        consulta.valor_custo_produto = formataMoeda(consulta.valor_pedido / consulta.qtd_total_produtos)
        consulta.valor_pedido = formataMoeda(consulta.valor_pedido)
        consulta.custo_frete_ponto_coleta = formataMoeda(consulta.custo_frete_ponto_coleta)
        consulta.custo_frete_transportadora = formataMoeda(consulta.custo_frete_transportadora)

        this.ENTREGAS_modalDetalhes = consulta

        this.abrirModalDetalhesDestinoEntrega()
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao buscar mais detalhes do destino!',
        )
      } finally {
        this.loading = false
        this.ENTREGAS_loadingMaisDetalhes = false
      }
    },
    ENTREGAS_pesquisaCustomizada(value, pesquisa, item) {
      if (pesquisa.length < 3) return

      const pesquisaFiltrada = removeAcentos(pesquisa)

      const resultado = Object.values(item).some(
        (valor) => valor && removeAcentos(valor.toString().toLowerCase()).includes(pesquisaFiltrada.toLowerCase()),
      )

      return resultado
    },
    async buscarTransacoes() {
      this.loading = true
      MobileStockApi(`api_administracao/transacoes?pesquisa=${this.TRANSACOES_pesquisa}`)
        .then((res) => res.json())
        .then((json) => {
          this.TRANSACOES_lista = json.data || []
        })
        .catch((error) => {
          this.TRANSACOES_lista = []
          this.snackbar.texto = 'Ocorreu um erro ao buscar transações!'
          this.snackbar.mostra = true
        })
        .finally(() => {
          this.loading = false
        })
    },
    async buscarTransacoesPendentes() {
      this.loading = true
      MobileStockApi(`api_administracao/transacoes/pendentes?pesquisa=${this.PENDENTES_pesquisa}&pagamento=`)
        .then((res) => res.json())
        .then((json) => {
          this.PENDENTES_lista_transacoes = json.data || []
        })
        .catch((error) => {
          this.PENDENTES_lista_transacoes = []
          this.snackbar.texto = 'Ocorreu um erro ao buscar transações pendentes!'
          this.snackbar.mostra = true
        })
        .finally(() => {
          this.loading = false
        })
    },
    async buscaProdutosSemEntrega(item) {
      try {
        this.ENTREGAS_loading = true
        const titulo = this.ENTREGAS_maisProdutos(item).titulo
        if (item.qtd_produtos_prontos < 1) {
          throw new Error(titulo)
        }

        this.ENTREGAS_modal_titulo = titulo
        let tipoPedido = 'PONTO_ENTREGADOR'
        if (item.id) {
          tipoPedido = 'ENTREGA'
        } else if (item.eh_retirada_cliente) {
          tipoPedido = 'RETIRADA_TRANSPORTADORA'
        }

        const parametros = new URLSearchParams({
          tipo_pedido: tipoPedido,
          identificador: item.transacoes?.join(',') || item.uuid_entrega,
        })
        const response = await api.get(`api_administracao/produtos/pedidos?${parametros}`)

        let produtos = response.data

        if (tipoPedido === 'ENTREGA') {
          const entregaFiltrada = response.data.detalhes_entregas.filter(
            (entrega) => entrega.id_entrega === item.id_entrega,
          )
          produtos = entregaFiltrada.length > 0 ? entregaFiltrada[0].produtos : []
        }

        this.ENTREGAS_modal_produtos_pendentes = true
        this.ENTREGAS_lista_produtos_pendentes = produtos
      } catch (error) {
        this.ENTREGAS_lista_produtos_pendentes = []
        this.enqueueSnackbar(
          error?.response?.message ||
            error?.message ||
            'Ocorreu um erro ao buscar os produtos para juntar a essa entrega',
        )
      } finally {
        this.ENTREGAS_loading = false
      }
    },
    gerirModalTransacoes(transacoes = []) {
      this.ENTREGAS_modal_transacoes = {
        aberto: !!transacoes.length,
        transacoes: transacoes,
      }
    },
    fechaModalProdutosPendentes() {
      this.ENTREGAS_modal_produtos_pendentes = false
      this.ENTREGAS_modal_titulo = ''
      this.ENTREGAS_lista_produtos_pendente = []
    },
    async buscaRelatorioEntregadores() {
      try {
        this.ENTREGAS_carregando_relatorio_entregadores = true
        const entregas = this.ENTREGAS_relatorio_infos?.filter((item) => item.id_entrega && item.tipo_entrega === 'PM')
        if (!entregas.length) {
          throw new Error('Não há entregas para entregadores pra gerar o relatório!')
        }

        const requisicoes = entregas.map((entrega) =>
          api.get(`api_administracao/entregas/busca_detalhes_entrega/${entrega.id_entrega}`).then((resp) => resp.data),
        )
        const detalhesEntregas = await Promise.all(requisicoes)
        this.ENTREGAS_relatorio_entregadores = entregas.map((entrega) => {
          const detalhesEntrega = detalhesEntregas
            .find((detalhesEntrega) => detalhesEntrega.some((item) => item.id_entrega === entrega.id_entrega))
            .map((item) => ({ ...item, telefone: formataTelefone(item.telefone) }))

          return {
            id_entrega: entrega.id_entrega,
            entregador: entrega.destino,
            apelido_raio: entrega.apelido_raio,
            detalhes_entrega: detalhesEntrega,
          }
        })
        this.ENTREGAS_dialog_relatorio_entregadores = true
      } catch (error) {
        this.enqueueSnackbar(error.message || 'Ocorreu um erro ao imprimir o relatório de entregadores!')
      } finally {
        this.ENTREGAS_carregando_relatorio_entregadores = false
      }
    },
    buscaInfosRelatorio(completo = false) {
      let informacoes = []
      if (completo) {
        informacoes = this.ENTREGAS_lista_entregas
      } else {
        if (this.ENTREGAS_lista_relatorios.length < 1) return

        informacoes = this.ENTREGAS_lista_entregas.filter((pedido) =>
          this.ENTREGAS_lista_relatorios.includes(pedido.identificador),
        )
      }

      this.ENTREGAS_relatorio_infos = informacoes
      this.ENTREGAS_modal_relatorio = true
      this.ENTREGAS_relatorio_data = new Date().toLocaleString('pt-br')

      this.ENTREGAS_entregas_frete_custeado = this.ENTREGAS_relatorio_infos.reduce((acumulador, item) => {
        if (parseFloat(item.valor_custo_frete) > 0) {
          acumulador.push(item)
        }
        return acumulador
      }, [])

      this.ENTREGAS_entregas_frete_nao_custeado = this.ENTREGAS_relatorio_infos.reduce((acumulador, item) => {
        if (parseFloat(item.valor_custo_frete) == 0) {
          acumulador.push(item)
        }
        return acumulador
      }, [])

      this.calculaTotalFrete()
      this.calculaTotalVolumes()
      this.calculaTotalVolumesCusteados()
      this.calculaTotalVolumesNaoCusteados()
    },
    fechaModalRelatorioEntregas() {
      this.ENTREGAS_modal_relatorio = false
      this.ENTREGAS_lista_relatorios = []
      this.ENTREGAS_relatorio_infos = []
    },
    imprimirRelatorio(tipo) {
      $(`#relatorio-${tipo}-imprimivel`).printThis({
        pageTitle: tipo === 'geral' ? 'Relatório de Entregas' : 'Relatório de Entregas por Entregador',
        importCSS: true,
        importStyle: true,
      })
    },
    copiarDados(texto) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(texto)
      } else {
        let campoCopiar = document.getElementById(`${this.tela_atual}_inputCopiar`)
        campoCopiar.value = texto

        campoCopiar.style.top = '0'
        campoCopiar.style.left = '0'
        campoCopiar.style.position = 'fixed'

        campoCopiar.focus()
        campoCopiar.select()

        document.execCommand('copy')
      }

      this.snackbar.texto = 'Copiado para a área de transferência!'
      this.snackbar.mostra = true
    },
    atualizaInformacoes(valor) {
      switch (valor) {
        case 'ENTREGAS':
          if (this.ENTREGAS_lista_entregas.length == 0) {
            this.buscarEntregas()
          }
          break
        case 'PENDENTES':
          if (this.PENDENTES_lista_transacoes.length == 0) {
            this.buscarTransacoesPendentes()
          }
          break
        case 'TRANSACOES':
          if (this.TRANSACOES_lista.length == 0) {
            this.buscarTransacoes()
          }
          break
      }
    },
    entrega_check(item) {
      const centralTransportadora =
        item.tipo_entrega === 'ENVIO_TRANSPORTADORA' || (item.categoria === 'MS' && item.tipo_entrega === 'PP')

      switch (true) {
        case item.eh_fraude:
          return 'row-fraude'
        case centralTransportadora && item.tem_mais_produtos:
          return 'row-pendencias'
        case centralTransportadora && !item.tem_mais_produtos:
          return 'row-liberado'
        case parseInt(item.devolucoes_pendentes) >= 20:
          return 'row-alerta'
      }
    },
    cor_ponto_entrega(categoria) {
      switch (categoria) {
        case 'MS':
          return 'var(--cor-primaria-mobile-stock)'
        case 'ML':
          return 'var(--cor-secundaria-meulook)'
        case 'PE':
          return ''
      }
    },
    informacoesTipoEntrega(tipoEntrega) {
      const listaTipos = [
        {
          icone: 'mdi-dolly',
          explicacao: 'Essa entrega será feita por transportadora.',
          valor: 'ENVIO_TRANSPORTADORA',
          texto: 'Transportadora',
        },
        {
          icone: 'mdi-home',
          explicacao: 'Essa entrega será feita em um ponto.',
          valor: 'PP',
          texto: 'Ponto Parado',
        },
        {
          icone: 'mdi-truck',
          explicacao: 'Essa entrega será feita por um entregador.',
          valor: 'PM',
          texto: 'Entregador',
        },
      ]

      const informacoes = listaTipos.find((item) => item.valor === tipoEntrega)
      return informacoes
    },

    selecionaEntrega(item) {
      this.ENTREGAS_ponto_entrega_selecionada = item.id_colaborador
    },

    mudaTipoEmbalagem(item) {
      const idColaborador = this.ENTREGAS_ponto_entrega_selecionada || this.ENTREGAS_modalDetalhes.id_colaborador

      MobileStockApi(`api_administracao/pontos_de_entrega/muda_tipo_embalagem`, {
        method: 'PUT',
        body: JSON.stringify({
          tipo_embalagem: item,
          id_colaborador_destinatario: idColaborador,
        }),
      })
        .then(async (resp) => resp.json())
        .then(() => {
          this.snackbar.mostra = true
          this.snackbar.cor = 'success'
          this.snackbar.texto = 'O tipo de embalagem foi alterado com sucesso.'
        })
        .catch((error) => {
          this.snackbar.texto = error | 'Ocorreu um erro ao atualizar o tipo de embalagem!'
          this.snackbar.mostra = true
        })
        .finally(() => {
          this.buscarEntregas()
        })
    },

    async ENTREGAS_buscarGruposDestinos(item) {
      try {
        this.ENTREGAS_disabled_botao_acompanhar = true

        const resultado = await api.get(`api_administracao/tipo_frete/listar_grupos/${item.id_tipo_frete}`)

        this.ENTREGAS_destino_origem = {
          nome: item.destinatario.nome,
          cidade: item.cidade,
          id_tipo_frete: item.id_tipo_frete,
          destinatario: {
            id_cidade: item.destinatario.id_cidade,
            id_raio: item.destinatario.id_raio,
            id_colaborador: item.destinatario.id_colaborador,
          },
        }

        switch (true) {
          case resultado.data.tipo_retorno === 'GRUPO' && resultado.data.retorno.length > 1:
            this.ENTREGAS_grupos = resultado.data.retorno
            this.ENTREGAS_dialog_destinos_grupos = true
            break
          case resultado.data.tipo_retorno === 'DESTINOS' && resultado.data.retorno.length > 1:
            resultado.data.retorno.map((item) => {
              this.ENTREGAS_grupos_destinos_acompanhar.push({
                id_tipo_frete: item.id_tipo_frete,
                id_colaborador: item.id_colaborador_tipo_frete,
                cidades: item.cidades,
                identificador: item.identificador,
                identificador_raio: item.destinos.map((item) => item.identificador),
              })
            })

            this.ENTREGAS_dialog_destinos_agrupados = true
            this.ENTREGAS_grupos_destinos = resultado.data.retorno
            this.ENTREGAS_id_grupo_origem = resultado.data.id_grupo_origem

            break
          default:
            this.ENTREGAS_acompanharDestino(item)
            break
        }

        this.ENTREGAS_grupos_destinos = resultado.data.retorno
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao buscar os grupos de destinos!',
        )
      } finally {
        this.ENTREGAS_disabled_botao_acompanhar = false
      }
    },

    async ENTREGAS_listarDestinosDoGrupo(grupo) {
      try {
        this.ENTREGAS_loading_acompanhar_grupo = true
        this.ENTREGAS_disabled_acompanhar_grupo = true

        const resultado = await api.get(
          `api_administracao/tipo_frete/listar_destinos_grupo/${grupo.id_tipo_frete_grupos}`,
        )

        this.ENTREGAS_limparAcompanharDestinos()
        const retorno = resultado.data.retorno.map((item) => {
          const destinos = item.destinos.map((destino) => {
            const objetoDestino = {
              id_colaborador: item.id_colaborador_tipo_frete,
              id_tipo_frete: item.id_tipo_frete,
              id_cidade: destino.id_cidade,
              id_raio: destino.id_raio,
              apelido: destino.apelido,
              cidade: destino.cidade,
              identificador: destino.identificador,
            }

            this.ENTREGAS_grupos_destinos_acompanhar.push(objetoDestino)

            return objetoDestino
          })

          return {
            ...item,
            destinos,
          }
        })

        this.ENTREGAS_dialog_destinos_agrupados = true
        this.ENTREGAS_grupos_destinos = retorno
        this.ENTREGAS_id_grupo_origem = grupo.id_tipo_frete_grupos
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao listar os destinos do grupo!',
        )
      } finally {
        this.ENTREGAS_loading_acompanhar_grupo = false
        this.ENTREGAS_disabled_acompanhar_grupo = false
      }
    },

    ENTREGAS_adicionarDestinoParaAcompanhar(destino) {
      const index = this.ENTREGAS_grupos_destinos_acompanhar.findIndex(
        (item) => item.identificador === destino.identificador,
      )

      if (index !== -1) {
        this.ENTREGAS_grupos_destinos_acompanhar.splice(index, 1)
      } else {
        this.ENTREGAS_grupos_destinos_acompanhar.push(destino)
      }
    },

    ENTREGAS_limparAcompanharDestinos() {
      this.ENTREGAS_grupos = []
      this.ENTREGAS_grupos_destinos_acompanhar = []
      this.ENTREGAS_dialog_destinos_grupos = false
      this.ENTREGAS_dialog_destinos_agrupados = false
      this.ENTREGAS_loading_acompanhar_grupo = false
      this.ENTREGAS_disabled_acompanhar_grupo = false
    },

    async ENTREGAS_acompanharDestinoEmGrupo(dados) {
      try {
        this.loading = true
        this.ENTREGAS_loading_acompanhar_grupo = true
        this.ENTREGAS_disabled_acompanhar_grupo = true

        await api.post('api_administracao/acompanhamento/acompanhar_em_grupo', dados)

        await this.buscarEntregas()

        this.enqueueSnackbar('Grupo acompanhado com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao acompanhar o destino em grupo!',
        )
      } finally {
        this.loading = false
        this.ENTREGAS_limparAcompanharDestinos()
        this.ENTREGAS_id_grupo_origem = null
      }
    },

    navegaParaWhatsApp(telefone) {
      const whatsapp = new MensagensWhatsApp({
        telefone: telefone,
      }).resultado

      window.open(whatsapp)
    },

    ENTREGAS_direcionaBotaoAcompanhamento(item) {
      if (!item.acompanhamento) {
        this.ENTREGAS_buscarGruposDestinos(item)
      } else {
        this.ENTREGAS_desacompanharDestino(item)
      }
    },

    async ENTREGAS_acompanharDestino(item) {
      try {
        this.loading = true
        this.ENTREGAS_loading_acompanhar_grupo = true
        this.ENTREGAS_disabled_botao_acompanhar = true

        await api.post('api_cliente/acompanhamento/acompanhar', {
          id_destinatario: item.destinatario.id_colaborador,
          id_cidade: item.destinatario.id_cidade,
          id_raio: item.destinatario.id_raio,
          id_tipo_frete: item.id_tipo_frete,
        })

        await this.buscarEntregas()

        const index = this.ENTREGAS_lista_entregas.findIndex(
          (destino) =>
            destino.destinatario.id_colaborador === item.destinatario.id_colaborador &&
            destino.destinatario.id_cidade === item.destinatario.id_cidade &&
            destino.id_tipo_frete === item.id_tipo_frete,
        )

        this.ENTREGAS_lista_entregas[index].acompanhando = true

        this.snackbar.mostra = true
        this.snackbar.cor = 'success'
        this.snackbar.texto = 'O pedido está sendo acompanhado. Verifique nos acompanhamentos pelo aplicativo.'
      } catch (error) {
        this.loading = false
        this.snackbar.mostra = true
        this.snackbar.cor = 'error'
        this.snackbar.texto =
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao alterar o acompanhamento do destino!'
      } finally {
        this.ENTREGAS_disabled_botao_acompanhar = false
        this.ENTREGAS_dialog_destinos_grupos = false
        this.ENTREGAS_loading_acompanhar_grupo = false
        this.ENTREGAS_dialog_destinos_agrupados = false
      }
    },

    async ENTREGAS_desacompanharDestino(item) {
      try {
        this.loading = true
        this.ENTREGAS_disabled_botao_acompanhar = true

        await api.delete(`api_cliente/acompanhamento/desacompanhar/${item.acompanhamento.id}`)

        const index = this.ENTREGAS_lista_entregas.findIndex(
          (destino) => destino.acompanhamento && destino.acompanhamento.id === item.acompanhamento.id,
        )

        this.ENTREGAS_lista_entregas[index].acompanhando = false

        this.enqueueSnackbar('O acompanhamento deste destino foi finalizado', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao alterar o acompanhamento do destino!',
        )
        this.loading = false
      } finally {
        await this.buscarEntregas()
        this.ENTREGAS_disabled_botao_acompanhar = false
      }
    },

    ENTREGAS_bloqueiaBotaoAcompanhar(item) {
      return this.loading || (item.id_entrega && item.situacao !== 'Aberta')
    },
    ENTREGAS_maisProdutos(item) {
      const informacoes = {
        desativado: !item.tem_mais_produtos && (!!item.id_entrega || [1, 2, 3, 4].includes(item.id_tipo_frete)),
        titulo: 'Produtos que já estão nesse pedido',
      }
      switch (true) {
        case !!item.id:
          informacoes.titulo = `Produtos que podem ser inseridos na entrega ${item.id}`
          break
        case [1, 2, 3, 4].includes(item.id_tipo_frete):
          informacoes.titulo = 'Produtos que podem ser juntados com esse pedido'
          break
        case item.qtd_produtos_prontos < 1:
          informacoes.titulo = 'Ainda não há produtos nesse pedido'
          break
      }

      return informacoes
    },
    ENTREGAS_filtrarPorEntregador(ativo) {
      if (ativo) {
        this.ENTREGAS_lista_entregas_backup = this.ENTREGAS_lista_entregas
        this.ENTREGAS_lista_entregas = this.ENTREGAS_lista_entregas.filter((item) => {
          return item.tipo_entrega == 'PM'
        })
      } else {
        this.ENTREGAS_lista_entregas = this.ENTREGAS_lista_entregas_backup
      }
    },
    async ENTREGAS_salvarObservacaoColaborador() {
      try {
        this.loading = true
        await api.post('api_administracao/cadastro/salvar_observacao', {
          id_colaborador: this.ENTREGAS_modalDetalhes.id_colaborador,
          observacao: document.querySelector("textarea[name='colaboradores_observacoes']").value,
        })
        this.snackbar.mostra = true
        this.snackbar.cor = 'success'
        this.snackbar.texto = 'A observação foi atualizada com sucesso!'
      } catch (error) {
        this.snackbar.mostra = true
        this.snackbar.cor = 'error'
        this.snackbar.texto =
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao alterar a observação!'
      } finally {
        this.loading = false
      }
    },

    calculaTotalFrete() {
      this.ENTREGAS_valor_total_frete = formataMoeda(
        this.ENTREGAS_entregas_frete_custeado.reduce((acumulador, item) => {
          acumulador += parseFloat(item.valor_custo_frete)
          return acumulador
        }, 0),
      )
    },

    calculaTotalVolumes() {
      this.ENTREGAS_valor_total_volumes = this.ENTREGAS_relatorio_infos.reduce((acumulador, item) => {
        acumulador += item.volumes
        return acumulador
      }, 0)
    },
    calculaTotalVolumesCusteados() {
      this.ENTREGAS_total_volumes_custeados = this.ENTREGAS_entregas_frete_custeado.reduce(
        (acumulador_custeado, item) => {
          acumulador_custeado += item.volumes
          return acumulador_custeado
        },
        0,
      )
    },
    calculaTotalVolumesNaoCusteados() {
      this.ENTREGAS_total_volumes_nao_custeados = this.ENTREGAS_entregas_frete_nao_custeado.reduce(
        (acumulador_nao_custeado, item) => {
          acumulador_nao_custeado += item.volumes
          return acumulador_nao_custeado
        },
        0,
      )
    },
  },

  watch: {
    tela_atual(valor) {
      this.atualizaInformacoes(valor)
    },
    ENTREGAS_situacao(valor) {
      this.buscarEntregas(valor)
    },
    ENTREGAS_pesquisa(valor) {
      this.pesquisaDebouncer(valor)
    },
    PENDENTES_pesquisa(valor) {
      this.pesquisaDebouncer(valor)
    },
    TRANSACOES_pesquisa(valor) {
      this.pesquisaDebouncer(valor)
    },
  },

  computed: {
    desabilitarBotaoRelatorioEntregadores() {
      return (
        !this.ENTREGAS_relatorio_infos?.some((item) => item.id_entrega && item.tipo_entrega === 'PM') ||
        this.ENTREGAS_carregando_relatorio_entregadores
      )
    },
  },

  mounted() {
    this.atualizaInformacoes(this.tela_atual)
  },
})
