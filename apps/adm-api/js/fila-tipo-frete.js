import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

Vue.component('l-map', window.Vue2Leaflet.LMap)
Vue.component('l-tile-layer', window.Vue2Leaflet.LTileLayer)
Vue.component('l-marker', window.Vue2Leaflet.LMarker)
Vue.component('l-circle', window.Vue2Leaflet.LCircle)
Vue.component('l-polygon', window.Vue2Leaflet.LPolygon)
Vue.component('l-tooltip', window.Vue2Leaflet.LTooltip)
Vue.component('l-icon', window.Vue2Leaflet.LIcon)

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
      loading: false,
      loadingDocumentos: false,
      loadingCidades: false,
      timeoutAtualizarDados: null,
      listaDePontos: [],
      headersPontos: [
        this.criarHeader('ID', 'id_colaborador', false),
        this.criarHeader('Nome', 'razao_social', false),
        this.criarHeader('Telefone', 'telefone', false),
        this.criarHeader('Cidade', 'cidade', false),
        this.criarHeader('Endereço', 'endereco', false),
        this.criarHeader('Mapa', 'raios'),
        this.criarHeader('Street View', 'street_view'),
        this.criarHeader('Documentos', 'documentos'),
        this.criarHeader('Gerir', 'gestao'),
      ],
      headersEntregadores: [
        this.criarHeader('ID', 'id_colaborador', false),
        this.criarHeader('Nome', 'razao_social', false),
        this.criarHeader('Telefone', 'telefone'),
        this.criarHeader('Cidade', 'cidade', false),
        this.criarHeader('Cobertura', 'cidades'),
        this.criarHeader('Documentos', 'documentos'),
        this.criarHeader('Gerir', 'gestao'),
      ],
      headersCidades: [
        this.criarHeader('Cidade', 'cidade', false),
        this.criarHeader('Raio', 'raio'),
        this.criarHeader('Tarifa', 'valor'),
        this.criarHeader('Prazo Forçar Entrega', 'prazo_forcar_entrega'),
        this.criarHeader('Ativado', 'esta_ativo'),
        this.criarHeader('Elegível', 'eh_elegivel'),
      ],
      visualizacao: 'ELEGIVEIS',
      tipoFrete: 'ENTREGADORES',
      pesquisa: '',
      modalContato: {
        mostrar: false,
        qrCode: '',
        link: '',
      },
      modalDocumentos: {
        mostrar: false,
        documentos: [],
      },
      modalMapa: {
        mostrar: false,
        zoom: 11,
        limites: null,
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        center: [0, 0],
        options: {
          zoomControl: false,
          doubleClickZoom: false,
        },
        dados: null,
        selecionado: null,
      },
      modalAprovacao: {
        mostrar: false,
        dados: null,
      },
      modalCidades: {
        mostrar: false,
        dados: null,
      },
      snackbar: {
        mostrar: false,
        cor: '',
        texto: '',
      },
    }
  },

  methods: {
    criarHeader(titulo, chave, centralizar = true) {
      return {
        text: titulo,
        value: chave,
        align: centralizar ? 'center' : 'left',
      }
    },
    retornaCorRaio(raio) {
      switch (true) {
        case raio.eh_colaborador_atual:
          return 'red'
        case raio.tipo_ponto === 'PP':
          return 'orange'
        case raio.tipo_ponto === 'PM':
          return 'blue'
        default:
          return 'green'
      }
    },
    buscaListaDePontos() {
      this.loading = true
      const parametros = new URLSearchParams({
        visualizacao: this.visualizacao,
        tipo_frete: this.tipoFrete,
      })
      api
        .get(`api_administracao/tipo_frete/fila_aprovacao?${parametros}`)
        .then((json) => {
          this.listaDePontos = json.data
        })
        .catch((error) => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'error'
          this.snackbar.texto = error?.response?.data?.message || error?.message || 'Erro ao buscar lista de pontos'
        })
        .finally(() => (this.loading = false))
    },
    buscarDocumentos(ponto) {
      this.loadingDocumentos = true
      const parametros = new URLSearchParams({
        ponto_parado: (ponto.tipo_ponto === 'PP').toString(),
      })
      MobileStockApi(`api_administracao/entregadores/documentos/${ponto.id_colaborador}?${parametros}`)
        .then((response) => response.json())
        .then((json) => {
          if (!json.status) throw json.message
          this.modalDocumentos.documentos = Object.values(json.data)
          this.modalDocumentos.mostrar = true
        })
        .catch((error) => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'error'
          this.snackbar.texto = error
        })
        .finally(() => (this.loadingDocumentos = false))
    },
    buscarMapaCidade(ponto, cidadeSelecionada = null) {
      this.loading = true
      const idCidade = cidadeSelecionada ? cidadeSelecionada.id_cidade : ponto.id_cidade
      api
        .get(`api_administracao/cidades/${idCidade}/cobertura/${ponto.id_colaborador}`)
        .then((json) => {
          const raios = json.data
          this.modalMapa.center = cidadeSelecionada
            ? [cidadeSelecionada.latitude, cidadeSelecionada.longitude]
            : [ponto.localizacao.latitude, ponto.localizacao.longitude]
          this.modalMapa.selecionado = cidadeSelecionada
            ? {
                ...ponto,
                cidade: cidadeSelecionada.cidade,
                localizacao: cidadeSelecionada,
              }
            : ponto
          this.modalMapa.dados = raios
          this.modalMapa.mostrar = true

          try {
            const dadosCidade = (cidadeSelecionada || ponto).cidade.split(' - ')
            const params = new URLSearchParams({
              country: 'brazil',
              city: dadosCidade[0],
              state: dadosCidade[1],
              polygon_geojson: 1,
              format: 'jsonv2',
            }).toString()
            fetch('https://nominatim.openstreetmap.org/search.php?' + params)
              .then((res) => res.json())
              .then((json) => {
                const boundary = json
                  .find((item) => item.category === 'boundary')
                  .geojson.coordinates[0].map((item) => [item[1], item[0]])
                this.modalMapa.limites = boundary
              })
          } catch (error) {
            this.snackbar.mostrar = true
            this.snackbar.cor = 'error'
            this.snackbar.texto = 'Erro ao buscar limites do município'
            console.error(error)
          }

          setTimeout(() => {
            window.dispatchEvent(new Event('resize'))
          }, 250)
        })
        .catch((error) => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'error'
          this.snackbar.texto = error
        })
        .finally(() => (this.loading = false))
    },
    async atualizarSituacao(ponto, situacao) {
      if (this.loading) {
        return
      }

      try {
        this.loading = true
        await api.post('api_administracao/tipo_frete/atualiza_situacao_ponto', {
          id_usuario_ponto: ponto.id_usuario,
          id_colaborador_ponto: ponto.id_colaborador,
          tipo_ponto: ponto.tipo_ponto,
          telefone: ponto.telefone,
          situacao: situacao,
        })

        this.snackbar.mostrar = true
        this.snackbar.cor = 'success'
        this.snackbar.texto = `Ponto ${situacao?.toLowerCase()} com sucesso!`
        this.buscaListaDePontos()
      } catch (error) {
        this.snackbar.mostrar = true
        this.snackbar.cor = 'error'
        this.snackbar.texto = error?.response?.data?.message || error?.message || 'Erro ao atualizar situação do ponto'
      } finally {
        this.loading = false
      }
    },
    atualizarStatusCidade(cidade) {
      this.loadingCidades = true
      api
        .patch(`api_administracao/entregadores/atualizar_status_raio/${cidade.id_raio}`)
        .then(() => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'success'
          this.snackbar.texto = 'Raio atualizado com sucesso'
        })
        .catch((error) => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'error'
          this.snackbar.texto = error?.response?.data?.message || error?.message || 'Erro ao atualizar raio'
        })
        .finally(() => (this.loadingCidades = false))
    },
    atualizaDadosCidade(cidade) {
      this.loadingCidades = true
      api
        .post('api_administracao/entregadores/alterar_dados_raio', cidade)
        .then(() => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'success'
          this.snackbar.texto = 'Dados atualizados com sucesso'
        })
        .catch((error) => {
          this.snackbar.mostrar = true
          this.snackbar.cor = 'error'
          this.snackbar.texto = error?.response?.data?.message || error?.message || 'Erro ao atualizar dados da cidade'
        })
        .finally(() => (this.loadingCidades = false))
    },
    debounceSalvarNovosDados(dados) {
      clearTimeout(this.timeoutAtualizarDados)
      this.timeoutAtualizarDados = setTimeout(() => this.atualizaDadosCidade(dados), 800)
    },
    streetView(cidade) {
      const parametros = new URLSearchParams({
        query: `${cidade.endereco} ${cidade.cidade}`,
      }).toString()
      window.open('https://www.google.com/maps/search/?api=1&' + parametros, '_blank')
    },
    gerirModalContato(ponto = null) {
      const modalContato = {
        mostrar: !!ponto,
        qrCode: '',
        link: '',
      }
      if (ponto) {
        const tipoPonto = ponto.tipo_ponto === 'PP' ? 'Ponto de Retirada' : 'Entregador'
        const mensagem = `Olá, ${ponto.razao_social}, estamos entrando em contato pois você se cadastrou como um ${tipoPonto} do meulook!`

        modalContato.qrCode = encodeURIComponent(
          new MensagensWhatsApp({ mensagem, telefone: String(ponto.telefone) }).resultado,
        )
        modalContato.link = new MensagensWhatsApp({
          mensagem: encodeURIComponent(mensagem),
          telefone: String(ponto.telefone),
        }).resultado
      }
      this.modalContato = modalContato
    },
    gerirModalCidadesEntregadores(ponto) {
      this.modalCidades = {
        mostrar: true,
        dados: ponto,
      }
    },
  },

  watch: {
    visualizacao() {
      this.buscaListaDePontos()
    },
    tipoFrete() {
      this.buscaListaDePontos()
    },
  },

  mounted() {
    this.buscaListaDePontos()
  },
})
