import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#monitoraPrevisoesVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      bounce: null,
      qrCode: null,
      diaAtual: null,
      horaAtual: null,
      cidadeSelecionada: null,
      informacoesProduto: null,
      informacoesFornecedor: null,
      informacoesPontoColeta: null,
      informacoesTransportador: null,
      mostrarHorariosSeparacao: false,
      mostrarAgendaPontoColeta: false,
      abirModalQrCode: false,
      carregandoHorarios: false,
      carregandoPesquisa: false,
      pesquisaProduto: '',
      pesquisaTransportador: '',
      diasNaoTrabalhados: [],
      horariosSeparacao: [],
      cidades: [],
      snackbar: {
        ativar: false,
        cor: '',
        texto: '',
      },
      diasDaSemana: [
        {
          text: 'Domingo',
          value: 'DOMINGO',
        },
        {
          text: 'Segunda-Feira',
          value: 'SEGUNDA',
        },
        {
          text: 'Terça-Feira',
          value: 'TERCA',
        },
        {
          text: 'Quarta-Feira',
          value: 'QUARTA',
        },
        {
          text: 'Quinta-Feira',
          value: 'QUINTA',
        },
        {
          text: 'Sexta-Feira',
          value: 'SEXTA',
        },
        {
          text: 'Sábado',
          value: 'SABADO',
        },
      ],

      FULFILLMENT_headers: this.headersPadrao('FULFILLMENT'),
      EXTERNO_headers: this.headersPadrao('EXTERNO'),
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
    itemGrades(campo, valor, ordernavel = false, estilizacao = 'text-light grey darken-3') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        class: estilizacao,
        align: 'center',
      }
    },
    headersPadrao(area) {
      const headers = [
        this.itemGrades('Cidade', 'nome', true),
        this.itemGrades('Média Envio Responsável', `media_envio_${area.toLowerCase()}`),
        this.itemGrades('Enviar ao Ponto de Coleta', 'dias_enviar_pedido'),
        this.itemGrades('Chegar no Ponto de Coleta', 'dias_pedido_chegar'),
        this.itemGrades('Entregar ao Cliente', 'dias_entregar_cliente'),
        this.itemGrades('Previsão Mínima', 'previsao_minima', false, 'text-success grey darken-3'),
        this.itemGrades('Margem de Erro', 'dias_margem_erro'),
        this.itemGrades('Previsão Máxima', 'previsao_maxima', false, 'text-danger grey darken-3'),
      ]
      if (area === 'FULFILLMENT') {
        const index = headers.findIndex((item) => item.value === `media_envio_${area.toLowerCase()}`)
        headers.splice(index, 1)
      }

      return headers
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        cor: cor,
        texto: texto,
      }
    },
    diasEnviarAoPontoColeta() {
      if (!this.informacoesPontoColeta?.horarios.length) return null

      const data = new Date()
      let indexSemana = data.getDay()
      let totalDiasPassou = 0
      let qtdDiasEnviar = 0
      let finalizado = false
      while (!finalizado) {
        const horariosDisponiveisDoDia = this.informacoesPontoColeta?.horarios?.filter(
          (item) =>
            item.dia === this.diasDaSemana[indexSemana].value &&
            (qtdDiasEnviar > 0 || this.horarioEhMaiorQue(item.horario, this.horaAtual)),
        )

        const dataCalculo = new Date()
        dataCalculo.setDate(data.getDate() + totalDiasPassou)
        const ehDiaUtil = this.ehDiaUtil(dataCalculo)
        if (!horariosDisponiveisDoDia?.length || !ehDiaUtil) {
          if (ehDiaUtil) {
            qtdDiasEnviar++
          }

          totalDiasPassou++
          indexSemana++
          if (indexSemana > 6) {
            indexSemana = 0
          }
          continue
        }

        finalizado = true
      }

      return qtdDiasEnviar
    },
    calculaPrevisaoMinima(cidade, area) {
      if (!cidade) return
      const diasEnviarAoPontoColeta = this.diasEnviarAoPontoColeta()
      if (diasEnviarAoPontoColeta === null) return

      const campos = [diasEnviarAoPontoColeta, this.informacoesPontoColeta.dias_pedido_chegar]
      if (area === 'EXTERNO') {
        campos.push(this.informacoesProduto.EXTERNO)
      }
      if (this.informacoesTransportador?.tipo_ponto === 'PM') {
        campos.push(cidade.dias_entregar_cliente)
      }

      const total = campos.reduce((acc, item) => acc + parseInt(item || 0), 0)

      return total
    },
    calculaPrevisaoMaxima(cidade, area) {
      if (!cidade) return
      const minimoDias = this.calculaPrevisaoMinima(cidade, area)
      if (typeof minimoDias !== 'number') return
      const total = minimoDias + cidade.dias_margem_erro

      return total
    },
    alertaDiasIndefinidos(cidade) {
      if (!cidade) return
      const campos = [cidade.dias_margem_erro]
      if (this.informacoesTransportador?.tipo_ponto === 'PM') {
        campos.push(cidade.dias_entregar_cliente)
      }
      const possuiAlgoErrado = campos.includes(null) || !this.informacoesPontoColeta?.horarios.length

      return possuiAlgoErrado
    },
    textoPrevisao(area) {
      if (!area || !this.cidadeSelecionada || this.alertaDiasIndefinidos(this.cidadeSelecionada)) return

      const semana = [
        'Domingo',
        'Segunda-feira',
        'Terça-feira',
        'Quarta-feira',
        'Quinta-feira',
        'Sexta-feira',
        'Sábado',
      ]
      const hoje = new Date()
      const cidade = this.cidadeSelecionada
      const somaMinimo = this.calculaPrevisaoMinima(this.cidadeSelecionada, area)
      const dataMinimo = this.calcularData(hoje, somaMinimo)
      const somaMaximo = this.calculaPrevisaoMaxima(this.cidadeSelecionada, area)
      const dataMaximo = this.calcularData(hoje, somaMaximo)
      const dataMinimoFormatada = this.formataData(dataMinimo)
      const dataMaximoFormatada = this.formataData(dataMaximo)
      const diaMinimo = dataMinimo.getDay()

      return `Receba em ${cidade.nome} entre ${semana[diaMinimo]} (${dataMinimoFormatada}) e ${dataMaximoFormatada}`
    },
    calcularData(data, somaDias) {
      const dataBase = new Date(data)
      let diasAdicionados = 0
      while (diasAdicionados < somaDias) {
        dataBase.setDate(dataBase.getDate() + 1)

        if (this.ehDiaUtil(dataBase)) {
          diasAdicionados++
        }
      }

      return dataBase
    },
    formataData(data) {
      data = new Date(data)
      const dataFormatada = data.toLocaleDateString('pt-br', { day: '2-digit', month: '2-digit' })

      return dataFormatada
    },
    ehDiaUtil(dataAtual) {
      const diaAtual = dataAtual.getDay()
      const ehFimDeSemana = [0, 6].includes(diaAtual)

      const feriadosNacionais = ['01/01', '21/04', '01/05', '07/09', '12/10', '02/11', '15/11', '25/12']
      const dataFormatada = this.formataData(dataAtual)
      const ehFeriado = feriadosNacionais.includes(dataFormatada)

      const ehDiaNaoTrabalhado = this.diasNaoTrabalhados.includes(dataFormatada)

      return !ehFimDeSemana && !ehFeriado && !ehDiaNaoTrabalhado
    },
    corPorReputacao(reputacao) {
      switch (reputacao) {
        case 'RUIM':
          return 'error'
        case 'REGULAR':
          return 'amber'
        case 'EXCELENTE':
          return 'success'
        case 'MELHOR_FABRICANTE':
          return 'primary'
        default:
          return
      }
    },
    limpaTexto(texto) {
      const textoLimpo = texto?.replace(/_/g, ' ')

      return textoLimpo
    },
    conversorTipoPonto(tipoPonto) {
      switch (tipoPonto) {
        case 'PP':
          return 'Ponto de Retirada'
        case 'PM':
          return 'Entregador'
      }
    },
    formatadorTelefone(telefone) {
      const telefoneFormatado = formataTelefone(telefone)

      return telefoneFormatado
    },
    gerirModalQrCode(qrCode = null) {
      this.abirModalQrCode = !!qrCode
      this.qrCode = qrCode
    },
    /**
     * Função para verificar se o horário1 é maior que o horário2
     * @param {string} horario1
     * @param {string} horario2
     * @returns {boolean}
     */
    horarioEhMaiorQue(horario1, horario2) {
      if (!horario1 || !horario2) {
        return false
      }

      const [hora1, minuto1] = horario1?.split(':').map(Number)
      const [hora2, minuto2] = horario2?.split(':').map(Number)
      const totalMinutos1 = hora1 * 60 + minuto1
      const totalMinutos2 = hora2 * 60 + minuto2

      return totalMinutos1 > totalMinutos2
    },
    consultaHoraCerta() {
      setInterval(() => {
        const data = new Date()
        const diaDaSemana = this.diasDaSemana[data.getDay()]
        const hora = data.getHours()
        const minutos = (data.getMinutes() < 10 ? '0' : '') + data.getMinutes()

        this.diaAtual = diaDaSemana
        this.horaAtual = `${hora}:${minutos}`
      }, 3000)
    },
    async buscaDiasNaoTrabalhados() {
      try {
        this.carregandoHorarios = true
        const resposta = await api.get('api_administracao/configuracoes/dia_nao_trabalhado')

        this.diasNaoTrabalhados = resposta.data.map((dia) => dia.data.replace(/(\/[0-9]{4})/g, ''))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar dias não trabalhados')
      } finally {
        this.carregandoHorarios = false
      }
    },
    async buscaHorariosSeparacaoFulfillment() {
      try {
        this.carregandoHorarios = true
        const resposta = await api.get('api_administracao/configuracoes/busca_horarios_separacao')
        this.horariosSeparacao = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar horários de separação')
      } finally {
        this.carregandoHorarios = false
      }
    },
    async buscarPrevisao() {
      if (!this.pesquisaProduto?.length || !this.pesquisaTransportador?.length || this.carregandoPesquisa) {
        return
      }

      try {
        this.informacoesPontoColeta = null
        this.carregandoPesquisa = true
        this.cidades = []
        this.FULFILLMENT_headers = this.headersPadrao('FULFILLMENT')
        this.EXTERNO_headers = this.headersPadrao('EXTERNO')

        const parametros = new URLSearchParams({
          id_colaborador: this.pesquisaTransportador,
          id_produto: this.pesquisaProduto,
        })

        const resposta = await api.get(`api_administracao/produtos/busca_previsao?${parametros}`)

        const consulta = resposta.data
        if (consulta.transportador.tipo_ponto === 'PP') {
          const FULFILLMENT_index = this.FULFILLMENT_headers.findIndex((item) => item.value === 'dias_entregar_cliente')
          this.FULFILLMENT_headers.splice(FULFILLMENT_index, 1)

          const EXTERNO_index = this.EXTERNO_headers.findIndex((item) => item.value === 'dias_entregar_cliente')
          this.EXTERNO_headers.splice(EXTERNO_index, 1)
        }

        this.informacoesProduto = consulta.produto
        this.informacoesFornecedor = consulta.fornecedor
        this.informacoesPontoColeta = consulta.ponto_coleta
        this.informacoesTransportador = consulta.transportador
        this.cidades = consulta.transportador.cidades
        this.cidadeSelecionada = this.cidades[0]
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar previsão de entrega')
      } finally {
        this.carregandoPesquisa = false
      }
    },
  },
  mounted() {
    this.consultaHoraCerta()
    const requisicoes = []
    requisicoes.push(this.buscaDiasNaoTrabalhados())
    requisicoes.push(this.buscaHorariosSeparacaoFulfillment())
    Promise.all(requisicoes)
  },
  watch: {
    pesquisaProduto() {
      this.debounce(() => this.buscarPrevisao(), 750)
    },
    pesquisaTransportador() {
      this.debounce(() => this.buscarPrevisao(), 750)
    },
  },
})
