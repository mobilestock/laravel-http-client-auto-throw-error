import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#paineisImpressaoVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      paineis: '',
      editando: false,
      input: '',
      snackbar: {
        mostrar: false,
        cor: '',
        texto: ''
      },
      carregando: false
    }
  },
  methods: {
    enqueueSnackbar(texto, cor = 'error') {
      this.snackbar.texto = texto
      this.snackbar.cor = cor
      this.snackbar.mostrar = true
    },
    async buscaPaineis() {
      this.carregando = true
      const response = await api.get('api_administracao/configuracoes/paineis_impressao')
      this.paineis = response.data.join(', ')
      this.carregando = false
    },
    iniciaEdicao() {
      this.input = this.paineis
      this.editando = true
    },
    async salvaPaineis() {
      try {
        this.carregando = true
        await api.put('api_administracao/configuracoes/paineis_impressao', { paineis_impressao: this.input.split(',').map((item) => parseInt(item)) })
        this.buscaPaineis()
        this.editando = false
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao salvar painéis de impressão')
      } finally {
        this.carregando = false
      }
    },
  },
  mounted() {
    this.buscaPaineis()
  }
})
