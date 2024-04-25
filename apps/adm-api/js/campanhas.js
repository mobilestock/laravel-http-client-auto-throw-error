import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#campanhas',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      carregando: true,
      campanhaAtiva: {
        id: null,
        url_pagina: '',
        url_imagem: null,
      },
      formulario: {
        url_pagina: '',
        url_imagem: null,
      },
      snack: {
        mensagem: '',
        ativo: false,
      },
      dialogConfirmarDesativarCampanha: false,
    }
  },
  computed: {
    inputCriarDisabled() {
      return this.carregando || this.formulario.url_pagina === '' || this.formulario.url_imagem === null
    },
  },
  watch: {
    'formulario.url_imagem': (novaImagemInput) => {
      if (!novaImagemInput) return
      const fileReader = new FileReader()
      fileReader.onload = (event) => {
        const url = event.target.result
        const definirParametrosImagem = (idElemento, urlImagem) => {
          const element = document.getElementById(idElemento)
          element.src = urlImagem
        }
        definirParametrosImagem('previsualizacao-iphonese', url)
        definirParametrosImagem('previsualizacao-iphonexr', url)
        definirParametrosImagem('previsualizacao-pc', url)
      }
      fileReader.readAsDataURL(novaImagemInput)
    },
    'campanhaAtiva.url_imagem': (novaImagemUrl) => {
      if (!novaImagemUrl) return
      const element = document.getElementById('previsualizacao-campanha-ativa')
      element.src = novaImagemUrl
    },
  },
  methods: {
    async buscarUltimaCampanha() {
      try {
        this.carregando = true
        const resposta = await api.get('api_cliente/campanhas')
        this.campanhaAtiva = resposta.data
      } catch (error) {
        this.onCatchError(error, 'Erro ao buscar campanha')
      } finally {
        this.carregando = false
      }
    },
    async criarCampanha() {
      try {
        this.carregando = true
        const form = new FormData()
        form.append('url_pagina', this.formulario.url_pagina)
        form.append('file_imagem', this.formulario.url_imagem)
        await api.post('api_administracao/campanhas', form)
        await this.buscarUltimaCampanha()
        this.formulario = {
          url_pagina: '',
          url_imagem: null,
        }
      } catch (error) {
        this.onCatchError(error, 'Erro ao criar campanha')
      } finally {
        this.carregando = false
      }
    },
    async desativarCampanha() {
      try {
        this.carregando = true
        await api.delete(`api_administracao/campanhas/${this.campanhaAtiva.id}`)
        this.campanhaAtiva = {
          id: null,
          url_pagina: null,
          url_imagem: null,
        }
        this.dialogConfirmarDesativarCampanha = false
      } catch (error) {
        this.onCatchError(error, 'Erro ao desativar campanha')
      } finally {
        this.carregando = false
      }
    },
    onCatchError(error, mensagemPadrao) {
      this.snack.mensagem = error.response?.data.message || mensagemPadrao
      this.snack.ativo = true
    },
  },
  mounted() {
    this.buscarUltimaCampanha()
  },
})
