import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#monitoraPagamentoAutoVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      carregando: false,
      ativado: false,
      ativarModalRegras: false,
      valorTotalSaques: 0,
      snackbar: {
        ativar: false,
        cor: '',
        texto: '',
      },
      contemplados: [],
      headersContemplados: [
        this.itemGrades('ID', 'id'),
        this.itemGrades('ID Saque', 'id_saque'),
        this.itemGrades('Data Criação', 'data_criacao'),
        this.itemGrades('Quem Sacou', 'sacador'),
        this.itemGrades('Reputação', 'reputacao'),
        this.itemGrades('Cidade', 'endereco'),
        this.itemGrades('Quem Receberá', 'recebedor'),
        this.itemGrades('Valor Saque', 'valor_pagamento'),
      ],
      diasTransferenciaSeller: {},
    }
  },
  methods: {
    async buscaInformacoes() {
      try {
        this.carregando = true

        const resposta = await api.get('api_administracao/pagamento/informacoes_pagamento_automatico_transferencias')
        this.ativado = resposta.data.ativado
        this.contemplados = resposta.data.informacoes?.contemplados || []
        this.valorTotalSaques = resposta.data.informacoes?.valor_total_saques || 0
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar informações')
      } finally {
        this.carregando = false
      }
    },
    async alterarPagamentoAutomatico() {
      try {
        this.carregando = true

        await MobileStockApi('api_administracao/pagamento/alterar_pagamento_automatico_transferencias', {
          method: 'PUT',
          body: JSON.stringify({
            ativo: !this.ativado,
          }),
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw new Error(resp.message)

            this.ativado = resp.data
            this.enqueueSnackbar(resp.message, 'success')
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.carregando = false
      }
    },
    async atualizarFilaTransferenciaAutomatico() {
      try {
        this.carregando = true

        await MobileStockApi('api_administracao/pagamento/atualiza_fila_transferencia', {
          method: 'POST',
        })
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw new Error(resp.message)

            this.enqueueSnackbar('Fila atualizada com sucesso', 'success')
            setTimeout(() => document.location.reload(), 2500)
          })
      } catch (error) {
        this.enqueueSnackbar(error)
        this.carregando = false
      }
    },
    buscaDiasTransferenciaColaboradores() {
      try {
        this.carregando = true

        MobileStockApi('api_administracao/configuracoes/datas_transferencia_colaborador')
          .then((resp) => resp.json())
          .then((resp) => {
            if (!resp.status) throw new Error(resp.message)

            this.diasTransferenciaSeller = resp.data
          })
      } catch (error) {
        this.enqueueSnackbar(error)
        this.carregando = false
      }
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
    corPorOrigem(reputacao) {
      switch (reputacao) {
        case 'RUIM':
          return 'red lighten-5'
        case 'REGULAR':
          return 'amber lighten-5'
        case 'EXCELENTE':
          return 'green lighten-5'
        case 'MELHOR_FABRICANTE':
          return 'blue lighten-5'
        case 'ANTECIPACAO':
          return 'brown lighten-5'
        case 'ENTREGADOR':
          return 'deep-purple accent-1'
        default:
          return
      }
    },
    formataReputacao(reputacao) {
      return reputacao.replace(/_/, ' ')
    },
    formataValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        cor: cor,
        texto: texto,
      }
    },
  },
  mounted() {
    this.buscaInformacoes()
    this.buscaDiasTransferenciaColaboradores()
  },
})
