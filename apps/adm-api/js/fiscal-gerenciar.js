import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'
var app = new Vue({
  el: '#fiscal',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      modalDados: false,
      modalDadosCliente: false,
      loadingTabela: false,
      loadingTabelaFinalizadas: false,
      vazio: true,
      snackbar: false,
      listaFinalizadas: false,
      modalAlterar: false,
      idCliente: 0,
      cnpj: '',
      notaFiscal: '',
      textoSnackbar: '',
      finalizadas: [],
      entregas: [],
      cliente: [],
      listaTransportadoras: [],
      dadosModal: [],
      dadosModalAlterar: [],
      cnpj: '25252339000106',
      cabecalho: [
        this.itemGrade('Data início', 'data_inicio'),
        this.itemGrade('Entrega', 'id_entrega'),
        this.itemGrade('Cliente', 'cliente'),
        this.itemGrade('Cidade', 'cidade'),
        this.itemGrade('Ult. Transportadora', 'ultima_transportadora'),
        this.itemGrade('Whatsapp', 'telefone'),
        this.itemGrade('Dados', 'id_cliente'),
      ],
      cabecalhoFinalizadas: [
        this.itemGrade('Data início', 'data_criacao'),
        this.itemGrade('Entrega', 'id_entrega'),
        this.itemGrade('Transportadora', 'transportadora'),
        this.itemGrade('CNPJ', 'cnpj'),
        this.itemGrade('Nota Fiscal', 'nota_fiscal'),
        this.itemGrade('Alterar', 'alterar'),
      ],
    }
  },
  methods: {
    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        align: 'start',
        sortable: ordenavel,
        value: valor,
      }
    },

    verFinalizadas() {
      this.listaFinalizadas = true
      this.finalizadas = []
      this.loadingTabelaFinalizadas = true
      return MobileStockApi('api_administracao/transportadoras/finalizadas')
        .then((resp) => resp.json())
        .then((json) => {
          this.finalizadas = json.data
          this.loadingTabelaFinalizadas = false
        })
    },

    buscaEntregasPendentes() {
      this.entregas = []
      this.loadingTabela = true
      api
        .get('api_administracao/transportadoras/entregas_pendentes')
        .then((json) => (this.entregas = json.data))
        .catch((error) => {
          this.snackbar = true
          this.vazio = true
          this.textoSnackbar = error?.response?.data?.message || error?.message || 'Erro ao buscar entregas pendentes'
        })
        .finally(() => (this.loadingTabela = false))
    },

    buscaDadosClientes(idCliente) {
      this.cliente = []
      api.get(`api_administracao/cadastro/busca/colaboradores/${idCliente}`).then((json) => (this.cliente = json.data))
    },

    abreModalDados(item) {
      this.dadosModal = item
      this.modalDados = true
      this.buscaDadosClientes(item.id_cliente)
    },

    abreModalAlterar(item) {
      this.dadosModalAlterar = item
      this.modalAlterar = true
    },

    async buscaTransportadora() {
      return MobileStockApi('api_administracao/transportadoras/busca/transportadoras')
        .then((resp) => resp.json())
        .then((json) => {
          this.listaTransportadoras = json.data
        })
    },

    async insereDadosRastreio(e) {
      this.id_entrega = e.target[0].value
      this.id_transportadora = e.target[4].value
      this.cnpj = e.target[5].value
      this.notaFiscal = e.target[6].value
      if (this.notaFiscal == '') {
        this.vazio = true
        this.textoSnackbar = 'Insira a nota fiscal!'
      } else if (this.cnpj == '') {
        this.vazio = true
        this.textoSnackbar = 'Insira o CNPJ!'
      } else if (this.id_transportadora == '') {
        this.vazio = true
        this.textoSnackbar = 'Escolha uma transportadora!'
      } else {
        await MobileStockApi('api_administracao/transportadoras/rastreio', {
          method: 'POST',
          body: JSON.stringify({
            id_entrega: this.id_entrega,
            cpf_cnpj: this.cnpj,
            numero_nota_fiscal: this.notaFiscal,
            id_transportadora: this.id_transportadora,
          }),
        })
        this.vazio = false
        this.textoSnackbar = 'Sucesso!'
        location.reload()
      }
      this.snackbar = true
    },

    async alteraDadosRastreio(e) {
      this.id_entrega = e.target[0].value
      this.id_transportadora = e.target[2].value
      this.cnpj = e.target[3].value
      this.notaFiscal = e.target[4].value
      if (this.notaFiscal == '') {
        this.vazio = true
        this.textoSnackbar = 'Insira a nota fiscal!'
      } else if (this.cnpj == '') {
        this.vazio = true
        this.textoSnackbar = 'Insira o CNPJ!'
      } else if (this.id_transportadora == '') {
        this.vazio = true
        this.textoSnackbar = 'Escolha uma transportadora!'
      } else {
        await MobileStockApi('api_administracao/transportadoras/rastreio/alterar', {
          method: 'POST',
          body: JSON.stringify({
            id_entrega: this.id_entrega,
            id_transportadora: this.id_transportadora,
            cpf_cnpj: this.cnpj,
            numero_nota_fiscal: this.notaFiscal,
          }),
        })
        this.modalAlterar = false
        this.vazio = false
        this.textoSnackbar = 'Sucesso!'
      }
      this.snackbar = true
    },

    mensagemWhatsapp(item) {
      const mensagemWhatsApp = `Olá, estamos aguardando a nota fiscal referente a entrega ${item.id_entrega}`
      const mensagem = new MensagensWhatsApp({
        mensagem: mensagemWhatsApp,
        telefone: item.telefone,
      }).resultado

      window.open(mensagem, '_blank')
    },
  },

  async mounted() {
    this.buscaEntregasPendentes()
    await this.buscaTransportadora()
  },
})
