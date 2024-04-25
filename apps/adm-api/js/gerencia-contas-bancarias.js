import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

const contasBancariaColaboradores = new Vue({
  el: '#contasBancariaColaboradores',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      isLoading: false,
      modalEditaDados: false,
      filtro: '',
      nomeAlterado: '',
      codigoAlterado: '',
      agenciaAlterada: '',
      contaAlterada: '',
      nomeTitular: '',
      listaContas: [],
      headerDados: [
        this.itemGrades('ID', 'id'),
        this.itemGrades('CPF/CNPJ', 'cpf_titular'),
        this.itemGrades('Titular', 'nome_titular'),
        this.itemGrades('Banco - Código', 'id_banco'),
        this.itemGrades('Agência', 'agencia'),
        this.itemGrades('Conta', 'conta'),
        this.itemGrades('Editar Dados', 'editar'),
      ],
      snackbar: {
        ativar: false,
        texto: '',
      },
    }
  },
  methods: {
    itemGrades(campo, valor, ordernavel = false, classe = 'text-light black darken-3') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        class: classe,
        align: 'center',
      }
    },

    async buscaDadosBancarios() {
      try {
        this.isLoading = true

        const resp = await api.get('api_administracao/contas_bancarias')
        this.listaContas = resp.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message)
      } finally {
        this.isLoading = false
        this.limparDados()
      }
    },

    async editaDados() {
      try {
        this.isLoading = true

        await api.put(`api_administracao/contas_bancarias/${this.idConta}`, {
          cod_alterado: this.codigoAlterado,
          agencia_alterada: this.agenciaAlterada,
          conta_alterada: this.contaAlterada,
          nome_alterado: this.nomeAlterado,
        })
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message)
      } finally {
        this.isLoading = false
        this.modalEditaDados = false
        this.limparDados()
        this.buscaDadosBancarios()
      }
    },

    cpfCnpjFormatado(cnpjCpf) {
      const quantidade = cnpjCpf

      if (quantidade.length === 11) {
        return formataCpf(cnpjCpf)
      } else {
        return formataCnpj(cnpjCpf)
      }
    },

    abrirModalDados(item) {
      this.modalEditaDados = true
      this.idConta = item.id
      this.conta = item.conta
      this.contaAlterada = item.conta
      this.agencia = item.agencia
      this.agenciaAlterada = item.agencia
      this.codigo = item.id_banco
      this.codigoAlterado = item.id_banco
      this.nomeAlterado = item.nome_titular
    },

    limparDados() {
      this.codigoAlterado = ''
      this.agenciaAlterada = ''
      this.contaAlterada = ''
      this.nomeAlterado = ''
    },

    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error', tempo = 5000) {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
        tempo: tempo,
      }
    },
  },

  mounted() {
    this.buscaDadosBancarios()
  },
})
