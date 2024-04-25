import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var app = new Vue({
  el: '#menuFraudesDevolucoes',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      pesquisa: '',

      colaborador_alterar: null,
      telefone_modal: null,

      carregando: false,

      colaboradores_suspeitos: [],
      situacoes_fraude: [
        {
          text: 'Pendente de Fraude',
          value: 'PE',
        },
        {
          text: 'Liberado temporariamente (até que o valor de devoluções seja atingido)',
          value: 'LT',
        },
      ],
      cabecalho: [
        this.itemGrade('Id', 'id'),
        this.itemGrade('Data do Cadastro', 'data_cadastro', true),
        this.itemGrade('Nome', 'razao_social'),
        this.itemGrade('Telefone', 'telefone'),
        this.itemGrade('Cidade', 'cidade'),
        this.itemGrade('Saldo', 'saldo_cliente'),
        this.itemGrade('Valor Devoluções + Saldo Bloqueado Cliente', 'valor_devolucoes_cliente'),
        this.itemGrade('Valor Devoluções Ponto', 'valor_devolucoes_ponto'),
        this.itemGrade('Limite', 'limite'),
        this.itemGrade('Situação', 'situacao'),
      ],

      snackbar: {
        cor: 'error',
        texto: '',
        open: false,
      },
    }
  },

  methods: {
    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        alignn: 'start',
        sortable: ordenavel,
        value: valor,
      }
    },

    ativaSnackbar(mensagem, cor = 'error') {
      this.snackbar.cor = cor
      this.snackbar.texto = mensagem || 'Ocorreu um erro! Contate a equipe de desenvolvimento.'
      this.snackbar.open = true
      this.carregando = false
    },

    mudaSituacaoFraude(val, item) {
      this.colaborador_alterar = {
        id: item.id,
        situacao: val,
      }
    },

    async buscaColaboradoresFraudulentos() {
      try {
        this.carregando = true
        const resposta = await api.get('api_administracao/fraudes/devolucoes')
        this.colaboradores_suspeitos = resposta.data?.map((colaborador) => ({
          ...colaborador,
          telefone: formataTelefone(colaborador.telefone),
          valor_devolucoes_cliente: formataMoeda(colaborador.valor_devolucoes_cliente),
          valor_devolucoes_ponto: formataMoeda(colaborador.valor_devolucoes_ponto),
          saldo_cliente: formataMoeda(colaborador.saldo_cliente),
          limite: formataMoeda(colaborador.limite),
        }))
      } catch (error) {
        this.ativaSnackbar(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro! Contate a equipe de T.I.',
        )
      } finally {
        this.carregando = false
      }
    },

    async confirmaMudancaSituacaoFraude() {
      this.carregando = true
      try {
        await api.put(`api_administracao/fraudes/${this.colaborador_alterar.id}`, {
          situacao: this.colaborador_alterar.situacao,
          origem: 'DEVOLUCAO',
        })
        this.colaborador_alterar = null
        this.ativaSnackbar('Situação alterada com sucesso!', 'success')
      } catch (error) {
        this.ativaSnackbar(error?.response?.data?.message || error?.message || error)
      } finally {
        this.carregando = false
      }
    },

    async alteraValorMinimo(id_colaborador, value, valor_atual) {
      this.carregando = true
      let valor = parseFloat(value.target[0].value.replace('R$', ''))

      if (valor === parseFloat(valor_atual)) return this.ativaSnackbar('O valor informado é igual ao valor atual.')
      if (valor === 0) return this.ativaSnackbar('O valor informado não pode ser 0.')

      const resposta = await MobileStockApi(`api_administracao/fraudes/altera_valor_limite_para_entrar_fraude`, {
        method: 'PUT',
        body:
          JSON.stringify({
            id_colaborador: id_colaborador,
            valor: valor,
          }) || [],
      })
      if (!resposta.status || resposta.status === 400) return this.ativaSnackbar(resposta.message)
      this.carregando = false
      this.ativaSnackbar('Valor alterado com sucesso!', 'success')
      this.buscaColaboradoresFraudulentos()
    },
  },

  computed: {
    qrcodeCliente() {
      if (!this.telefone_modal?.telefone) return ''

      const mensagem = new MensagensWhatsApp({
        mensagem: '',
        telefone: this.telefone_modal.telefone,
      }).resultado

      return `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(mensagem)}`
    },
  },

  async mounted() {
    await this.buscaColaboradoresFraudulentos()
  },
})
