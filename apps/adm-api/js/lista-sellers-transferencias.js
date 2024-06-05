import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#listaSellersPagar',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      loading: false,
      loadingInteiraTransferencia: false,
      loadingApagarSaque: false,
      loadingPagarManualmente: false,
      disabled: false,
      dialogInteiraTransferencia: false,
      dialogApagarSaque: false,
      dialogPagarManualmente: false,
      dialogInteiraTransferenciaItem: [],
      dialogApagarSaqueItem: [],
      dialogPagarManualmenteItem: '',
      filtro: '',
      snackbar: {
        ativar: false,
        texto: '',
        cor: '',
      },
      listaPagamentosHeader: [
        { text: 'Posição', value: 'posicao' },
        { text: 'ID Transferência', value: 'id_prioridade' },
        { text: 'Data do saque', value: 'data_criacao' },
        { text: 'Titular', value: 'nome_titular' },
        { text: 'Valor do saque', value: 'valor_pagamento' },
        { text: 'Valor faltante:', value: 'valor_pendente' },
        { text: 'Saldo MobileStock', value: 'saldo' },
        { text: 'Previsão Prox. Pgto.', value: 'proximo_pagamento' },
        { text: 'Situação', value: 'situacao' },
        { text: 'Bloqueado?', value: 'pagamento_bloqueado' },
        { text: 'Pagamento prioritario', value: 'opcoes' },
      ],
      listaPagamentos: [],
      listaTotais: [],
      listaPagamentosFiltrados: [],
    }
  },
  methods: {
    enqueueSnackbar(mensagem = 'Ocorreu um erro. Notifique o suporte.', cor = 'error') {
      this.snackbar.texto = mensagem
      this.snackbar.ativar = true
      this.snackbar.cor = cor
    },
    mostrarDialogInteiraTransferencia(item) {
      this.dialogInteiraTransferenciaItem = item.id_prioridade
      this.dialogInteiraTransferencia = true
    },
    fecharDialogInteiraTransferencia() {
      this.dialogInteiraTransferenciaItem = []
      this.dialogInteiraTransferencia = false
    },
    mostrarDialogApagarSaque(item) {
      this.dialogApagarSaqueItem = item.id_prioridade
      this.dialogApagarSaque = true
    },
    fecharDialogApagarSaque() {
      this.dialogApagarSaqueItem = []
      this.dialogApagarSaque = false
    },
    mostrarDialogPagarManualmente(item) {
      this.dialogPagarManualmenteItem = item.id_prioridade
      this.dialogPagarManualmente = true
    },
    fecharDialogPagarManualmente() {
      this.dialogPagarManualmenteItem = ''
      this.dialogPagarManualmente = false
    },
    corReputacao(reputacao) {
      switch (reputacao) {
        case 'MELHOR_FABRICANTE':
          return 'badge badge-primary'
        case 'EXCELENTE':
          return 'badge badge-success'
        case 'REGULAR':
          return 'badge badge-warning'
        case 'RUIM':
          return 'badge badge-danger'
      }
    },
    traduzSituacao(situacao) {
      switch (situacao) {
        case 'EM':
          return { texto: 'Criado/Adiantamento', class: 'badge badge-sm badge-info' }
        case 'CR':
          return { texto: 'Criado', class: 'badge badge-sm badge-dark' }
      }
    },
    async buscarListaPagamentos() {
      try {
        this.loading = true
        this.disabled = true

        const resp = await api.get('api_administracao/transferencias/')

        resp.data.fila.forEach(function (item, index) {
          resp.data.fila[index].posicao = index + 1

          item.valor_pagamento = formataMoeda(item.valor_pagamento)
          item.valor_pendente = formataMoeda(item.valor_pendente)
          item.saldo = formataMoeda(item.saldo)
          item.valor_pago = formataMoeda(item.valor_pago)
        })

        resp.data.total.valor_pagamento = formataMoeda(resp.data.total.valor_pagamento)
        resp.data.total.valor_pendente = formataMoeda(resp.data.total.valor_pendente)
        resp.data.total.saldo = formataMoeda(resp.data.total.saldo)

        this.listaPagamentos = resp.data.fila
        this.listaTotais = resp.data.total
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Erro ao buscar a lista de pagamentos.',
        )
      } finally {
        this.loading = false
        this.disabled = false
      }
    },
    bloqueiaContaPagamento(item) {
      this.loading = true
      this.disabled = true

      try {
        MobileStockApi('api_administracao/cadastro/alterna_conta_bancaria_colaborador', {
          method: 'POST',
          body: JSON.stringify({
            id_conta: item.id,
            acao: item.pagamento_bloqueado,
          }),
        })
          .then((resp) => resp.json())
          .then(() => {
            this.enqueueSnackbar(
              `Conta bancária ${item.pagamento_bloqueado ? '' : 'des'}bloqueada com sucesso!`,
              'success',
            )
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loading = false
        this.disabled = false
      }
    },
    async inteirarTransferencia() {
      this.loadingInteiraTransferencia = true
      this.disabled = true

      try {
        await api.patch(`api_administracao/transferencias/inteirar/${this.dialogInteiraTransferenciaItem}`)

        this.enqueueSnackbar('Transferência inteirado com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao inteirar transferência')
      } finally {
        this.loadingInteiraTransferencia = false
        this.disabled = false
      }
    },
    async apagarSaque() {
      try {
        this.loadingApagarSaque = true
        this.disabled = true

        const resultado = await MobileStockApi(
          `api_administracao/pagamento/deletar_transferencia/${this.dialogApagarSaqueItem}`,
          {
            method: 'DELETE',
          },
        ).then((resp) => resp.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.buscarListaPagamentos()

        this.enqueueSnackbar(resultado.message, 'success')
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loadingApagarSaque = false
        this.disabled = false
        this.dialogApagarSaque = false
      }
    },
    pagarManualmente() {
      if (this.loadingPagarManualmente) return
      this.loadingPagarManualmente = true
      this.disabled = true

      MobileStockApi(`api_administracao/pagamento/pagamento_manual`, {
        method: 'POST',
        body: JSON.stringify({
          id_transferencia: this.dialogPagarManualmenteItem,
        }),
      })
        .then((resp) => resp.json())
        .then((resp) => {
          if (!resp.status) throw new Error(resp.message)

          this.buscarListaPagamentos()

          this.enqueueSnackbar(resp.message, 'success')
        })
        .catch((error) => this.enqueueSnackbar(error))
        .finally(() => {
          this.loadingPagarManualmente = false
          this.disabled = false
          this.dialogPagarManualmente = false
        })
    },
    pesquisa_customizada(value, pesquisa, item) {
      if (pesquisa.length < 3) return

      const pesquisaFiltrada = removeAcentos(pesquisa)

      const resultado = Object.values(item).some(
        (valor) => valor && removeAcentos(valor.toString().toLowerCase()).includes(pesquisaFiltrada.toLowerCase()),
      )

      return resultado
    },
    atualizarListaFiltrada() {
      this.listaPagamentosFiltrados = this.listaPagamentos.filter((item) =>
        this.pesquisa_customizada(null, this.filtro, item),
      )
    },
  },
  computed: {
    somaValoresFiltrados() {
      var soma = this.listaPagamentosFiltrados.reduce((acumulador, pagamento) => {
        var valorLimpo = pagamento.valor_pagamento.replace(/[^0-9,]/g, '').replace(',', '.')
        var valorNumerico = parseFloat(valorLimpo)
        return acumulador + valorNumerico
      }, 0)
      return formataMoeda(soma)
    },
  },
  watch: {
    filtro() {
      this.atualizarListaFiltrada()
    },
  },
  mounted() {
    this.listaPagamentosFiltrados.length > 0 ? this.atualizarListaFiltrada() : this.buscarListaPagamentos()
  },
})
