import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

Vue.component('l-map', window.Vue2Leaflet.LMap)
Vue.component('l-tile-layer', window.Vue2Leaflet.LTileLayer)
Vue.component('l-tooltip', window.Vue2Leaflet.LTooltip)
Vue.component('l-marker', window.Vue2Leaflet.LMarker)
Vue.component('l-polygon', window.Vue2Leaflet.LPolygon)
Vue.component('l-circle', window.Vue2Leaflet.LCircle)
Vue.component('l-icon', window.Vue2Leaflet.LIcon)

new Vue({
  el: '#gerenciarPontosVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      areaAtual: {
        id: Number(sessionStorage.getItem('areaGerenciarPontos') || 0),
      },
      areasDisponiveis: [
        {
          nome: 'PONTO_RETIRADA',
          icone: 'mdi-city-variant',
        },
        {
          nome: 'ENTREGADORES',
          icone: 'fas fa-motorcycle',
        },
        {
          nome: 'PONTOS_COLETA',
          icone: 'mdi-office-building',
        },
      ],
      carregando: false,
      pesquisaCidade: '',
      pesquisaPontoColeta: '',
      tempoDebounce: null,
      modalContato: {
        mostrar: false,
        qrCode: '',
        link: '',
      },
      snackbar: {
        mostrar: false,
        cor: '',
        texto: '',
      },

      // PONTO_RETIRADA
      PONTO_RETIRADA_headers: [
        this.itemGrades('ID', 'id', true),
        this.itemGrades('Ponto', 'nome'),
        this.itemGrades('Razão Social', 'razao_social'),
        this.itemGrades('Cidade', 'cidade'),
        this.itemGrades('Bairro', 'bairro'),
        this.itemGrades('Endereço', 'endereco'),
        this.itemGrades('Nº', 'numero'),
        this.itemGrades('Cep', 'cep'),
        this.itemGrades('Contato', 'telefone'),
        this.itemGrades('Ativação Ponto de Coleta', 'eh_ponto_coleta', true),
        this.itemGrades('Ações', 'acoes'),
      ],
      PONTO_RETIRADA_pesquisa: '',
      PONTO_RETIRADA_lista: [],
      PONTO_RETIRADA_situacoes: [
        {
          text: 'Aprovado',
          value: 'ML',
        },
        {
          text: 'Pendente',
          value: 'PE',
        },
      ],
      PONTO_RETIRADA_BKP_alterarDados: JSON.stringify({}),
      PONTO_RETIRADA_alterarDados: {},

      // ENTREGADORES
      ENTREGADORES_headers: [
        this.itemGrades('ID', 'id', true),
        this.itemGrades('Razão Social', 'razao_social'),
        this.itemGrades('Cidade', 'local'),
        this.itemGrades('Cidades', 'cidades'),
        this.itemGrades('Documentos', 'foto_documento_habilitacao'),
        this.itemGrades('Contato', 'telefone'),
        this.itemGrades('Ponto de coleta', 'ponto_coleta', true),
        this.itemGrades('Ações', 'categoria', true),
        this.itemGrades('Ativação Ponto Coleta', 'eh_ponto_coleta', true),
      ],
      ENTREGADORES_headersCidades: [
        this.itemGrades('ID Raio', 'id_raio', true),
        this.itemGrades('Cidade', 'cidade'),
        this.itemGrades('Apelido Raio', 'apelido'),
        this.itemGrades('Ação', 'id_cidade'),
        this.itemGrades('Tarifa de Entrega', 'valor', false, '10rem'),
        this.itemGrades('Prazo Forçar Entrega', 'prazo_forcar_entrega', false, '10rem'),
        this.itemGrades('Entregar ao Cliente', 'dias_entregar_cliente', false, '10rem'),
        this.itemGrades('Margem de Erro', 'dias_margem_erro', false, '10rem'),
        this.itemGrades('Situação', 'esta_ativo'),
      ],
      ENTREGADORES_pesquisa: '',
      ENTREGADORES_lista: [],
      ENTREGADORES_listaCidades: [],
      ENTREGADORES_listaPontosColeta: [],
      ENTREGADORES_novaCidade: {},
      ENTREGADORES_modalConfirmacao: {},
      ENTREGADORES_documentos: {},
      ENTREGADORES_configCidades: {},
      ENTREGADORES_configPontoColeta: {},
      ENTREGADORES_configRaio: {
        mostrar: false,
        zoom: 11,
        limites: null,
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
          zoomControl: false,
          doubleClickZoom: false,
        },
        raios: [],
        raioSelecionado: 0,
        center: [0, 0],
      },
      ENTREGADORES_BKP_configRaio: JSON.stringify([]),

      // PONTOS_COLETA
      PONTOS_COLETA_headers: [
        this.itemGrades('ID', 'id', true),
        this.itemGrades('Tipo de Ponto', 'tipo_ponto', true),
        this.itemGrades('Ponto', 'nome'),
        this.itemGrades('Razão Social', 'razao_social'),
        this.itemGrades('Cidade', 'cidade'),
        this.itemGrades('Data Ativação', 'data_criacao'),
        this.itemGrades('Custo Frete', 'valor_custo_frete'),
        this.itemGrades('Porcentagem Acrescimo', 'porcentagem_frete', true),
        this.itemGrades('Contato', 'telefone'),
        this.itemGrades('Tarifa Envio', 'tarifa_envio'),
        this.itemGrades('Prazos', 'prazos'),
      ],
      PONTOS_COLETA_headersHorarios: [
        this.itemGrades('Dia Semana', 'dia', true, 'calc(100% / 4)', 'text-light grey darken-3'),
        this.itemGrades('Frequência', 'frequencia', true, 'calc(100% / 4)', 'text-light grey darken-3'),
        this.itemGrades('Horário Inicio', 'horario', true, 'calc(100% / 4)', 'text-light grey darken-3'),
        this.itemGrades('Remover Horário', 'remove', false, 'calc(100% / 4)', 'text-light grey darken-3'),
      ],
      PONTOS_COLETA_pesquisa: '',
      PONTOS_COLETA_lista: [],
      PONTOS_COLETA_novoHorario: {
        dia: null,
        frequencia: null,
        horario: null,
      },
      PONTOS_COLETA_seletores: {
        frequencias: [
          {
            text: 'Pontual',
            value: 'PONTUAL',
          },
          {
            text: 'Recorrente',
            value: 'RECORRENTE',
          },
        ],
        dias: [
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
        ],
        horarios: [],
      },
      PONTOS_COLETA_configValores: {},
      PONTOS_COLETA_BKP_configValores: JSON.stringify({}),
      PONTOS_COLETA_configurarAgenda: null,
      PONTOS_COLETA_modalAgendaHorarios: false,
      PONTOS_COLETA_modifiqueiValor: false,
      PONTOS_COLETA_modalFormulaTarifa: false,
    }
  },
  methods: {
    debounce(funcao, atraso) {
      clearTimeout(this.tempoDebounce)
      this.tempoDebounce = setTimeout(() => {
        funcao()
        this.tempoDebounce = null
      }, atraso)
    },
    pesquisarNaTabela(_valorColuna, pesquisa, valoresLinha) {
      const resultado = Object.values(valoresLinha).some(
        (campo) => campo && campo.toString().toLowerCase().includes(pesquisa.toLowerCase()),
      )

      return resultado
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        mostrar: true,
        cor: cor,
        texto: texto,
      }
    },
    itemGrades(campo, valor, ordernavel = false, largura = null, estilizacao = '') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        width: largura,
        align: 'center',
        class: estilizacao,
      }
    },
    mudarArea(idArea) {
      this.areaAtual = {
        id: idArea,
        ...this.areasDisponiveis[idArea],
      }

      this[`${this.areaAtual.nome}_busca`]()
    },
    formataNomeArea(nome) {
      return nome.replace('_', ' ')
    },
    converteValorEmReais(valor = 0) {
      const reais = valor.toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })

      return reais
    },
    gerirModalContato(ponto = null) {
      const modalContato = {
        mostrar: !!ponto,
        qrCode: '',
        link: '',
      }

      if (ponto) {
        const mensagem =
          this.areaAtual.nome === 'ENTREGADORES'
            ? `Olá, ${ponto.razao_social}, estamos entrando em contato pois você se cadastrou como um entregador do meulook!`
            : ''

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

    // PONTO_RETIRADA
    PONTO_RETIRADA_gerirDados(ponto = null) {
      let dados = null

      if (ponto) {
        dados = {
          dias_margem_erro: ponto.dias_margem_erro,
          categoria: ponto.categoria,
          cidade: ponto.cidade,
          email: ponto.email,
          emitirNota: ponto.emitir_nota_fiscal,
          horarioFuncionamento: ponto.horario,
          idCidade: ponto.id_cidade.toString(),
          prazoForcarEntrega: ponto.prazo_forcar_entrega,
          idColaborador: ponto.id_colaborador,
          idPonto: ponto.id,
          informacoes: ponto.mensagem,
          nome: ponto.nome,
          preco: ponto.preco_ponto,
          telefone: formataTelefone(ponto.telefone),
          uf: ponto.uf,
        }
      }

      this.PONTO_RETIRADA_alterarDados = {
        mostrar: !!ponto,
        ...dados,
      }

      if (ponto?.id_cidade) {
        this.buscaCidadePorNome(ponto.id_cidade, true)
      }
      this.PONTO_RETIRADA_BKP_alterarDados = JSON.stringify(this.PONTO_RETIRADA_alterarDados)
    },
    async PONTO_RETIRADA_salvarAlteracoes() {
      try {
        if (!this.PONTO_RETIRADA_houveAlteracoesDados || this.carregando) return

        this.carregando = true
        const dadosPonto = this.PONTO_RETIRADA_alterarDados
        await api.put('api_administracao/ponto_retirada', {
          dias_margem_erro: dadosPonto.dias_margem_erro,
          categoria: dadosPonto.categoria,
          cidade: dadosPonto.cidade,
          email: dadosPonto.email,
          emitir_nota_fiscal: dadosPonto.emitirNota,
          prazo_forcar_entrega: dadosPonto.prazoForcarEntrega,
          horario_de_funcionamento: dadosPonto.horarioFuncionamento,
          id_cidade: dadosPonto.idCidade,
          id_colaborador: dadosPonto.idColaborador,
          id_ponto: dadosPonto.idPonto,
          mensagem: dadosPonto.informacoes,
          nome: dadosPonto.nome,
          preco_ponto: dadosPonto.preco,
          telefone: dadosPonto.telefone,
          uf: dadosPonto.uf,
        })

        this.enqueueSnackbar('Informações alteradas com sucesso!', 'success')
        this.PONTO_RETIRADA_lista = []
        this.carregando = false
        this.PONTO_RETIRADA_gerirDados()
        await this.PONTO_RETIRADA_busca()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao alterar informações')
        this.carregando = false
      }
    },
    async PONTO_RETIRADA_busca() {
      try {
        if (this.PONTO_RETIRADA_lista.length || this.carregando) return

        this.carregando = true
        const response = await api.get('api_administracao/ponto_retirada/ativos')

        this.PONTO_RETIRADA_lista = response.data.map((ponto) => {
          ponto.cep = formataCep(ponto.cep)
          return ponto
        })
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar pontos de retirada')
      } finally {
        this.carregando = false
      }
    },

    // ENTREGADORES
    ENTREGADORES_houveAlteracoesRaio() {
      return JSON.stringify(this.ENTREGADORES_configRaio.raios) !== this.ENTREGADORES_BKP_configRaio
    },
    ENTREGADOR_retornaVisualRaio(raio, icon = false) {
      switch (true) {
        case raio.eh_colaborador_atual:
          return icon ? 'images/marker01.png' : 'red'
        case raio.tipo_ponto === 'PP':
          return icon ? 'images/icons/icon_ponto.png' : 'orange'
        case raio.tipo_ponto === 'PM':
          return icon ? 'images/icons/icon_entregador.png' : 'blue'
      }
    },
    ENTREGADORES_gerirModalCidades(entregador = null) {
      let dados = {
        idPonto: 0,
        idColaborador: 0,
        cidades: [],
      }

      if (entregador) {
        dados = {
          idPonto: entregador.id,
          idColaborador: entregador.id_colaborador,
          cidades: entregador.cidades,
        }
      }

      this.ENTREGADORES_configCidades = {
        mostrar: !!entregador,
        ...dados,
      }
    },
    ENTREGADORES_gerirModalAdicionarCidade(abrindo) {
      this.ENTREGADORES_novaCidade = {
        mostrar: abrindo,
        idCidade: 0,
        tarifaCidade: 0,
      }

      this.ENTREGADORES_configCidades.mostrar = !this.ENTREGADORES_configCidades.mostrar
    },
    ENTREGADORES_gerirModalConfirma(cidade, abrindo, ativando) {
      this.ENTREGADORES_modalConfirmacao = {
        mostrar: abrindo,
        cidade: cidade,
        ativando,
      }
      if (abrindo) {
        this.ENTREGADORES_configCidades.mostrar = !this.ENTREGADORES_configCidades.mostrar

        return
      }

      cidade.esta_ativo = !cidade.esta_ativo
      this.ENTREGADORES_configCidades.mostrar = true
    },
    ENTREGADORES_gerirModalConfigPontoColeta(entregador = null) {
      this.ENTREGADORES_configPontoColeta = {
        mostrar: !!entregador,
        idColaboradorEntregadorEditado: entregador?.id_colaborador || 0,
        razaoSocial: entregador?.razao_social || '',
      }
    },
    ENTREGADORES_modificaAlcanceRaio(aumentar) {
      let markerIndex
      const marker = this.ENTREGADORES_configRaio.raios.find((marcador, index) => {
        markerIndex = index
        return marcador.id_raio === this.ENTREGADORES_configRaio.raioSelecionado
      })
      if (markerIndex < 0) return
      this.ENTREGADORES_configRaio.raioSelecionado = 0
      if (aumentar) {
        this.ENTREGADORES_configRaio.raios[markerIndex] = {
          ...marker,
          raio: marker.raio + 250,
        }
      } else if (marker.raio > 250) {
        this.ENTREGADORES_configRaio.raios[markerIndex] = {
          ...marker,
          raio: (marker.raio -= 250),
        }
      }
      this.ENTREGADORES_configRaio.raioSelecionado = marker.id_raio
    },
    ENTREGADORES_modificaMarcador(evento, marker) {
      const markerIndex = this.ENTREGADORES_configRaio.raios.findIndex(
        (marcador) => marcador.id_raio === marker.id_raio,
      )
      this.ENTREGADORES_configRaio.raioSelecionado = 0
      this.ENTREGADORES_configRaio.raios[markerIndex] = {
        ...marker,
        latitude: evento.lat,
        longitude: evento.lng,
      }
      this.ENTREGADORES_configRaio.raioSelecionado = marker.id_raio
    },
    ENTREGADORES_alertaDadosFaltantes(item) {
      const faltaAlgo = item.cidades.some((cidade) =>
        [cidade.dias_margem_erro, cidade.dias_entregar_cliente].includes(null),
      )

      return faltaAlgo
    },
    ENTREGADORES_debounceSalvaNovaConfigCidade(novaConfig) {
      this.debounce(async () => await this.ENTREGADORES_atualizaDadosCidade(novaConfig), 500)
    },
    async ENTREGADORES_buscaCoberturaCidade(cidadeSelecionada = null) {
      if (!cidadeSelecionada) {
        return (this.ENTREGADORES_configRaio = {
          ...this.ENTREGADORES_configRaio,
          raioSelecionado: 0,
          mostrar: false,
          limites: null,
        })
      }
      try {
        this.carregando = true

        const resposta = await api.get(
          `api_administracao/cidades/${cidadeSelecionada.id_cidade}/cobertura/${cidadeSelecionada.id_colaborador}`,
        )

        const raios = resposta.data
        this.ENTREGADORES_configRaio.center = cidadeSelecionada
          ? [cidadeSelecionada.latitude, cidadeSelecionada.longitude]
          : [ponto.localizacao.latitude, ponto.localizacao.longitude]

        this.ENTREGADORES_configRaio.raios = raios
        this.ENTREGADORES_configRaio.mostrar = true

        this.ENTREGADORES_configCidades.mostrar = !this.ENTREGADORES_configCidades.mostrar

        try {
          const dadosCidade = cidadeSelecionada.cidade.split(' - ')
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
              this.ENTREGADORES_configRaio.limites = boundary
            })
        } catch (error) {
          this.enqueueSnackbar('Erro ao buscar limites do município')
          console.error(error)
        }

        setTimeout(() => {
          window.dispatchEvent(new Event('resize'))
        }, 250)

        this.ENTREGADORES_BKP_configRaio = JSON.stringify(this.ENTREGADORES_configRaio.raios)
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.carregando = false
      }
    },
    async ENTREGADORES_adicionarCidade() {
      try {
        this.carregando = true
        const cidadeSelecionada = this.ENTREGADORES_listaCidades.find(
          (cidade) => cidade.value === this.ENTREGADORES_novaCidade.idCidade,
        )
        if (!cidadeSelecionada) throw new Error('Selecione uma cidade')

        await api.post('api_administracao/entregadores/adicionar_cidade', {
          id_colaborador: this.ENTREGADORES_configCidades.idColaborador,
          id_cidade: cidadeSelecionada.value,
          cidade: cidadeSelecionada.text,
          valor: this.ENTREGADORES_novaCidade.tarifaCidade,
        })

        this.enqueueSnackbar('Cidade adicionada com sucesso!', 'success')
        window.location.reload()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao adicionar cidade')
        this.carregando = false
      }
    },
    async ENTREGADORES_atualizaDadosCidade(cidade) {
      this.carregando = true
      cidade.esta_ativo = !!cidade.esta_ativo
      try {
        await api.post('api_administracao/entregadores/alterar_dados_raio', cidade)

        this.enqueueSnackbar('Informações alteradas com sucesso!', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao alterar informações')
      } finally {
        this.carregando = false
      }
    },
    async ENTREGADORES_alterarSituacaoCidade() {
      try {
        this.carregando = true

        const cidadeAlterada = this.ENTREGADORES_modalConfirmacao.cidade
        await api.patch(`api_administracao/entregadores/atualizar_status_raio/${cidadeAlterada.id_raio}`)

        this.enqueueSnackbar('Raio atualizado com sucesso', 'success')
        window.location.reload()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar raio')
        this.carregando = false
      }
    },
    async ENTREGADORES_buscaDocumentos(entregador) {
      try {
        this.carregando = true

        const response = await MobileStockApi(
          `api_administracao/entregadores/documentos/${entregador['id_colaborador']}`,
        ).then((resp) => resp.json())
        if (!response.status) throw new Error(response.message)

        this.ENTREGADORES_documentos = {
          ...response.data,
          mostrar: true,
        }
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.carregando = false
      }
    },
    async ENTREGADORES_salvarPontoColeta() {
      try {
        this.carregando = true
        if (!this.ENTREGADORES_configPontoColeta.pontoColeta) {
          throw new Error('Selecione um ponto de coleta')
        }

        const idEditado = this.ENTREGADORES_configPontoColeta.idColaboradorEntregadorEditado
        const idPontoColeta = this.ENTREGADORES_configPontoColeta.pontoColeta
        const response = await MobileStockApi(
          `api_administracao/entregadores/${idEditado}/alterar_ponto_coleta/${idPontoColeta}`,
          {
            method: 'POST',
          },
        ).then((resp) => resp.json())
        if (!response.status) throw new Error(response.message)

        this.enqueueSnackbar(response.message, 'success')
        window.location.reload()
      } catch (error) {
        this.enqueueSnackbar(error)
        this.carregando = false
      }
    },
    async ENTREGADORES_mudarSituacao(entregador) {
      try {
        this.carregando = true

        await api.post('api_administracao/entregadores/muda_situacao', {
          id: entregador.id,
          id_usuario: entregador.id_usuario,
          situacao: entregador.categoria === 'PE' ? 'ML' : 'PE',
        })

        if (entregador.categoria === 'PE') {
          this.ENTREGADORES_gerirModalConfigPontoColeta(entregador)
        }

        this.enqueueSnackbar('Situação alterada com sucesso!', 'success')
        this.ENTREGADORES_lista = []
        this.carregando = false
        this.ENTREGADORES_busca()
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message ||
            error?.message ||
            'Erro ao alterar situação. Entre em contato com a equipe de TI.',
        )
        this.carregando = false
      }
    },
    async ENTREGADORES_editarDadosRaios() {
      try {
        this.carregando = true

        const dadosAnteriores = JSON.parse(this.ENTREGADORES_BKP_configRaio)
        const raiosAlterados = []
        this.ENTREGADORES_configRaio.raios.forEach((raio) => {
          const raioAnterior = dadosAnteriores.find((raioAnterior) => raioAnterior.id_raio === raio.id_raio)
          if (JSON.stringify(raioAnterior) !== JSON.stringify(raio)) {
            raiosAlterados.push(raio)
          }
        })

        await api.patch('api_administracao/entregadores/atualizar_raios', {
          raios: raiosAlterados,
        })

        this.enqueueSnackbar('Raios atualizados com sucesso!', 'success')
        await this.ENTREGADORES_buscaCoberturaCidade()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao alterar raios')
      } finally {
        this.carregando = false
      }
    },
    async ENTREGADORES_busca() {
      try {
        if (this.ENTREGADORES_lista.length || this.carregando) return

        this.carregando = true
        const response = await api.get('api_administracao/entregadores')

        this.ENTREGADORES_lista = response.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar entregadores')
      } finally {
        this.carregando = false
      }
    },

    // PONTOS_COLETA
    PONTOS_COLETA_debounceSalvaNovoPrazo() {
      this.debounce(async () => await this.PONTOS_COLETA_salvarNovoPrazo(), 500)
    },
    PONTOS_COLETA_conversorDia(horario) {
      const dia = this.PONTOS_COLETA_seletores.dias.find((dia) => dia.value === horario.dia)

      return dia
    },
    PONTOS_COLETA_conversorFrequencia(horario) {
      const frequencia = this.PONTOS_COLETA_seletores.frequencias.find(
        (frequencia) => frequencia.value === horario.frequencia,
      )

      return frequencia
    },
    PONTOS_COLETA_gerirModalConfigsAgenda(pontoColeta = null) {
      if (pontoColeta) {
        this.PONTOS_COLETA_horariosSeparacao()
      }

      this.PONTOS_COLETA_configurarAgenda = pontoColeta
      this.PONTOS_COLETA_modalAgendaHorarios = !!pontoColeta
      this.PONTOS_COLETA_novoHorario = {
        dia: null,
        frequencia: null,
        horario: null,
      }
    },
    PONTOS_COLETA_gerirModalFormulaCalculo(abrir) {
      this.PONTOS_COLETA_configValores.mostrar = !this.PONTOS_COLETA_configValores.mostrar
      this.PONTOS_COLETA_modalFormulaTarifa = abrir
    },
    PONTOS_COLETA_calculaTarifaPontoColeta(custoFrete = 0) {
      const valoresTarifa = this.PONTOS_COLETA_configValores
      if (!valoresTarifa?.mostrar) return

      let valorPercentual
      switch (true) {
        case !this.PONTOS_COLETA_modifiqueiValor:
          valorPercentual = valoresTarifa.porcentagem_frete
          break
        case valoresTarifa.entregas.length < 3:
          valorPercentual = 10
          break
        case custoFrete <= 0:
          valorPercentual = 0
          break
        default:
          const valoresEntregas = valoresTarifa.entregas.map((entrega) => entrega.valor_custo_produto)
          const valorMediaEntregas = valoresEntregas.reduce((acumulado, atual) => acumulado + atual) / 3

          valorPercentual = (custoFrete / valorMediaEntregas) * 100
          break
      }

      this.PONTOS_COLETA_modifiqueiValor = true
      this.PONTOS_COLETA_configValores.porcentagem_frete = parseFloat(valorPercentual.toFixed(2))
    },
    PONTOS_COLETA_iconeTipoPonto(pontoColeta) {
      let icone = ''
      switch (true) {
        case pontoColeta.id === 3:
          icone = 'mdi-map-marker'
          break
        case pontoColeta.tipo_ponto === 'PP':
          icone = 'mdi-home'
          break
        case pontoColeta.tipo_ponto === 'PM':
          icone = 'fas fa-motorcycle'
          break
      }

      return icone
    },
    async PONTOS_COLETA_horariosSeparacao() {
      try {
        this.carregando = true

        const resposta = await api.get('api_administracao/configuracoes/busca_horarios_separacao')

        this.PONTOS_COLETA_seletores.horarios = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar horários')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_buscaAgenda(pontoColeta) {
      try {
        this.carregando = true

        const parametros = new URLSearchParams({
          id_colaborador: pontoColeta.id_colaborador,
        })
        const resposta = await api.get(`api_administracao/ponto_coleta/agenda/buscar?${parametros}`)

        this.PONTOS_COLETA_gerirModalConfigsAgenda({
          ...pontoColeta,
          dias_pedido_chegar: resposta.data.dias_pedido_chegar,
          horarios: resposta.data.agenda,
        })
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar agenda')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_salvarNovoPrazo() {
      if (this.carregando) return

      try {
        this.carregando = true
        const novasConfigs = this.PONTOS_COLETA_configurarAgenda

        await api.put('api_administracao/ponto_coleta/novos_prazos', {
          id_colaborador: novasConfigs.id_colaborador,
          dias_pedido_chegar: novasConfigs.dias_pedido_chegar,
        })

        this.enqueueSnackbar(
          `Prazos do ponto de coleta ${novasConfigs.id_colaborador} atualizados com sucesso`,
          'success',
        )
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao tentar salvar o novo prazo')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_adicionaHorarioAgenda() {
      if (this.carregando) {
        return
      }

      try {
        this.carregando = true

        await api.post('api_administracao/ponto_coleta/agenda/criar_horario', {
          id_colaborador: this.PONTOS_COLETA_configurarAgenda.id_colaborador,
          ...this.PONTOS_COLETA_novoHorario,
        })
        await this.PONTOS_COLETA_buscaAgenda(this.PONTOS_COLETA_configurarAgenda)

        const index = this.PONTOS_COLETA_lista.findIndex(
          (pontoColeta) => pontoColeta.id_colaborador === this.PONTOS_COLETA_configurarAgenda.id_colaborador,
        )
        this.PONTOS_COLETA_lista[index].possui_horario = !!this.PONTOS_COLETA_configurarAgenda?.horarios?.length

        this.enqueueSnackbar('Horário adicionado com sucesso', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao adicionar horário agendado')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_removeHorarioAgenda(horario) {
      if (this.carregando) {
        return
      }

      try {
        this.carregando = true

        await api.delete(`api_administracao/ponto_coleta/agenda/remover_horario/${horario.id}`)
        await this.PONTOS_COLETA_buscaAgenda(this.PONTOS_COLETA_configurarAgenda)

        const index = this.PONTOS_COLETA_lista.findIndex(
          (pontoColeta) => pontoColeta.id_colaborador === this.PONTOS_COLETA_configurarAgenda.id_colaborador,
        )
        this.PONTOS_COLETA_lista[index].possui_horario = !!this.PONTOS_COLETA_configurarAgenda?.horarios?.length

        this.enqueueSnackbar('Horário removido com sucesso', 'success')
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao remover horário agendado')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_detalhes(entregador) {
      try {
        this.carregando = true
        const parametros = new URLSearchParams({
          id_colaborador_ponto_coleta: entregador.id_colaborador,
        })

        const response = await api.get(
          `api_administracao/pontos_de_entrega/busca/detalhes_tarifa_ponto_coleta?${parametros}`,
        )

        this.PONTOS_COLETA_configValores = {
          ...response.data,
          mostrar: true,
          id: entregador.id,
          idColaboradorPontoColeta: entregador.id_colaborador,
        }
        this.PONTOS_COLETA_BKP_configValores = JSON.stringify(this.PONTOS_COLETA_configValores)
      } catch (error) {
        this.enqueueSnackbar(error?.response?.message || error?.message || 'Erro ao buscar detalhes do ponto de coleta')
      } finally {
        this.carregando = false
      }
    },
    async PONTOS_COLETA_atualizarTarifaPontoColeta(travarPercentualEm = null) {
      try {
        if (this.carregando) return
        this.carregando = true

        const informacoes = this.PONTOS_COLETA_configValores
        const parametros = {
          deve_recalcular_percentual: informacoes.deve_recalcular,
          id_colaborador_ponto_coleta: informacoes.idColaboradorPontoColeta,
          valor_custo_frete: informacoes.valor_custo_frete,
        }
        if (typeof travarPercentualEm === 'number') {
          parametros.travar_porcentagem_em = travarPercentualEm
        }
        await api.patch('api_administracao/ponto_coleta/atualizar_tarifa', parametros)

        this.enqueueSnackbar('Tarifa atualizada com sucesso!', 'success')
        this.PONTOS_COLETA_configValores = {
          mostrar: false,
        }
        this.PONTOS_COLETA_lista = []
        this.carregando = false
        await this.PONTOS_COLETA_busca()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar tarifa')
        this.carregando = false
      }
    },
    async PONTOS_COLETA_busca() {
      try {
        if (this.PONTOS_COLETA_lista.length || this.carregando) return
        this.carregando = true

        const response = await api.get('api_administracao/ponto_coleta/busca_lista')

        this.PONTOS_COLETA_lista = response.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar pontos de coleta')
      } finally {
        this.carregando = false
      }
    },

    // FUNÇÕES GERAIS
    async gerirPontoColeta(ponto) {
      try {
        this.carregando = true

        const response = await MobileStockApi(`api_administracao/entregadores/gerir_ponto_coleta`, {
          method: 'POST',
          body: JSON.stringify({
            id_colaborador_ponto: ponto.id_colaborador,
          }),
        }).then((resp) => resp.json())
        if (!response.status) throw new Error(response.message)

        if (ponto.eh_ponto_coleta && this.areaAtual.nome === 'ENTREGADORES') {
          this.ENTREGADORES_gerirModalConfigPontoColeta(ponto)
        }

        this[`${[this.areaAtual.nome]}_lista`] = []
        this.carregando = false
        this.enqueueSnackbar(response.message, 'success')
        this.mudarArea(this.areaAtual.id)
      } catch (error) {
        this.enqueueSnackbar(error)
        this.carregando = false
      }
    },
    async buscaCidadePorNome(nome, padrao) {
      try {
        nome = nome.toString().trim().toLowerCase()
        if ([this.carregando, !nome, nome.length < 3, !!nome.match(/-/)].includes(true)) {
          return
        }

        this.carregando = true
        this[`${this.areaAtual.nome}_listaCidades`] = []
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })

        const response = await api.get(`api_administracao/cidades/pontos?${parametros}`)
        this[`${this.areaAtual.nome}_listaCidades`] = response.data
          .map((cidade) => ({
            text: cidade.label,
            value: cidade.id,
          }))
          .sort((a, b) => {
            if (a.text < b.text) return -1
            if (a.text > b.text) return 1
            return 0
          })

        if (padrao) {
          const selecionado = response.data[0]
          this.PONTO_RETIRADA_alterarDados = {
            ...this.PONTO_RETIRADA_alterarDados,
            idCidade: selecionado.id,
            cidade: selecionado.nome,
            uf: selecionado.uf,
          }
        }
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar cidade')
      } finally {
        this.carregando = false
      }
    },
    async buscaPontoColetaPorNome(nome) {
      try {
        nome = nome.toString().trim().toLowerCase()
        if ([this.carregando, !nome, nome.length < 3, !!nome.match(/-/)].includes(true)) {
          return
        }

        this.carregando = true
        delete this.ENTREGADORES_configPontoColeta.pontoColeta
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })

        const response = await api.get(`api_administracao/ponto_coleta/pesquisar_pontos_coleta?${parametros}`)
        this.ENTREGADORES_listaPontosColeta = response.data.map((pontoColeta) => ({
          text: `${pontoColeta.id_colaborador} - ${pontoColeta.razao_social} (${pontoColeta.cidade} - ${pontoColeta.uf})`,
          value: pontoColeta.id_colaborador,
        }))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar ponto de coleta')
      } finally {
        this.carregando = false
      }
    },
  },
  computed: {
    PONTO_RETIRADA_houveAlteracoesDados() {
      return JSON.stringify(this.PONTO_RETIRADA_alterarDados) !== this.PONTO_RETIRADA_BKP_alterarDados
    },
    PONTOS_COLETA_houveAlteracoesTarifa() {
      return JSON.stringify(this.PONTOS_COLETA_configValores) !== this.PONTOS_COLETA_BKP_configValores
    },
    PONTOS_COLETA_desabilitarAdicionarHorario() {
      const jaExiste = this.PONTOS_COLETA_configurarAgenda?.horarios?.some(
        (item) =>
          item.horario === this.PONTOS_COLETA_novoHorario.horario && item.dia === this.PONTOS_COLETA_novoHorario.dia,
      )

      return (
        !this.PONTOS_COLETA_novoHorario?.dia ||
        !this.PONTOS_COLETA_novoHorario?.frequencia ||
        !this.PONTOS_COLETA_novoHorario?.horario ||
        this.carregando ||
        jaExiste
      )
    },
  },
  mounted() {
    this.mudarArea(this.areaAtual.id)
  },
  watch: {
    'areaAtual.id'(novoValor) {
      const idAreaSalva = sessionStorage.getItem('areaGerenciarPontos')
      if (String(novoValor) !== idAreaSalva) {
        sessionStorage.setItem('areaGerenciarPontos', novoValor)
      }

      this.mudarArea(novoValor)
    },
    'PONTO_RETIRADA_alterarDados.telefone'(novoValor = '') {
      const telefoneFormatado = formataTelefone(novoValor)
      if (this.PONTO_RETIRADA_alterarDados.telefone === telefoneFormatado) return

      this.PONTO_RETIRADA_alterarDados.telefone = telefoneFormatado
    },
    'PONTOS_COLETA_configValores.mostrar'() {
      this.PONTOS_COLETA_modifiqueiValor = false
    },
    'PONTOS_COLETA_configValores.valor_custo_frete'(novoValor = 0) {
      if (this.carregando) return
      this.debounce(() => this.PONTOS_COLETA_calculaTarifaPontoColeta(novoValor), 1000)
    },
    pesquisaCidade(pesquisa = this.PONTO_RETIRADA_alterarDados.idCidade) {
      if (!pesquisa || pesquisa.length < 3 || this.carregando) return
      this.debounce(() => this.buscaCidadePorNome(pesquisa, false), 1000)
    },
    pesquisaPontoColeta(pesquisa) {
      if (!pesquisa || pesquisa.length < 3 || this.carregando) return
      this.debounce(() => this.buscaPontoColetaPorNome(pesquisa, false), 1000)
    },
  },
})
