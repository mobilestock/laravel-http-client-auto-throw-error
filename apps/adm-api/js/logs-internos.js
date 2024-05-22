import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'
import * as jsondiffpatch from 'https://esm.sh/jsondiffpatch@0.6.0'
import * as htmlFormatter from 'https://esm.sh/jsondiffpatch@0.6.0/formatters/html'

new Vue({
  el: '#logs-manuais',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      select: '',
      from: '',
      where: '',
      carregando: false,
      formularioInputsRegras: [(v) => !!v || 'Campo obrigatório'],
      itens: [],
      snack: {
        mostrar: false,
        mensagem: '',
      },
      cabecalhos: [
        this.itemHeaderDataTable('Id', 'id', 'left'),
        this.itemHeaderDataTable('Data criação', 'data_criacao', 'center'),
        this.itemHeaderDataTable('Json', 'dados', 'center'),
        this.itemHeaderDataTable('Ações', 'acao', 'center'),
      ],
      logSelecionado: null,
      autocomplete: {
        select: [],
        from: [],
        where: [],
      },
    }
  },
  computed: {
    botaoProximoDesabilitado() {
      return (
        this.itens.length === 0 ||
        this.itens.findIndex((item) => item.id === this.logSelecionado?.id) === this.itens.length - 1
      )
    },
    botaoAnteriorDesabilitado() {
      return this.itens.length === 0 || this.itens.findIndex((item) => item.id === this.logSelecionado?.id) === 0
    },
  },
  methods: {
    async consultar() {
      if (this.carregando) return
      try {
        this.carregando = true
        if (!this.$refs.formularioReferencia.validate()) {
          return
        }
        this.autocomplete.select = [...new Set(this.autocomplete.select), this.select]
        this.autocomplete.from = [...new Set(this.autocomplete.from), this.from]
        this.autocomplete.where = [...new Set(this.autocomplete.where), this.where]
        localStorage.setItem('logs-manuais-autocomplete', JSON.stringify(this.autocomplete))
        const parametros = new URLSearchParams()
        parametros.append('select', this.select)
        parametros.append('from', this.from)
        parametros.append('where', this.where)
        const resposta = await api.get(`api_administracao/logs?${parametros}`)
        this.itens = resposta.data.map((item) => ({ ...item, acao: item.id }))
      } catch (error) {
        this.snack.mostrar = true
        this.snack.mensagem = error.response.data.message || error.message
      } finally {
        this.carregando = false
      }
    },
    abrirDialogCompararJsons(index) {
      this.logSelecionado = this.itens[index]
      const jsonNovo = this.itens[index].dados
      const jsonAntigo = index + 1 < this.itens.length ? this.itens[index + 1].dados : {}
      const delta = jsondiffpatch.diff(jsonAntigo, jsonNovo)
      let conteudo = 'Não há diferença entre os JSONs'
      if (JSON.stringify(jsonNovo) !== JSON.stringify(jsonAntigo)) {
        conteudo = htmlFormatter.format(delta, jsonNovo)
      }
      document.getElementById('visual').innerHTML = conteudo
    },
    copiarDados(texto) {
      // @issue: https://github.com/mobilestock/web/issues/3177
      if (navigator.clipboard) {
        navigator.clipboard.writeText(texto)
      } else {
        let campoCopiar = document.getElementById('input-copiar')
        campoCopiar.value = texto

        campoCopiar.style.top = '0'
        campoCopiar.style.left = '0'
        campoCopiar.style.position = 'fixed'

        campoCopiar.focus()
        campoCopiar.select()

        document.execCommand('copy')
      }

      this.snack.mensagem = 'Copiado para a área de transferência!'
      this.snack.mostrar = true
    },
    alternarLogSelecionado(direcao = 1) {
      const index = this.itens.findIndex((item) => item.id === this.logSelecionado.id)
      if (index + direcao < 0 || index + direcao >= this.itens.length) return
      this.abrirDialogCompararJsons(index + direcao)
    },
    itemHeaderDataTable(label, valor, alinhamento) {
      return {
        text: label,
        value: valor,
        align: alinhamento,
        sortable: false,
      }
    },
    fecharDialog() {
      this.logSelecionado = null
    },
  },
  mounted() {
    const localStorageAutocomplete = localStorage.getItem('logs-manuais-autocomplete')
    this.autocomplete = localStorageAutocomplete ? JSON.parse(localStorageAutocomplete) : this.autocomplete
  },
})
