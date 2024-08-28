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
      rules: [(value) => (value && value.length >= 3) || 'Digite pelo menos 3 caracteres'],
      snackbar: {
        open: false,
        color: '',
        message: '',
      },
      dialog: false,
      usuarioSuperAdmin: false,
      saldo: [],
      saldo_total: '',
      loading: false,
      colaboradores: [],
      header_extrato: [
        this.itemGrade('Id Lançamento', 'id'),
        this.itemGrade('Id Comissão', 'id_comissao'),
        this.itemGrade('Data/Hora', 'data_emissao'),
        this.itemGrade('Origem', 'origem'),
        this.itemGrade('Transação', 'transacao_origem'),
        this.itemGrade('Crédito', 'credito'),
        this.itemGrade('Débito', 'debito'),
        this.itemGrade('Saldo', 'saldo_atual'),
        this.itemGrade('Motivo lançamento', 'motivo_lancamento'),
      ],
      header: [
        this.itemGrade('id', 'id_colaborador'),
        this.itemGrade('Razão social', 'razao_social'),
        this.itemGrade('telefone', 'telefone'),
        this.itemGrade('Extrato', 'extrato'),
      ],
      header_trocas: [
        this.itemGrade('Id do Produto', 'id_produto'),
        this.itemGrade('Tamanho', 'nome_tamanho'),
        this.itemGrade('Foto', 'foto'),
        this.itemGrade('qrcode', 'qrcode'),
        this.itemGrade('Situação', 'situacao'),
        this.itemGrade('Data', 'data_hora'),
      ],
      header_lancamentos: [
        this.itemGrade('Previsão', 'data_previsao'),
        this.itemGrade('Valor', 'valor'),
        this.itemGrade('Info', 'details'),
      ],
      header_todos_lancamentos: [this.itemGrade('Nome', 'recebedor', true), this.itemGrade('Valor', 'valor', true)],
      filtro: '',
      id_colaborador: 0,
      detalhes_extrato: [],
      modal: false,
      colaboradorSelecionado: [],
      carregandoEhRevendedor: false,
      carregandoCadastrarRevendedor: false,
      abrirModalCriarLojaMed: false,
      cadastroLojaMed: {},
      extratos: [],
      modal_trocas: false,
      trocas: [],
      modal_lancamento: false,
      debito_ou_credito: '',
      valor_lancamento: 0,
      foo: 0,
      menu: false,
      menu2: '',
      dataInicial: '',
      dataFinal: '',
      dataInicialFormatada: '',
      dataFinalFormatada: '',
      modal_lancamentos_futuros: false,
      lancamentos_futuros: [],
      credito_vendas_nao_entregues: 0,
      selectedItem: 1,
      geral: false,
      switch1: false,
      modal_total_de_lancamentos: false,
      lancamentos_futuros_data_especifica: [],
      show: false,
      botaoGerar: false,
      nomeUsuario: cabecalhoVue.user.nome.toLowerCase().trim(),
      qrcodeProduto: null,
      exibirQrcodeProduto: false,
      valorTotalFulfillment: 0,
      pesquisaId: '',
    }
  },

  async mounted() {
    const urlParams = new URLSearchParams(window.location.search)
    const idCliente = urlParams.get('id')
    if (idCliente) {
      this.pesquisaId = idCliente
      await this.buscaColaboradorPorId({ target: [null, { value: idCliente }] })
      const item = this.colaboradores[0]
      this.buscaDadosExtrato(item)
    }
  },

  methods: {
    enqueueSnackbar(mensagem, cor = 'error') {
      this.snackbar = {
        open: true,
        color: cor,
        message: mensagem,
      }
    },

    itemGrade(label, value, sortable) {
      return {
        text: label,
        align: 'start',
        sortable: sortable,
        value: value,
      }
    },

    buscaSaldo() {
      this.loading = true
      return MobileStockApi('api_administracao/pay/busca/saldo', {
        method: 'get',
      })
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status || !json.data) {
            throw new Error(json.message)
          }
          this.saldo = json.data
          this.salto_total =
            parseFloat(this.saldo.cliente) + parseFloat(this.saldo.fornecedores) + parseFloat(this.saldo.mobile)
        })
        .catch((err) => {
          this.snakbar.open = true
          this.snakbar.color = 'error'
          this.snakbar.message = err?.message || err || 'Não foi possível buscar o saldo'
        })
        .finally(() => {
          this.loading = false
          this.dialog = true
        })
    },

    async buscaColaborador(e) {
      try {
        this.loading = true
        this.filtro = e.target[1].value
        if (this.filtro.length < 4) {
          throw new Error('Dígite pelo menos 4 caracteres')
        }

        const parametros = new URLSearchParams({
          filtro: this.filtro,
        })
        const resposta = await api.get(`api_administracao/cadastro/lista_filtrada_colaboradores?${parametros}`)
        this.colaboradores = resposta.data?.map((colaborador) => ({
          ...colaborador,
          telefone: formataTelefone(colaborador.telefone || ''),
        }))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar colaborador')
      } finally {
        this.loading = false
      }
    },

    async buscaColaboradorPorId(item) {
      try {
        this.filtro = item.target[1].value
        this.loading = true
        if (!this.filtro) {
          throw new Error('Digite um ID para buscar')
        }

        const resp = await api.get(`api_administracao/cadastro/busca/colaboradores/${this.filtro}`)
        this.colaboradores = [resp.data].map((colaborador) => ({
          ...colaborador,
          telefone: formataTelefone(colaborador.telefone || ''),
        }))
      } catch (err) {
        this.enqueueSnackbar(err?.response?.data?.message || err?.message || 'Não foi possível buscar o colaborador')
      } finally {
        this.loading = false
      }
    },

    async buscaExtratoColaborador(item) {
      if (['356', '526'].includes(localStorage.getItem('idUsuarioLogado'))) {
        this.usuarioSuperAdmin = true
      }

      this.loading = true
      this.colaboradorSelecionado = item
      this.id_colaborador = item.id_colaborador
      this.modal = false
      await MobileStockApi(
        `api_administracao/pay/busca_extrato_colaborador/${this.id_colaborador}?de=${this.dataInicial}&ate=${this.dataFinal}`,
      )
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status || !json.data) {
            throw new Error(json.message)
          }
          json.data.dados.forEach((i) => (i.link_transacao = `transacao-detalhe.php?id=${i.transacao_origem}`))
          this.detalhes_extrato = json.data
          this.extratos = this.detalhes_extrato.dados.map((extrato) => {
            return extrato
          })
          this.buscaLojaMed(item.id_colaborador)
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err?.message || err || 'Não foi possível buscar os detalhes do extrato'
        })
        .finally(() => {
          this.modal = true
          this.loading = false
        })
    },

    listaTrocasAgendadas() {
      this.modal = false
      this.loading = true
      MobileStockApi(`api_administracao/troca/lista_trocas/${this.colaboradorSelecionado.id_colaborador}`)
        .then((resp) => resp.json())
        .then((json) => {
          if (json?.message !== undefined) {
            throw new Error(json.message)
          }
          this.trocas = json
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err?.message || err || 'Não foi possível buscar as trocas'
        })
        .finally(() => {
          this.loading = false
          this.modal_trocas = true
        })
    },

    async limpaItoken() {
      this.modal = false
      this.loading = true
      await MobileStockApi(`api_administracao/cadastro/limpa_itoken/${this.colaboradorSelecionado.id_colaborador}`)
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status || !json.data) {
            throw new Error(json.message)
          }
          this.snackbar.open = true
          this.snackbar.color = 'success'
          this.snackbar.message = json.message
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err?.message || err || 'Não foi possível limpar o Itoken do cliente'
        })
        .finally(() => {
          this.loading = false
          this.modal = true
        })
    },

    abreEstoque() {
      window.open(`estoque-detalhado.php?id=${this.colaboradorSelecionado.id_colaborador}`, '_blank')
    },

    async bloqueiaAdiantamento() {
      this.modal = false
      this.loading = true
      await MobileStockApi(
        `api_administracao/cadastro/bloqueia_adiantamentos/${this.colaboradorSelecionado.id_colaborador}`,
      )
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status || !json.data) {
            throw new Error(json.message)
          }
          this.detalhes_extrato.adiantamento_bloqueado = !this.detalhes_extrato.adiantamento_bloqueado
          this.snackbar.open = true
          this.snackbar.color = 'success'
          this.snackbar.message = json.message
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err?.message || err || 'Erro ao processar requisição'
        })
        .finally(() => {
          this.loading = false
          this.modal = true
        })
    },

    geraLancamento(e) {
      this.motivo_lancamento = e.currentTarget[0].value
      if (this.motivo_lancamento === '') {
        this.snackbar.open = true
        this.snackbar.color = 'error'
        this.snackbar.message = 'Escreva o motivo desse lançamento'
        return
      }
      if (this.debito_ou_credito === '') {
        this.snackbar.open = true
        this.snackbar.color = 'error'
        this.snackbar.message = 'Selecione uma ação para ser gerada'
        return
      }
      this.loading = true
      this.modal_lancamento = false
      this.modal = false
      return MobileStockApi(
        `api_administracao/pay/gera_lancamento_manual/${this.colaboradorSelecionado.id_colaborador}`,
        {
          method: 'POST',
          body: JSON.stringify({
            debito_ou_credito: this.debito_ou_credito,
            valor: this.valor_lancamento,
            motivo: this.motivo_lancamento,
          }),
        },
      )
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
          this.snackbar.open = true
          this.snackbar.color = 'success'
          this.snackbar.message = 'Lançamento gerado com sucesso!'
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err?.message || err || 'Não foi possível gerar o lançamento'
        })
        .finally(() => {
          this.loading = false
          this.modal = true
          this.buscaExtratoColaborador(this.colaboradorSelecionado)
          this.botaoGerar = false
        })
    },

    credito() {
      if (this.debito_ou_credito === 'P') {
        this.debito_ou_credito = ''
        this.botaoGerar = false
        return
      }
      this.debito_ou_credito = 'P'
      this.botaoGerar = true
    },

    debito() {
      if (this.debito_ou_credito === 'R') {
        this.debito_ou_credito = ''
        this.botaoGerar = false
        return
      }
      this.debito_ou_credito = 'R'
      this.botaoGerar = true
    },

    abreModalGerarLancamento(e) {
      this.valor_lancamento = e.currentTarget[1].value
      this.modal_lancamento = true
    },

    increment() {
      this.foo = parseInt(this.foo, 10) + 1
    },

    decrement() {
      this.foo = parseInt(this.foo, 10) - 1
    },

    limpar() {
      const data = new Date()
      const dia = data.getDate()
      const mes = data.getMonth() + 1
      const ano = data.getFullYear()
      this.dataInicial = `${ano}/${mes}/${dia}`
      this.dataFinal = `${ano}/${mes}/${dia}`
      this.buscaExtratoColaborador(this.colaboradorSelecionado)
    },

    filtraPorData() {
      this.dataInicial = this.dataInicial
      this.dataFinal = this.dataFinal
      this.buscaExtratoColaborador(this.colaboradorSelecionado)
    },

    formataData(data) {
      if (!data) return null
      const dataAux = data.replace(/([0-9]{4})-([0-9]{2})-([0-9]{2})/, '$3-$2-$1')
      return dataAux
    },

    lancamentosFuturos() {
      this.modal = false
      this.loading = true
      api(
        `api_administracao/pay/lancamentos_futuros?id_colaborador=${this.colaboradorSelecionado.id_colaborador}&geral=${this.geral}`,
      )
        .then((json) => {
          this.lancamentos_futuros = json.data.lancamentos
          this.credito_vendas_nao_entregues = json.data.credito_vendas_nao_entregues
        })
        .catch((err) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = err.response?.data.message || 'Não foi possível buscar os lançamentos futuros'
        })
        .finally(() => {
          this.modal = true
          this.loading = false
          this.modal_lancamentos_futuros = true
        })
    },

    total_de_lancamentos() {
      this.geral === true ? (this.geral = false) : (this.geral = true)
      this.modal_lancamentos_futuros = false
      this.lancamentosFuturos(this.geral)
    },

    buscaLancamentosPorData(item) {
      this.modal_total_de_lancamentos = true
      this.lancamentos_futuros_data_especifica = item.valores
    },

    async buscaLojaMed(idColaborador) {
      try {
        this.carregandoEhRevendedor = true

        const resposta = await MobileStockApi(`api_administracao/cadastro/loja_med/busca/${idColaborador}`, {
          method: 'POST',
        })
        if (!resposta.ok) {
          throw new Error('Loja Med desse colaborador não encontrada')
        }

        this.colaboradorSelecionado.ehRevendedor = true
      } catch (error) {
        this.colaboradorSelecionado.ehRevendedor = false
      } finally {
        this.carregandoEhRevendedor = false
      }
    },

    gerirModalCriarLojaMed() {
      if (this.carregandoEhRevendedor) {
        this.enqueueSnackbar('Aguarde a verificação se o colaborador é revendedor')
      } else if (this.colaboradorSelecionado.ehRevendedor) {
        this.enqueueSnackbar('Este colaborador já é revendedor', 'warning')
      } else {
        this.abrirModalCriarLojaMed = true
      }
    },

    async criarLojaMed() {
      try {
        if (this.carregandoCadastrarRevendedor) return
        this.carregandoCadastrarRevendedor = true

        await api.post('api_administracao/cadastro/loja_med', {
          id_revendedor: this.colaboradorSelecionado.id_colaborador,
          nome: this.cadastroLojaMed.nome,
          url: this.cadastroLojaMed.url,
          id_usuario: this.colaboradorSelecionado.id_usuario,
        })

        this.enqueueSnackbar('Loja Med cadastrada com sucesso', 'success')
        this.colaboradorSelecionado.ehRevendedor = true
        this.abrirModalCriarLojaMed = false
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao cadastrar loja med')
      } finally {
        this.carregandoCadastrarRevendedor = false
      }
    },

    async adicionaSenhaTemporaria(senha) {
      this.modal = false
      this.loading = true
      await MobileStockApi(`api_administracao/nova_senha_temporaria`, {
        method: 'POST',
        body: JSON.stringify({
          senha: `${this.nomeUsuario}-${senha.target[0].value}`,
          id_colaborador: this.colaboradorSelecionado.id_colaborador,
        }),
      })
        .then(async (resp) => await resp.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
        })
        .catch((error) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = error?.message || error || 'Não foi possível gerar uma senha temporária'
        })
        .finally(() => {
          this.modal = true
          this.loading = false
        })
    },

    agendado(item) {
      if (item.situacao == 'AGENDADO') {
        return 'orange'
      }
    },

    forcaEntradaFraude() {
      this.modal = false
      this.loading = true
      MobileStockApi('api_administracao/fraudes/insere_manualmente_fraude', {
        method: 'POST',
        body: JSON.stringify({
          id_colaborador: this.colaboradorSelecionado.id_colaborador,
        }),
      })
        .then(async (resp) => resp.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
          this.snackbar.color = 'success'
          this.snackbar.message = 'Inserção concluida com sucesso!'
          this.snackbar.open = true
        })
        .catch((error) => {
          this.snackbar.open = true
          this.snackbar.color = 'error'
          this.snackbar.message = error?.message || error || 'Não foi possível inserir colaborador na fraude'
        })
        .finally(() => {
          this.modal = true
          this.loading = false
        })
    },

    async buscaDadosFulfillment() {
      try {
        this.loading = true
        const resp = await api.get(
          `api_administracao/fornecedor/busca_valor_total_fulfillment/${this.colaboradorSelecionado.id_colaborador}`,
        )

        this.valorTotalfulfillment = resp.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar valor do fulfillment')
      } finally {
        this.loading = false
      }
    },

    buscaDadosExtrato(item) {
      this.buscaExtratoColaborador(item)
      this.buscaDadosFulfillment()
    },
  },

  watch: {
    dataInicial() {
      this.dataInicialFormatada = this.formataData(this.dataInicial)
    },

    dataFinal() {
      this.dataFinalFormatada = this.formataData(this.dataFinal)
    },
    qrcodeProduto(valor) {
      valor ? (this.exibirQrcodeProduto = true) : (this.exibirQrcodeProduto = false)
    },
  },

  computed: {
    dataInicialProcessada() {
      return this.formatarData(this.dataInicial)
    },

    dataFinalProcessada() {
      return (this.dataFinalFormatada = this.formatarData)
    },
  },

  filters: {
    dinheiro(value) {
      if (isNaN(parseFloat(value))) return 'R$0,00'
      return parseFloat(value).toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
  },
})
