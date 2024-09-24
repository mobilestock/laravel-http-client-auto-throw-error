import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

$('#imprimir-estantes').on('click', imprimirEtiquetas)

function imprimirEtiquetas() {
  var qte = $('#qte_estantes').val()
  if (qte == '' || qte == 0) {
    alert('Informe alguma quantidade de etiqueta.')
  } else {
    var json = '['
    for (var i = 1; i <= qte; i++) {
      if (i < 10) {
        json = json + '{"estante":"00' + i + '"}'
      } else if (i < 100) {
        json = json + '{"estante":"0' + i + '"}'
      } else {
        json = json + '{"estante":"' + i + '"}'
      }
      if (i < qte) {
        json = json + ','
      }
    }
    json = json + ']'
    var filename = 'etiqueta_estante'
    var blob = new Blob([json], { type: 'json' })
    saveAs(blob, filename + '.json')
  }
}

var taxasConfigVUE = new Vue({
  el: '#taxasConfigVUE',
  components: { draggable: window.vuedraggable },
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data: function () {
    return {
      rules: {
        required: (value) => !!value || 'Campo obrigatório.',
        counter: (value) => (!!value && value.length <= 100) || 'Max 20 characters',
        valorMin(value, min, campo) {
          return (value) => (!!value && parseInt(value) >= min) || `O valor mínimo para ${campo} é ${min}`
        },
        valorMax(value, max, campo) {
          return (value) => (!!value && parseInt(value) <= max) || `O valor máximo para ${campo} é ${max}.`
        },
      },
      headers: [
        {
          text: 'Número de Parcelas',
          align: 'center',
          value: 'numero_de_parcelas',
          sortable: false,
        },
        {
          text: 'Taxa de Juros',
          align: 'center',
          value: 'numero_de_parcelas',
          sortable: false,
        },
        {
          text: 'Mastercard',
          align: 'center',
          value: 'mastercard',
          sortable: false,
        },
        {
          text: 'Visa',
          align: 'center',
          value: 'visa',
          sortable: false,
        },
        {
          text: 'Elo',
          align: 'center',
          value: 'elo',
          sortable: false,
        },
        {
          text: 'American Express',
          align: 'center',
          value: 'american_express',
          sortable: false,
        },
        {
          text: 'Hiper',
          align: 'center',
          value: 'hiper',
          sortable: false,
        },
        {
          text: 'Boleto',
          align: 'center',
          value: 'boleto',
          sortable: false,
        },
        {
          text: 'Excluir',
          align: 'center',
          value: 'botao',
          sortable: false,
        },
      ],
      separacaoFulfillment: {
        carregando: false,
        headers: [
          { text: 'Horário', value: 'horario' },
          { text: 'Remover', value: 'remover' },
        ],
        novoHorario: null,
        horarios: [],
        horasCarenciaRetirada: null,
        BKP: JSON.stringify({ horarios: [], horasCarenciaRetirada: null }),
      },
      listaTaxas: [],
      listaProdutos: [],
      itemBluePrit: {
        american_express: '0.00',
        boleto: '0.00',
        elo: '0.00',
        hiper: '0.00',
        id: '0.00',
        juros: '0.00',
        mastercard: '0.00',
        numero_de_parcelas: '0',
        visa: '0.00',
      },
      overlay: false,
      dialog: false,
      snackbar: {
        mensagem: 'Regras salvas com sucesso.',
        color: 'green',
        open: false,
      },
      filtros: {
        fornecedor: '',
        descricao: '',
      },
      buscaFornecedor: ' ',
      selectFornecedor: [],
      alertaConfirmacao: false,
      configProduto: {
        tipo: 1,
        id_produto: '',
        comissao: 0,
        fornecedor: '',
      },
      listaMeiosPagamentoBkp: [],
      listaMeiosPagamento: [],
      headersMetodosPagamento: [
        { text: 'Pagamento', value: 'local_pagamento' },
        { text: 'ativo', value: 'situacao' },
        { text: 'Mover', value: 'mover' },
      ],
      percentuaisFreteiros: [],
      percentuaisFreteirosInicial: [],
      porcentagemComissoes: [],
      valorMinimoFraude: 0,
      headersPercentuaisFreteiros: [
        {
          text: 'Valor Inicial',
          align: 'center',
          value: 'de',
          sortable: false,
        },
        {
          text: 'Valor Final',
          align: 'center',
          value: 'ate',
          sortable: false,
        },
        {
          text: 'Porcentagem',
          align: 'center',
          value: 'porcentagem',
          sortable: false,
        },
        {
          text: 'Excluir',
          align: 'center',
          value: 'botao',
          sortable: false,
        },
      ],
      headerDiasNaoTrabalhados: [
        {
          text: 'Dias não trabalhados',
          align: 'start',
          filterable: false,
          value: 'data',
          sortable: false,
        },
        { text: '-', value: 'acao', sortable: false, filterable: false },
      ],
      listaDeDiaNaoTrabalhados: [],
      configuracoesEstoqueParado: {
        qtd_maxima_dias: 0,
        percentual_desconto: 0,
        dias_carencia: 0,
      },
      configuracoesEstoqueParadoBkp: null,
      pesquisaDiasNaoTrabalhados: '',
      carregandoMudarConfiguracoesEstoqueParado: false,
      loadingRemoveDiaNaoTrabalhado: false,
      loadingInsereDiaNaoTrabalhado: false,
      loadingPorcentagemComissoes: false,
      loadingValorMinimoFraude: false,
      pontuacao: {
        carregando: false,
        cabecalho: [
          { text: 'Chave', value: 'chave' },
          { text: 'Valor', value: 'valor' },
        ],
        observacoes: {
          pontuacao_atraso_separacao: 'Pontuação caso o fornecedor possua algum ATRASO NA SEPARAÇÃO',
          pontuacao_avaliacao_4_estrelas: 'Pontuação de cada avaliação com 4 estrelas no Meulook',
          pontuacao_avaliacao_5_estrelas: 'Pontuação de cada avaliação com 5 estrelas no Meulook',
          pontuacao_devolucao_defeito: 'Pontuação por cada devolução DEFEITO do produto',
          pontuacao_devolucao_normal: 'Pontuação por cada devolução NORMAL do produto',
          dias_mensurar_avaliacoes: 'Olha as avaliações criadas nos últimos X dias',
          dias_mensurar_cancelamento: 'Olha os cancelamentos que ocorreram nos últimos X dias',
          dias_mensurar_trocas_defeito: 'Olha as trocas DEFEITO que ocorreram nos últimos X dias',
          dias_mensurar_trocas_normais: 'Olha as trocas NORMAIS que ocorreram nos últimos X dias',
          dias_mensurar_vendas: 'Olha as vendas PAGAS nos últimos X dias',
          pontuacao_cancelamento: 'Pontuação por cada CANCELAMENTO AUTOMÁTICO do produto',
          pontuacao_venda: 'Pontuação por cada venda do produto',
          pontuacao_fulfillment: 'Pontuação caso o produto tenha QUALQUER estoque na modalidade Fulfillment',
          pontuacao_reputacao_excelente: 'Pontuação baseado na reputação do fornecedor do produto',
          pontuacao_reputacao_melhor_fabricante: 'Pontuação baseado na reputação do fornecedor do produto',
          pontuacao_reputacao_regular: 'Pontuação baseado na reputação do fornecedor do produto',
          pontuacao_reputacao_ruim: 'Pontuação baseado na reputação do fornecedor do produto',
        },
        dados: [],
        dadosHash: '',
      },
      reputacaoFornecedor: {
        carregando: false,
        cabecalho: [
          { text: 'Chave', value: 'chave' },
          { text: 'Valor', value: 'valor' },
        ],
        observacoes: {
          dias_mensurar_cancelamento: 'Quantidade de dias para o cálculo de cancelamento automático',
          dias_mensurar_media_envios: 'Quantidade de dias para o cálculo de média de envio',
          dias_mensurar_vendas: 'Quantidade de dias para o cálculo de quantidade e valor de vendas',
          media_dias_envio_excelente: 'Média dias envio máxima para reputação excelente',
          media_dias_envio_melhor_fabricante: 'Média dias envio máxima para reputação melhor fabricante',
          media_dias_envio_regular: 'Média dias envio máxima para reputação regular',
          taxa_cancelamento_excelente: 'Taxa cancelamento máxima para reputação excelente',
          taxa_cancelamento_melhor_fabricante: 'Taxa cancelamento máxima para reputação melhor fabricante',
          taxa_cancelamento_regular: 'Taxa cancelamento máxima para reputação regular',
          valor_vendido_excelente: 'Valor mínimo vendido para reputação excelente',
          valor_vendido_melhor_fabricante: 'Valor mínimo vendido para reputação melhor fabricante',
          valor_vendido_regular: 'Valor mínimo vendido para reputação regular',
        },
        dados: [],
        dadosHash: '',
      },
      dataDiaNaoTrabalhado: '',
      modalDataNaoTrabalhada: false,
      valoresFreteCidade: {
        carregando: false,
        cabecalho: [
          { text: 'ID', value: 'id' },
          { text: 'Cidade', value: 'nome' },
          { text: 'Valor de frete padrão', value: 'valor_frete' },
          { text: 'Valor adicional', value: 'valor_adicional' },
          { text: 'Frete Expresso', value: 'id_colaborador_ponto_coleta' },
          { text: 'Dias para Entrega', value: 'dias_entregar_cliente' },
        ],
        dados: [],
        dadosIniciais: [],
        listaColaboradoresFreteExpresso: [],
        carregandoBuscaColaboradoresFreteExpresso: false,
      },
      bounce: null,
      loadingDiasTransferenciaSeller: false,
      diasTransferenciaSeller: {},
      botaoSalvarDiasPagamentoSeller: true,
      loadingPorcentagemAntecipacao: false,
      loadingtaxaDevolucaoProdutoErrado: false,
      porcentagemAntecipacao: 0,
      taxaDevolucaoProdutoErrado: 0,
      loadingTaxaBloqueioFornecedor: false,
      taxaBloqueioFornecedor: 0,
      configuracoesFrete: {
        tamanhoRaioPontoParado: null,
        porcentagemDeCortePontos: null,
        minimoEntregasParaCorte: null,
        salvarDisponivel: false,
        carregando: false,
      },
      estados: [],
      pesquisa: '',
      regrasRetencaoSku: {
        anosAposEntregue: 0,
        diasAguardandandoEntrada: 0,
        houveAlteracao: false,
        loading: false,
      },
      carregandoTaxaProdutoBarato: false,
    }
  },
  mounted() {
    this.overlay = true
    const promises = []

    promises.push(this.buscaListaJuros())
    promises.push(this.buscaConfiguracoesEstoqueParado())
    promises.push(this.buscaListaMeiosPagamento())
    promises.push(this.buscaPercentualFreteiros())
    promises.push(this.buscaValoresPontuacoesProdutos())
    promises.push(this.buscaDadasNaoTrabalhadas())
    promises.push(this.buscaEstados())
    promises.push(this.buscaValoresReputacaoFornecedor())
    promises.push(this.buscaDiasTransferenciaColaboradores())
    promises.push(this.buscaPorcentagemComissoes())
    promises.push(this.buscaValorMinimoEntrarFraude())
    promises.push(this.buscaPorcentagemAntecipacao())
    promises.push(this.buscarTaxaProdutoErrado())
    promises.push(this.buscaConfiguracoesFrete())
    promises.push(this.buscaHorariosSeparacao())
    promises.push(this.buscaTaxaBloqueioFornecedor())
    promises.push(this.buscarPrazoRetencaoSku())

    Promise.all(promises).then(() => {
      this.overlay = false
    })
  },
  methods: {
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },
    async buscaDadasNaoTrabalhadas() {
      try {
        const response = await api.get('api_administracao/configuracoes/dia_nao_trabalhado')

        this.listaDeDiaNaoTrabalhados = response.data
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem =
          error?.response?.data?.message || error?.message || 'Falha ao buscar dias não trabalhados'
        this.snackbar.open = true
      }
    },
    async buscaConfiguracoesEstoqueParado() {
      try {
        const resposta = await api.get('api_administracao/configuracoes/estoque_parado')

        this.configuracoesEstoqueParado = resposta.data
        this.configuracoesEstoqueParadoBkp = { ...resposta.data }
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao buscar configurações estoque parado',
        )
      }
    },
    async atualizarConfiguracoesEstoqueParado() {
      try {
        this.carregandoMudarConfiguracoesEstoqueParado = true
        await api.put('api_administracao/configuracoes/estoque_parado', this.configuracoesEstoqueParado)

        this.configuracoesEstoqueParadoBkp = { ...this.configuracoesEstoqueParado }
        this.enqueueSnackbar('Configurações atualizadas com sucesso', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao atualizar dias parado no estoque',
        )
      } finally {
        this.carregandoMudarConfiguracoesEstoqueParado = false
      }
    },
    async buscaListaJuros() {
      return $.ajax({
        type: 'POST',
        url: 'controle/indexController.php',
        dataType: 'json',
        data: {
          action: 'buscaListaJuros',
        },
        beforeSend: function () {
          this.listaTaxas = []
        }.bind(this),
      }).done(
        function (json) {
          if (json.status == 'ok') {
            this.listaTaxas = json.listaJuros
          } else {
            console.log(json.mensagem)
          }
        }.bind(this),
      )
    },
    async salvarConfigTaxas() {
      $.ajax({
        type: 'POST',
        url: 'controle/indexController.php',
        dataType: 'json',
        data: {
          action: 'salvarConfigTaxas',
          listaTaxas: this.listaTaxas,
        },
        beforeSend: function () {
          this.overlay = true
        }.bind(this),
      }).done(
        function (json) {
          this.overlay = false
          if (json.status == 'ok') {
            this.snackbar.color = 'green'
            this.snackbar.mensagem = 'Regras salvas com sucesso.'
          } else {
            this.snackbar.color = 'error'
            this.snackbar.mensagem = json.mensagem
          }
          this.snackbar.open = true
        }.bind(this),
      )
    },
    adicionaRegra() {
      let clone = Object.assign({}, this.itemBluePrit)
      clone.numero_de_parcelas = parseInt(this.listaTaxas[this.listaTaxas.length - 1].numero_de_parcelas) + 1
      this.listaTaxas.push(clone)
    },
    removeRegra(index) {
      this.listaTaxas.splice(index, 1)
    },
    validate() {
      this.$refs.form.validate()
    },
    reset() {
      this.$refs.form.reset()
    },
    resetValidation() {
      this.$refs.form.resetValidation()
    },
    saveAttempt() {
      if (this.configProduto.tipo == 3) {
        this.alertaConfirmacao = true
        return false
      }
    },
    limpaModal() {
      this.configProduto = {
        tipo: 1,
        id_produto: '',
        comissao: 0,
        id_fornecedor: '',
      }
      this.alertaConfirmacao = false
      this.dialog = false
    },
    async buscaListaMeiosPagamento() {
      return MobileStockApi('api_administracao/meios_pagamento')
        .then((res) => res.json())
        .then(async (res) => {
          if (res.status === false) {
            this.snackbar.color = 'error'
            this.snackbar.mensagem = res.message
            this.snackbar.open = true
            return
          }

          this.$set(this, 'listaMeiosPagamento', res.data.meios_pagamento)
          this.listaMeiosPagamentoBkp = JSON.parse(JSON.stringify(res.data.meios_pagamento))
        })
        .catch((err) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao configurar os meios de pagamento.'
          this.snackbar.open = true
        })
    },
    async buscaPercentualFreteiros() {
      return MobileStockApi('api_administracao/taxas_frete')
        .then((res) => res.json())
        .then(async (res) => {
          if (res.status === false) {
            this.snackbar.color = 'error'
            this.snackbar.mensagem = res.message
            this.snackbar.open = true
            return
          }
          this.$set(this, 'percentuaisFreteiros', res.data)
          this.percentuaisFreteirosInicial = JSON.parse(JSON.stringify(res.data))
        })
        .catch(() => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao buscar taxas de frete.'
          this.snackbar.open = true
        })
    },
    salvaMeiosPagamento() {
      this.overlay = true

      MobileStockApi('api_administracao/meios_pagamento', {
        method: 'POST',
        body: JSON.stringify({
          meios_pagamento: this.listaMeiosPagamento,
        }),
      })
        .then((res) => res.json())
        .then(async (res) => {
          if (res.status === false) {
            this.snackbar.color = 'error'
            this.snackbar.mensagem = res.message
            this.snackbar.open = true
            return
          }

          await this.buscaListaMeiosPagamento()
        })
        .catch((err) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao configurar os meios de pagamento.'
          this.snackbar.open = true
        })
        .finally(() => {
          this.overlay = false
        })
    },
    salvaPercentualFreteiros() {
      this.overlay = true

      MobileStockApi('api_administracao/taxas_frete', {
        method: 'POST',
        body: JSON.stringify({
          taxas: this.percentuaisFreteiros,
        }),
      })
        .then((res) => res.json())
        .then(async (res) => {
          if (res.status === false) {
            this.snackbar.color = 'error'
            this.snackbar.mensagem = res.message || 'Ocorreu um erro ao salvar os percentuais de frete.'
            this.snackbar.open = true
            return
          } else {
            this.snackbar.color = 'success'
            this.snackbar.mensagem = 'Atualizado!'
            this.snackbar.open = true
            await this.buscaPercentualFreteiros()
          }
        })
        .catch((err) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao configurar os meios de pagamento.'
          this.snackbar.open = true
        })
        .finally(() => {
          this.overlay = false
        })
    },
    adicionaNovoPercentual() {
      this.percentuaisFreteiros.splice(this.percentuaisFreteiros.length - 1, 0, {
        de: 1,
        ate: 2,
        porcentagem: 1,
      })
    },
    removePercentualFreteiros(index) {
      this.percentuaisFreteiros.splice(index, 1)
    },
    configuraEstruturaFatores(fatores, area) {
      let observacoes = []
      if (area === 'PONTUACAO_PRODUTOS') {
        observacoes = this.pontuacao.observacoes
      } else if (area === 'REPUTACAO_FORNECEDORES') {
        observacoes = this.reputacaoFornecedor.observacoes
      } else {
        throw new Error('Área não encontrada')
      }

      const fatoresEstruturados = Object.keys(fatores).map((chave) => ({
        chave,
        valor: fatores[chave],
        observacao: observacoes[chave],
      }))

      return fatoresEstruturados
    },
    desmontaEstruturaFatores(fatoresEstruturados) {
      const fatores = fatoresEstruturados.reduce((fatores, item) => {
        fatores[item.chave] = item.valor
        return fatores
      }, {})

      return fatores
    },
    buscaValoresPontuacoesProdutos() {
      this.pontuacao.carregando = true
      api
        .get('api_administracao/configuracoes/fatores/PONTUACAO_PRODUTOS')
        .then((json) => {
          const pontuacoesProdutos = this.configuraEstruturaFatores(json.data, 'PONTUACAO_PRODUTOS')
          this.pontuacao.dados = pontuacoesProdutos
          this.pontuacao.dadosHash = JSON.stringify(pontuacoesProdutos)
        })
        .catch(() => {
          this.enqueueSnackbar(
            error?.response?.data?.message ||
              error.message ||
              'Ocorreu um erro ao buscar valores de pontuação dos produtos',
          )
        })
        .finally(() => (this.pontuacao.carregando = false))
    },
    alteraValoresPontuacoesProdutos() {
      this.pontuacao.carregando = true
      const fatores = this.desmontaEstruturaFatores(this.pontuacao.dados)
      api
        .put('api_administracao/configuracoes/fatores/PONTUACAO_PRODUTOS', fatores)
        .then(() => {
          this.pontuacao.dadosHash = JSON.stringify(this.pontuacao.dados)
          this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
        })
        .catch((error) => {
          this.enqueueSnackbar(
            error?.response?.data?.message || error.message || 'Ocorreu um erro ao alterar os valores',
          )
        })
        .finally(() => (this.pontuacao.carregando = false))
    },
    alteraFatoresReputacao() {
      this.reputacaoFornecedor.carregando = true
      const fatores = this.desmontaEstruturaFatores(this.reputacaoFornecedor.dados)
      api
        .put('api_administracao/configuracoes/fatores/REPUTACAO_FORNECEDORES', fatores)
        .then(() => {
          this.reputacaoFornecedor.dadosHash = JSON.stringify(this.reputacaoFornecedor.dados)
          this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
        })
        .catch((error) => {
          this.enqueueSnackbar(
            error?.response?.data?.message || error.message || 'Ocorreu um erro ao alterar os fatores de reputação',
          )
        })
        .finally(() => (this.reputacaoFornecedor.carregando = false))
    },
    async removerDiaNaoTrabalhado(item) {
      try {
        this.loadingRemoveDiaNaoTrabalhado = item.id
        const response = await MobileStockApi(`api_administracao/configuracoes/dia_nao_trabalhado/${item.id}`, {
          method: 'DELETE',
        }).then((res) => res.json())
        if (!response.status) {
          throw new Error(response.message)
        }

        this.listaDeDiaNaoTrabalhados = this.listaDeDiaNaoTrabalhados.filter((item1) => item.id !== item1.id)
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Falha ao remover dia não trabalhado'
        this.snackbar.open = true
      } finally {
        this.loadingRemoveDiaNaoTrabalhado = false
      }
    },
    exibeEventos(date) {
      const [ano, mes, dia] = date.split('-')
      const lista = this.listaDeDiaNaoTrabalhados
        .filter((item) => {
          const [diaLista, mesLista, anoLista] = item.data.split('/')
          if (anoLista == ano && mesLista == mes) {
            return true
          }
        })
        .map((item) => item.data.split('/')[0])

      if (lista.includes(dia)) return true
      return false
    },
    async adicionaDiaNaoTrabalhado() {
      try {
        this.loadingInsereDiaNaoTrabalhado = true
        const response = await MobileStockApi('api_administracao/configuracoes/dia_nao_trabalhado', {
          method: 'POST',
          body: JSON.stringify({
            data: this.dataDiaNaoTrabalhado,
          }),
        }).then((res) => res.json())
        if (!response.status) {
          throw new Error(response.message)
        }
        this.listaDeDiaNaoTrabalhados = [
          { id: response.data.id, data: response.data.data },
          ...this.listaDeDiaNaoTrabalhados,
        ]
        this.dataDiaNaoTrabalhado = ''
        this.modalDataNaoTrabalhada = false
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Falha ao adicionar dia não trabalhado'
        this.snackbar.open = true
      } finally {
        this.loadingInsereDiaNaoTrabalhado = false
      }
    },
    adicionarHorarioSeparacao() {
      this.separacaoFulfillment.horarios.push(this.separacaoFulfillment.novoHorario)
      this.separacaoFulfillment.novoHorario = null
    },
    async buscaHorariosSeparacao() {
      try {
        this.separacaoFulfillment.carregando = true
        const resposta = await api.get('api_administracao/configuracoes/fatores_separacao_fulfillment')

        const consulta = resposta.data
        this.separacaoFulfillment.horarios = consulta.horarios
        this.separacaoFulfillment.horasCarenciaRetirada = consulta.horas_carencia_retirada

        this.separacaoFulfillment.BKP = JSON.stringify({
          horarios: consulta.horarios,
          horasCarenciaRetirada: consulta.horas_carencia_retirada,
        })
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar horários de separação')
      } finally {
        this.separacaoFulfillment.carregando = false
      }
    },
    async salvarHorariosSeparacao() {
      if (this.separacaoFulfillment.carregando) {
        return
      }
      try {
        this.separacaoFulfillment.carregando = true
        const { horarios, horasCarenciaRetirada } = this.separacaoFulfillment
        await api.put('api_administracao/configuracoes/fatores_separacao_fulfillment', {
          horarios: horarios,
          horas_carencia_retirada: horasCarenciaRetirada,
        })

        this.separacaoFulfillment.BKP = JSON.stringify({ horarios, horasCarenciaRetirada })
        this.enqueueSnackbar('Regras de separação fulfillment atualizadas com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Erro ao salvar novas regras de separação',
        )
      } finally {
        this.separacaoFulfillment.carregando = false
      }
    },
    buscaConfiguracoesFrete() {
      this.configuracoesFrete.carregando = true
      MobileStockApi('api_administracao/configuracoes/busca_configuracoes_frete')
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw Error(json.message)
          const {
            percentual_para_cortar_pontos,
            tamanho_raio_padrao_ponto_parado,
            minimo_entregas_para_cortar_pontos,
          } = json.data
          this.configuracoesFrete = {
            ...this.configuracoesFrete,
            porcentagemDeCortePontos: percentual_para_cortar_pontos,
            tamanhoRaioPontoParado: tamanho_raio_padrao_ponto_parado,
            minimoEntregasParaCorte: minimo_entregas_para_cortar_pontos,
          }
        })
        .catch((err) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = err.message || 'Ocorreu um erro ao buscar configurações de frete'
          this.snackbar.open = true
        })
        .finally(() => (this.configuracoesFrete.carregando = false))
    },
    salvarConfiguracoesFrete() {
      this.configuracoesFrete.carregando = true
      MobileStockApi('api_administracao/configuracoes/altera_configuracoes_frete', {
        method: 'PUT',
        body: JSON.stringify({
          percentual_para_cortar_pontos: this.configuracoesFrete.porcentagemDeCortePontos,
          tamanho_raio_padrao_ponto_parado: this.configuracoesFrete.tamanhoRaioPontoParado,
          minimo_entregas_para_cortar_pontos: this.configuracoesFrete.minimoEntregasParaCorte,
        }),
      })
        .then(async (response) => await response.json())
        .then((json) => {
          if (!json.status) throw Error(json.message)
          this.snackbar.color = 'success'
          this.snackbar.mensagem = 'Configurações atualizadas!'
          this.snackbar.open = true
          this.configuracoesFrete.salvarDisponivel = false
        })
        .catch((err) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = err.message || 'Ocorreu um erro ao alterar configurações de frete'
          this.snackbar.open = true
        })
        .finally(() => (this.configuracoesFrete.carregando = false))
    },
    buscaValoresReputacaoFornecedor() {
      this.reputacaoFornecedor.carregando = true
      api
        .get('api_administracao/configuracoes/fatores/REPUTACAO_FORNECEDORES')
        .then((json) => {
          const reputacaoFornecedor = this.configuraEstruturaFatores(json.data, 'REPUTACAO_FORNECEDORES')
          this.reputacaoFornecedor.dados = reputacaoFornecedor
          this.reputacaoFornecedor.dadosHash = JSON.stringify(reputacaoFornecedor)
        })
        .catch(() => {
          this.enqueueSnackbar(
            error?.response?.data?.message ||
              error.message ||
              'Ocorreu um erro ao buscar valores de reputação dos fornecedores',
          )
        })
        .finally(() => (this.reputacaoFornecedor.carregando = false))
    },
    async buscaEstados() {
      try {
        this.valoresFreteCidade.carregando = true
        const resposta = await api.get('api_cliente/estados')
        this.estados = resposta.data.map((estado) => estado.uf)
        this.buscaValoresFreteCidade()
      } catch (err) {
        this.enqueueSnackbar(
          err?.response?.data?.message || err?.message || 'Falha ao buscar valores de frete por cidade',
        )
        this.valoresFreteCidade.carregando = false
      }
    },
    async buscaValoresFreteCidade(estado = 'MG') {
      try {
        this.valoresFreteCidade.carregando = true
        const fretes = await api.get(`api_cliente/fretes_por_estado/${estado}`)

        this.valoresFreteCidade.dados = fretes.data.map((item) => ({
          ...item,
          buscarColaboradorFreteExpresso: '',
          editando: false,
        }))
        this.valoresFreteCidade.dadosIniciais = JSON.parse(JSON.stringify(this.valoresFreteCidade.dados))
      } catch (err) {
        this.enqueueSnackbar(
          err?.response?.data?.message || err?.message || 'Falha ao buscar valores de frete por cidade',
        )
      } finally {
        this.valoresFreteCidade.carregando = false
      }
    },
    mudouPontoColetaCidade(cidade, novoValor) {
      cidade.id_colaborador_ponto_coleta = novoValor.id
      cidade.razao_social = novoValor.nome
      cidade.editando = false
    },
    async alteraValoresFretePorCidade() {
      try {
        this.valoresFreteCidade.carregando = true
        const camposParaValidar = [
          'valor_frete',
          'valor_adicional',
          'dias_entregar_cliente',
          'id_colaborador_ponto_coleta',
        ]

        const valoresAux = this.valoresFreteCidade.dados
          .filter((item) => {
            const itemInicial = this.valoresFreteCidade.dadosIniciais.find((inicial) => inicial.id === item.id)
            return camposParaValidar.some(
              (campo) => item[campo] && JSON.stringify(item[campo]) !== JSON.stringify(itemInicial[campo]),
            )
          })
          .map((item) => ({
            id: item.id,
            valor_frete: item.valor_frete,
            valor_adicional: item.valor_adicional,
            dias_entregar_cliente: item.dias_entregar_cliente,
            id_colaborador_ponto_coleta: item.id_colaborador_ponto_coleta,
          }))

        if (!valoresAux.length) throw Error('Algum valor deve ser alterado!')

        await api.put('api_administracao/configuracoes/atualiza_frete_por_cidade', {
          valores: valoresAux,
        })

        this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
        this.buscaValoresFreteCidade()
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao alterar valores de frete por cidade',
        )
      } finally {
        this.valoresFreteCidade.carregando = false
      }
    },
    async buscaDiasTransferenciaColaboradores() {
      try {
        this.loadingDiasTransferenciaSeller = true

        const resultado = await api.get('api_administracao/configuracoes/datas_transferencia_colaborador')

        this.diasTransferenciaSeller = resultado.data
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message ||
            error?.message ||
            'Ocorreu um erro ao buscar os dias de pagamento dos colaboradores',
        )
      } finally {
        this.loadingDiasTransferenciaSeller = false
      }
    },
    async buscarColaboradoresParaFreteExpresso(valorBusca) {
      if (!valorBusca || this.bounce || this.loading) return
      this.debounce(async () => {
        try {
          this.valoresFreteCidade.carregandoBuscaColaboradoresFreteExpresso = true
          const resultado = await api.get(`api_administracao/ponto_coleta/pesquisar_pontos_coleta`, {
            params: { pesquisa: valorBusca },
          })
          this.valoresFreteCidade.listaColaboradoresFreteExpresso = resultado.data.map((colaborador) => ({
            id: colaborador.id_colaborador,
            nome: colaborador.razao_social,
          }))
        } catch (error) {
          this.enqueueSnackbar(
            error?.response?.data?.message || error?.message || 'Falha ao buscar colaboradores para o frete expresso',
          )
        } finally {
          this.valoresFreteCidade.carregandoBuscaColaboradoresFreteExpresso = false
        }
      }, 1000)
    },
    async atualizaDiasPagamentoColaboradores(event) {
      try {
        this.loadingDiasTransferenciaSeller = true

        let dados = {}
        const inputs = event.target.querySelectorAll('input')

        for (let el of inputs) {
          if (isNaN(el.value)) throw new Error(`O campo ${el.name} deve ser um número!`)
          dados[el.name] = el.value
        }

        const resultado = await api.put('api_administracao/configuracoes/datas_transferencia_colaborador', dados)

        this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message ||
            error?.message ||
            'Ocorreu ao atualizar os dias de pagamento dos colaboradores',
        )
      } finally {
        this.loadingDiasTransferenciaSeller = false
      }
    },

    async buscaPorcentagemComissoes() {
      try {
        this.loadingPorcentagemComissoes = true
        const resposta = await api.get('api_administracao/configuracoes/comissoes')
        this.porcentagemComissoes = resposta.data
        ;(this.porcentagemComissoes.taxaProdutoBaratoMLAnterior = this.porcentagemComissoes.taxa_produto_barato_ml),
          (this.porcentagemComissoes.taxaProdutoBaratoMSAnterior = this.porcentagemComissoes.taxa_produto_barato_ms),
          (this.porcentagemComissoes.custoMaxAplicarTaxaMLAnterior =
            this.porcentagemComissoes.custo_max_aplicar_taxa_ml),
          (this.porcentagemComissoes.custoMaxAplicarTaxaMSAnterior =
            this.porcentagemComissoes.custo_max_aplicar_taxa_ms)
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error.message || 'Falha ao buscar porcentagens de comissões',
        )
      } finally {
        this.loadingPorcentagemComissoes = false
      }
    },

    async atualizaPorcentagemComissoesTransacao() {
      try {
        if (this.porcentagemComissoes?.comissao_direito_coleta?.length === 0) {
          throw Error('Porcentagem de comissão deve ter algum valor!')
        }
        this.loadingPorcentagemComissoes = true
        await api.patch('api_administracao/configuracoes/porcentagem_comissoes_direito_coleta', {
          comissao_direito_coleta: this.porcentagemComissoes.comissao_direito_coleta,
        })
        this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error.message || 'Falha ao atualizar porcentagem de comissão',
        )
      } finally {
        this.loadingPorcentagemComissoes = false
      }
    },

    async alteraPorcentagemComissoes() {
      try {
        this.loadingPorcentagemComissoes = true
        await api.put('api_administracao/configuracoes/porcentagem_comissoes', {
          comissao_ml: this.porcentagemComissoes.porcentagem_comissao_ml,
          comissao_ms: this.porcentagemComissoes.porcentagem_comissao_ms,
          comissao_ponto_coleta: this.porcentagemComissoes.porcentagem_comissao_ponto_coleta,
        })
        this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao alterar porcentagens de comissões',
        )
      } finally {
        this.loadingPorcentagemComissoes = false
      }
    },

    async alteraTaxaProdutoBarato() {
      try {
        if (
          this.porcentagemComissoes.taxa_produto_barato_ml === this.porcentagemComissoes.taxaProdutoBaratoMLAnterior ||
          this.porcentagemComissoes.taxa_produto_barato_ms === this.porcentagemComissoes.taxaProdutoBaratoMSAnterior ||
          this.porcentagemComissoes.custo_max_aplicar_taxa_ml ===
            this.porcentagemComissoes.custoMaxAplicarTaxaMLAnterior ||
          this.porcentagemComissoes.custo_max_aplicar_taxa_ms ===
            this.porcentagemComissoes.custoMaxAplicarTaxaMSAnterior
        ) {
          throw Error('Deve haver alteração em pelo menos um dos campos!')
        }

        this.carregandoTaxaProdutoBarato = true

        await api.put('api_administracao/configuracoes/taxa_produto_barato', {
          taxa_produto_barato_ml: this.porcentagemComissoes.taxa_produto_barato_ml,
          taxa_produto_barato_ms: this.porcentagemComissoes.taxa_produto_barato_ms,
          custo_max_aplicar_taxa_ml: this.porcentagemComissoes.custo_max_aplicar_taxa_ml,
          custo_max_aplicar_taxa_ms: this.porcentagemComissoes.custo_max_aplicar_taxa_ms,
        })
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao alterar taxa de produto barato',
        )
      } finally {
        this.carregandoTaxaProdutoBarato = false
      }
    },

    buscaValorMinimoEntrarFraude() {
      try {
        this.loadingValorMinimoFraude = true
        MobileStockApi(`api_administracao/configuracoes/busca_valor_minimo_fraude`)
          .then((res) => res.json())
          .then((resp) => {
            if (!resp.status) throw Error(resp.message)
            this.valorMinimoFraude = resp.data
          })
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Falha ao buscar valor mínimo para entrar em fraude'
        this.snackbar.open = true
      } finally {
        this.loadingValorMinimoFraude = false
      }
    },

    alteraValorMinimoFraude() {
      try {
        this.loadingValorMinimoFraude = true
        MobileStockApi(`api_administracao/configuracoes/altera_valor_limite_para_entrar_fraude`, {
          method: 'PUT',
          body: JSON.stringify({
            valor: parseFloat(this.valorMinimoFraude),
          }),
        })
          .then((res) => res.json())
          .then((resp) => {
            if (!resp.status) throw Error(resp.message)
            this.snackbar.color = 'success'
            this.snackbar.mensagem = 'Dados alterados com sucesso!'
            this.snackbar.open = true
          })
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Falha ao atualizar valor mínimo pra entrar na fraude'
        this.snackbar.open = true
      } finally {
        this.loadingValorMinimoFraude = false
      }
    },
    buscaPorcentagemAntecipacao() {
      try {
        this.loadingPorcentagemAntecipacao = true
        MobileStockApi(`api_administracao/configuracoes/busca_porcentagem_antecipacao`)
          .then((res) => res.json())
          .then((resp) => {
            this.porcentagemAntecipacao = resp.data
          })
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = 'Falha ao buscar porcentagens de antecipação'
        this.snackbar.open = true
      } finally {
        this.loadingPorcentagemAntecipacao = false
      }
    },

    alteraPorcentagemAntecipacao() {
      try {
        this.loadingPorcentagemAntecipacao = true
        MobileStockApi(`api_administracao/configuracoes/altera_porcentagem_antecipacao`, {
          method: 'PUT',
          body: JSON.stringify({
            porcentagem_antecipacao: this.porcentagemAntecipacao,
          }),
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw Error(resp.message)
            this.snackbar.color = 'success'
            this.snackbar.mensagem = 'Dados alterados com sucesso!'
            this.snackbar.open = true
          })
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = 'Falha ao alterar porcentagem de antecipação'
        this.snackbar.open = true
      } finally {
        this.loadingPorcentagemAntecipacao = false
      }
    },

    buscarTaxaProdutoErrado() {
      api
        .get('api_administracao/configuracoes/busca_taxa_produto_errado')
        .then((res) => {
          this.taxaDevolucaoProdutoErrado = res.data
        })
        .catch((error) => {
          this.enqueueSnackbar(
            error?.response?.data?.message ||
              error.message ||
              'Falha ao buscar a taxa de devolução de produto enviado errado',
          )
        })
    },

    alterarTaxaProdutoErrado() {
      if (!this.taxaDevolucaoProdutoErrado) return
      this.loadingtaxaDevolucaoProdutoErrado = true
      api
        .put('api_administracao/configuracoes/alterar_taxa_produto_errado', {
          taxa: this.taxaDevolucaoProdutoErrado,
        })
        .then(() => {
          this.snackbar.color = 'success'
          this.snackbar.mensagem = 'Taxa alterada com sucesso!'
          this.snackbar.open = true
        })
        .catch((error) => {
          this.enqueueSnackbar(
            error?.response?.data?.message ||
              error?.message ||
              'Falha ao alterar a taxa de devolução de produto enviado errado',
          )
        })
        .finally(() => {
          this.loadingtaxaDevolucaoProdutoErrado = false
        })
    },
    async buscaTaxaBloqueioFornecedor() {
      try {
        this.loadingTaxaBloqueioFornecedor = true
        const resp = await api.get('api_administracao/configuracoes/busca_taxa_bloqueio_fornecedor')

        this.taxaBloqueioFornecedor = resp.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Falha ao buscar taxa de cancelamento')
      } finally {
        this.loadingTaxaBloqueioFornecedor = false
      }
    },

    async alteraTaxaBloqueioFornecedor() {
      try {
        this.loadingTaxaBloqueioFornecedor = true
        await api.put(`api_administracao/configuracoes/altera_taxa_bloqueio_fornecedor`, {
          taxa_bloqueio_fornecedor: this.taxaBloqueioFornecedor,
        })

        this.enqueueSnackbar('Dados alterados com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao atualizar taxa de cancelamento',
        )
      } finally {
        this.loadingTaxaBloqueioFornecedor = false
      }
    },

    async buscarPrazoRetencaoSku() {
      try {
        const resultado = await api.get('api_administracao/configuracoes/prazo_retencao_sku')

        this.regrasRetencaoSku.anosAposEntregue = resultado.data.anos_apos_entregue
        this.regrasRetencaoSku.diasAguardandandoEntrada = resultado.data.dias_aguardando_entrada
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao buscar regras de retenção de SKU',
        )
      }
    },

    async atualizarPrazoRetencaoSku() {
      try {
        this.regrasRetencaoSku.loading = true

        const prazos = {
          anos_apos_entregue: this.regrasRetencaoSku.anosAposEntregue,
          dias_aguardando_entrada: this.regrasRetencaoSku.diasAguardandandoEntrada,
        }

        await api.put('api_administracao/configuracoes/prazo_retencao_sku', prazos)

        this.enqueueSnackbar('Regras de retenção de SKU atualizadas com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao atualizar regras de retenção de SKU',
        )
      } finally {
        this.regrasRetencaoSku.loading = false
      }
    },

    enqueueSnackbar(mensagem = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        open: true,
        mensagem: mensagem,
        color: cor,
      }
    },
  },
  filters: {},
  computed: {
    textoAlertaInfo() {
      switch (this.configProduto.tipo) {
        case 1:
          return false
        case 3:
          return `Atenção, o valor inserido será aplicado a TODOS os produtos consignados!`
        default:
          break
      }
    },
    desabilitarAdicionarHorarioSeparacao() {
      return (
        !this.separacaoFulfillment.novoHorario ||
        this.separacaoFulfillment.carregando ||
        this.separacaoFulfillment.horarios.includes(this.separacaoFulfillment.novoHorario)
      )
    },
    houveAlteracaoMeiosPagamento() {
      return JSON.stringify(this.listaMeiosPagamento) !== JSON.stringify(this.listaMeiosPagamentoBkp)
    },
    houveAlteracaoPercentualFreteiros() {
      return JSON.stringify(this.percentuaisFreteiros) !== JSON.stringify(this.percentuaisFreteirosInicial)
    },
    houveAlteracaoPontuacaoProdutos() {
      return JSON.stringify(this.pontuacao.dados) !== this.pontuacao.dadosHash
    },
    houveAlteracaoValoresFreteCidade() {
      return JSON.stringify(this.valoresFreteCidade.dados) !== JSON.stringify(this.valoresFreteCidade.dadosIniciais)
    },
    houveAlteracaoFatoresReputacao() {
      return JSON.stringify(this.reputacaoFornecedor.dados) !== this.reputacaoFornecedor.dadosHash
    },
    houveAlteracaoSeparacaoFulfillment() {
      const { horarios, horasCarenciaRetirada } = this.separacaoFulfillment
      return JSON.stringify({ horarios, horasCarenciaRetirada }) !== this.separacaoFulfillment.BKP
    },
    houveAlteracaoConfiguracoesEstoqueParado() {
      return JSON.stringify(this.configuracoesEstoqueParado) !== JSON.stringify(this.configuracoesEstoqueParadoBkp)
    },
  },
})
