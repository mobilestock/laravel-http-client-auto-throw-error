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
        BKP_horarios: JSON.stringify([]),
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
      qtdParadoNoEstoque: null,
      qtdParadoNoEstoqueBkp: null,
      pesquisaDiasNaoTrabalhados: '',
      carregandoMudarQtdDiasEstoqueParado: false,
      loadingRemoveDiaNaoTrabalhado: false,
      loadingInsereDiaNaoTrabalhado: false,
      loadingPorcentagemComissoes: false,
      loadingValorMinimoFraude: false,
      pontuacao: {
        carregando: false,
        cabecalho: [
          { text: 'ID', value: 'id' },
          { text: 'Chave', value: 'chave' },
          { text: 'Valor', value: 'valor' },
        ],
        dados: [],
        dadosHash: '',
      },
      reputacaoFornecedor: {
        carregando: false,
        cabecalho: [
          { text: 'ID', value: 'id' },
          { text: 'Chave', value: 'chave' },
          { text: 'Valor', value: 'valor' },
        ],
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
          { text: 'Frete Expresso', value: 'id_colaborador_transportador' },
          { text: 'Dias para Entrega', value: 'dias_entregar_frete' },
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
      porcentagemAntecipacao: 0,
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
    }
  },
  mounted() {
    this.overlay = true
    const promises = []

    promises.push(this.buscaListaJuros())
    promises.push(this.buscaQtdDiasParadoNoEstoque())
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
    async buscaQtdDiasParadoNoEstoque() {
      try {
        const resposta = await api.get('api_administracao/configuracoes/dias_produto_parado_estoque')

        this.qtdParadoNoEstoque = resposta.data
        this.qtdParadoNoEstoqueBkp = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Falha busca dias parado no estoque')
      }
    },
    async atualizarQtdDiasEstoqueParado() {
      try {
        this.carregandoMudarQtdDiasEstoqueParado = true
        await api.patch('api_administracao/configuracoes/dias_produto_parado_estoque', {
          dias: this.qtdParadoNoEstoque,
        })

        this.qtdParadoNoEstoqueBkp = this.qtdParadoNoEstoque
        this.enqueueSnackbar('Dias atualizados com sucesso', 'success')
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Falha ao atualizar dias parado no estoque',
        )
      } finally {
        this.carregandoMudarQtdDiasEstoqueParado = false
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
    // editarComissao(tipo, produto) {
    //   this.configProduto.tipo = tipo;
    //   if (produto) {
    //     this.configProduto.comissao = produto.porcentagem_comissao;
    //     this.configProduto.comissao_cnpj = produto.porcentagem_comissao_cnpj;
    //     this.configProduto.id_produto = produto.id;
    //   }

    //   if (tipo == 2) {
    //     this.configProduto.id_fornecedor = this.filtros.fornecedor;
    //   }
    //   this.dialog = true;
    // },
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
      try {
        const response = await api.get('api_administracao/taxas_frete')
        this.percentuaisFreteiros = response.data
        this.percentuaisFreteirosInicial = JSON.parse(JSON.stringify(response.data))
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Ocorreu um erro ao buscar taxas de frete.'
        this.snackbar.open = true
      }
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
    buscaValoresPontuacoesProdutos() {
      this.pontuacao.carregando = true
      MobileStockApi('api_administracao/produtos/busca_fatores_pontuacao')
        .then(async (response) => await response.json())
        .then((json) => {
          this.pontuacao.dados = json.data
          this.pontuacao.dadosHash = JSON.stringify(json.data)
        })
        .catch(() => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao buscar valores de pontuação dos produtos'
          this.snackbar.open = true
        })
        .finally(() => (this.pontuacao.carregando = false))
    },
    alteraValoresPontuacoesProdutos() {
      this.pontuacao.carregando = true
      MobileStockApi('api_administracao/produtos/alterar_fatores_pontuacao', {
        method: 'PUT',
        body: JSON.stringify(this.pontuacao.dados),
      })
        .then(async (response) => await response.json())
        .then((json) => {
          if (json.status == false) throw Error(json.message)
          this.pontuacao.dadosHash = JSON.stringify(this.pontuacao.dados)
          this.snackbar.color = 'success'
          this.snackbar.mensagem = 'Dados alterados com sucesso!'
          this.snackbar.open = true
        })
        .catch((error) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = error.message || 'Ocorreu um erro ao alterar os valores'
          this.snackbar.open = true
        })
        .finally(() => (this.pontuacao.carregando = false))
    },
    alteraFatoresReputacao() {
      this.reputacaoFornecedor.carregando = true
      MobileStockApi('api_administracao/configuracoes/altera_fatores_reputacao', {
        method: 'PUT',
        body: JSON.stringify(this.reputacaoFornecedor.dados),
      })
        .then(async (response) => await response.json())
        .then((json) => {
          if (json.status == false) throw Error(json.message)
          this.reputacaoFornecedor.dadosHash = JSON.stringify(this.reputacaoFornecedor.dados)
          this.snackbar.color = 'success'
          this.snackbar.mensagem = 'Dados alterados com sucesso!'
          this.snackbar.open = true
        })
        .catch((error) => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = error.message || 'Ocorreu um erro ao alterar os fatores de reputação'
          this.snackbar.open = true
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
        const resposta = await api.get('api_administracao/configuracoes/busca_horarios_separacao')

        this.separacaoFulfillment.horarios = resposta.data
        this.separacaoFulfillment.BKP_horarios = JSON.stringify(resposta.data)
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem =
          error?.response?.data?.message || error?.message || 'Erro ao buscar horários de separação'
        this.snackbar.open = true
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
        await api.put('api_administracao/configuracoes/altera_horarios_separacao', {
          horarios: this.separacaoFulfillment.horarios,
        })

        this.separacaoFulfillment.BKP_horarios = JSON.stringify(this.separacaoFulfillment.horarios)
        this.snackbar.color = 'success'
        this.snackbar.mensagem = 'Horários de separação salvos com sucesso!'
        this.snackbar.open = true
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem =
          error?.response?.data?.message || error?.message || 'Erro ao salvar horários de separação'
        this.snackbar.open = true
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
    async buscaValoresReputacaoFornecedor() {
      this.reputacaoFornecedor.carregando = true
      MobileStockApi('api_administracao/configuracoes/busca_fatores_reputacao')
        .then(async (response) => await response.json())
        .then((json) => {
          this.reputacaoFornecedor.dados = json.data
          this.reputacaoFornecedor.dadosHash = JSON.stringify(json.data)
        })
        .catch(() => {
          this.snackbar.color = 'error'
          this.snackbar.mensagem = 'Ocorreu um erro ao buscar valores de reputação dos fornecedores'
          this.snackbar.open = true
        })
        .finally(() => (this.reputacaoFornecedor.carregando = false))
    },
    async buscaEstados() {
      try {
        this.valoresFreteCidade.carregando = true
        const resposta = await api.get('api_administracao/configuracoes/estados')
        this.estados = resposta.data
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
        const fretes = await api.get(`api_administracao/configuracoes/fretes_por_estado/${estado}`)

        this.valoresFreteCidade.dados = fretes.data.map((item) => ({
          ...item,
          buscarColaboradorFreteExpresso: '',
          editando: false,
        }))
        this.valoresFreteCidade.dadosIniciais = fretes.data.map((item) => ({ ...item }))
      } catch (err) {
        this.enqueueSnackbar(
          err?.response?.data?.message || err?.message || 'Falha ao buscar valores de frete por cidade',
        )
      } finally {
        this.valoresFreteCidade.carregando = false
      }
    },
    async alteraValoresFretePorCidade() {
      try {
        this.valoresFreteCidade.carregando = true
        const camposParaValidar = [
          'valor_frete',
          'valor_adicional',
          'dias_entregar_frete',
          'colaboradorFreteExpressoSelecionado',
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
            dias_entregar_frete: item.dias_entregar_frete,
            id_colaborador_transportador: item.colaboradorFreteExpressoSelecionado?.id,
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
          'error',
        )
      } finally {
        this.valoresFreteCidade.carregando = false
      }
    },
    async buscaDiasTransferenciaColaboradores() {
      try {
        this.loadingDiasTransferenciaSeller = true

        const resultado = await MobileStockApi('api_administracao/configuracoes/datas_transferencia_colaborador').then(
          (resp) => resp.json(),
        )

        if (!resultado.status) throw new Error(resultado.message)

        this.diasTransferenciaSeller = resultado.data
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error.message || 'Ocorreu um erro ao buscar os dias de pagamento dos colaboradores'
        this.snackbar.open = true
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

        const resultado = await MobileStockApi('api_administracao/configuracoes/datas_transferencia_colaborador', {
          method: 'PUT',
          body: JSON.stringify(dados),
        }).then((resp) => resp.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.snackbar.color = 'success'
        this.snackbar.mensagem = resultado.message || 'Dados alterados com sucesso!'
        this.snackbar.open = true
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error.message || 'Ocorreu um erro ao buscar os dias de pagamento dos colaboradores'
        this.snackbar.open = true
      } finally {
        this.loadingDiasTransferenciaSeller = false
      }
    },

    buscaPorcentagemComissoes() {
      try {
        this.loadingPorcentagemComissoes = true
        MobileStockApi('api_administracao/configuracoes/busca_porcentagem_comissoes')
          .then((res) => res.json())
          .then((resp) => {
            this.porcentagemComissoes = resp.data
          })
      } catch (error) {
        this.snackbar.color = 'error'
        this.snackbar.mensagem = error?.message || 'Falha ao buscar porcentagens de comissões'
        this.snackbar.open = true
      } finally {
        this.loadingPorcentagemComissoes = false
      }
    },

    alteraPorcentagemComissoes(e) {
      try {
        this.loadingPorcentagemComissoes = true
        MobileStockApi('api_administracao/configuracoes/altera_porcentagem_comissoes', {
          method: 'PUT',
          body: JSON.stringify({
            comissao_ml: e.target[1].value,
            comissao_ms: e.target[3].value,
            comissao_ponto_coleta: e.target[5].value,
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
        this.snackbar.mensagem = error?.message || 'Falha ao alterar porcentagens de comissões'
        this.snackbar.open = true
      } finally {
        this.loadingPorcentagemComissoes = false
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
    houveAlteracaoHorariosSeparacaoFulfillment() {
      return JSON.stringify(this.separacaoFulfillment.horarios) !== this.separacaoFulfillment.BKP_horarios
    },
    houveAlteracaoQtdDiasEstoqueParado() {
      return this.qtdParadoNoEstoque !== this.qtdParadoNoEstoqueBkp
    },
  },
})
