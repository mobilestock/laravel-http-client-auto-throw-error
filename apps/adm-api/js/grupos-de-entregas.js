import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#grupoEntregas',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      tipoEtiqueta: '',
      buscarPontosPorNome: '',
      buscarPontosPorIdColaborador: '',
      nomeGrupoEntregas: '',
      idGrupoEntregas: '',
      dialogGrupoEntregasTipo: '',
      diaFechamentoGrupoEntregas: '',
      bounce: 0,
      listaEntregas: [],
      loadingImprimeEtiquetas: false,
      loadingEditarGrupo: null,
      loadingAtivarGrupo: null,
      loadingApagarGrupo: null,
      loadingListaEntregas: false,
      loadingModalEntregas: false,
      loadingAcompanharDestinos: false,
      loadingNovoGrupoEntregas: false,
      dialogNovoGrupoEntregas: false,
      dialogBotoesEtiquetas: false,
      dialogListaDestinosDoGrupo: false,
      disabledDialogGrupoEntregas: false,
      disabledAtivarGrupo: false,
      dialogApagarGrupoEntregas: false,
      dialogImprimirEtiquetaMobile: false,
      disabledApagarGrupo: false,
      disabledEditarGrupo: false,
      loadingBuscandoPontosPorIdColaborador: false,
      loadingBuscandoPontosPorNome: false,
      grupoEntregasHeader: [
        { text: 'ID', value: 'id', sortable: false },
        { text: 'Nome', value: 'nome_grupo', sortable: false },
        { text: 'Último usuário', value: 'usuario_criacao', sortable: false },
        { text: 'Data de Criação', value: 'data_criacao', sortable: false },
        { text: 'Status', value: 'ativo', sortable: false },
        { text: 'Ações', value: 'actions', sortable: false },
      ],
      listaEntregasHeader: [
        { text: 'Id Frete', value: 'id_tipo_frete' },
        { text: 'Destino', value: 'nome' },
      ],
      grupoEntregas: [],
      listaPontosEncontrados: [],
      listaPontosSelecionados: [],
      listaClientesParaImprimir: [],
      checkboxImprimirMobile: [],
      dialogApagarGrupoEntregasItem: [],
      diasSemana: [
        { value: 'SEGUNDA', text: 'Segunda-Feira', busca: '' },
        { value: 'TERCA', text: 'Terça-Feira', busca: '' },
        { value: 'QUARTA', text: 'Quarta-Feira', busca: '' },
        { value: 'QUINTA', text: 'Quinta-Feira', busca: '' },
        { value: 'SEXTA', text: 'Sexta-Feira', busca: '' },
      ],
      classificacoesEtiquetas: {
        retiradaCentralTransportadora: 'TODAS',
        pontosRetiradaEntregadores: 'TODAS',
        pontosRetiradaEntregadoresDia: null,
      },
      snackbar: {
        mostrar: false,
        cor: '',
        texto: '',
      },
    }
  },
  methods: {
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        mostrar: true,
        cor: cor,
        texto: texto,
      }
    },
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },
    retornaClasseStatus(status) {
      if (status) {
        return 'badge badge-success'
      } else {
        return 'badge badge-warning'
      }
    },
    selecionaPonto(pontoSelecionado) {
      const filtro = this.listaPontosEncontrados.filter((ponto) => ponto.value == pontoSelecionado)

      if (this.listaPontosSelecionados.filter((ponto) => ponto.value == pontoSelecionado).length == 0) {
        this.listaPontosSelecionados = [...this.listaPontosSelecionados, ...filtro]
      }
      this.listaPontosEncontrados = []
      this.buscarPontosPorIdColaborador = ''
      this.buscarPontosPorNome = ''
    },
    removePonto(ponto) {
      this.listaPontosSelecionados = this.listaPontosSelecionados.filter((item) => item.value != ponto)
    },
    buscarPontosFn(pesquisa) {
      this.listaPontosEncontrados = []

      MobileStockApi(`api_administracao/tipo_frete/buscar?pesquisa=${pesquisa}`)
        .then((res) => res.json())
        .then((response) => {
          if (!response.status) throw new Error(response.message)

          const filtro = response.data.filter((item) => {
            return !this.listaPontosSelecionados.some((ponto) => ponto.value == item.id)
          })

          this.listaPontosEncontrados = filtro.map((item) => {
            return {
              value: item.id,
              text: item.nome,
              adicionados: item.adicionados,
            }
          })
        })
        .catch((error) => {
          this.enqueueSnackbar(error.message)
        })
        .finally(() => {
          this.loadingBuscandoPontosPorIdColaborador = false
          this.loadingBuscandoPontosPorNome = false
        })
    },
    abrirDialogNovoGrupoEntregas() {
      this.limparDadosGrupoEntregas()
      this.dialogGrupoEntregasTipo = 'novo'
      this.dialogNovoGrupoEntregas = true
    },
    abrirDialogApagarGrupoEntregas(item) {
      this.dialogApagarGrupoEntregasItem = item
      this.dialogApagarGrupoEntregas = true
    },
    abrirDialogListaEntregasDoGrupo(item) {
      this.dialogListaDestinosDoGrupo = item
      this.DialogListaEntregasDoGrupoItem = true
    },
    limparDadosGrupoEntregas() {
      this.buscarPontos = ''
      this.nomeGrupoEntregas = ''
      this.diaFechamentoGrupoEntregas = ''
      this.idGrupoEntregas = ''
      this.listaPontosEncontrados = []
      this.listaPontosSelecionados = []
    },
    fecharModalImprimirEtiquetas() {
      this.listaClientesParaImprimir = []
      this.checkboxImprimirMobile = []
      this.dialogImprimirEtiquetaMobile = false
    },
    fecharDialogListaDestinosDoGrupo() {
      this.dialogListaDestinosDoGrupo = false
      this.loadingAcompanharDestinos = false
      this.listaEntregas = []
      this.ENTREGAS_grupos_entrega = []
    },
    adicionarItemParaImprimirEtiquetaMobile(item) {
      if (
        this.checkboxImprimirMobile.find((filtro) => filtro.id_cliente === item.id_cliente) &&
        this.checkboxImprimirMobile.length > 0
      ) {
        this.checkboxImprimirMobile = this.checkboxImprimirMobile.filter(
          (filtro) => filtro.id_cliente !== item.id_cliente,
        )
      } else {
        this.checkboxImprimirMobile.push(item)
      }
    },
    desmarcarTodasEtiquetas() {
      this.checkboxImprimirMobile = []
    },
    marcarTodasEtiquetas() {
      this.checkboxImprimirMobile = this.listaClientesParaImprimir
    },
    async listaGruposEntregas() {
      try {
        this.loadingModalEntregas = true

        const resultado = await MobileStockApi('api_administracao/tipo_frete/buscar_grupos').then((res) => res.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.grupoEntregas = resultado.data
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingModalEntregas = false
      }
    },
    async salvarGrupoEntregas() {
      try {
        this.loadingNovoGrupoEntregas = true
        this.disabledDialogGrupoEntregas = true
        const ids = this.listaPontosSelecionados.map((item) => item.value)

        const resultado = await MobileStockApi('api_administracao/tipo_frete/criar_grupo', {
          method: 'POST',
          body: JSON.stringify({
            nome_grupo: this.nomeGrupoEntregas,
            dia_fechamento: this.diaFechamentoGrupoEntregas,
            ids_tipo_frete: ids,
          }),
        }).then((res) => res.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.enqueueSnackbar(resultado.message, 'success')

        this.limparDadosGrupoEntregas()
        this.dialogNovoGrupoEntregas = false
        this.listaGruposEntregas()
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingNovoGrupoEntregas = false
        this.disabledDialogGrupoEntregas = false
      }
    },
    async mudarSituacaoGrupo(item) {
      try {
        this.disabledAtivarGrupo = true
        this.loadingAtivarGrupo = item.id

        const resultado = await MobileStockApi('api_administracao/tipo_frete/mudar_situacao_grupo', {
          method: 'PATCH',
          body: JSON.stringify({
            id_grupo: item.id,
          }),
        }).then((res) => res.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.listaGruposEntregas()

        this.enqueueSnackbar(resultado.message, 'success')
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingAtivarGrupo = false
        this.disabledAtivarGrupo = null
      }
    },
    async apagarGrupoEntregas(item) {
      try {
        this.loadingApagarGrupo = item.id
        this.disabledApagarGrupo = true

        const resultado = await MobileStockApi(`api_administracao/tipo_frete/apagar_grupo/${item.id}`, {
          method: 'DELETE',
        }).then((res) => res.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.dialogApagarGrupoEntregasItem = []
        this.dialogApagarGrupoEntregas = false
        this.listaGruposEntregas()

        this.enqueueSnackbar(resultado.message, 'success')
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingApagarGrupo = null
        this.disabledApagarGrupo = false
      }
    },
    async buscarDetalhesGrupoTipoFrete(item) {
      try {
        this.loadingEditarGrupo = item.id
        this.disabledEditarGrupo = true

        const resultado = await MobileStockApi(`api_administracao/tipo_frete/buscar_detalhes_grupo/${item.id}`).then(
          (res) => res.json(),
        )

        if (!resultado.status) throw new Error(resultado.message)

        this.nomeGrupoEntregas = resultado.data.nome_grupo
        this.idGrupoEntregas = resultado.data.id
        this.diaFechamentoGrupoEntregas = resultado.data.dia_fechamento
        this.listaPontosSelecionados = resultado.data.tipo_frete_grupo_item.map((item) => {
          return {
            value: item.tipo_frete_id,
            text: item.tipo_frete_grupos_item_nome,
            adicionados: item.adicionados,
          }
        })
        this.dialogGrupoEntregasTipo = 'editar'
        this.dialogNovoGrupoEntregas = true
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingEditarGrupo = null
        this.disabledEditarGrupo = false
      }
    },

    async listaEntregaPeloGrupo(item) {
      this.idGrupoEntregas = item.id
      this.nomeGrupoEntregas = item.nome_grupo
      this.loadingListaEntregas = true
      try {
        this.ENTREGAS_disabled_fechar_entrega = true
        const resultado = await api.get(`api_administracao/tipo_frete/listar_destinos_grupo/${this.idGrupoEntregas}`)

        resultado.data.retorno.map((item) => {
          this.listaEntregas.push({
            id_tipo_frete: item.id_tipo_frete,
            id_colaborador: item.id_colaborador_tipo_frete,
            nome: item.nome,
            destinos: item.destinos.sort((a, b) => a.id_raio - b.id_raio),
          })
        })

        this.abrirDialogListaEntregasDoGrupo(item)
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingListaEntregas = false
      }
    },
    async acompanharDestinosDoGrupo() {
      try {
        this.loadingAcompanharDestinos = true

        const destinosArr = this.listaEntregas.flatMap((item) =>
          item.destinos.map((destino) => ({
            id_colaborador: item.id_colaborador,
            id_tipo_frete: item.id_tipo_frete,
            id_cidade: destino.id_cidade,
            id_raio: destino.id_raio,
            apelido: destino.apelido,
            cidade: destino.cidade,
            identificador: destino.identificador,
          })),
        )

        await api.post('api_administracao/acompanhamento/acompanhar_em_grupo', destinosArr)
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Não foi possível acompanhar os destinos',
        )
      } finally {
        this.fecharDialogListaDestinosDoGrupo()
      }
    },
    async editarGrupoEntregas() {
      try {
        this.loadingEditarGrupo = this.idGrupoEntregas
        this.disabledEditarGrupo = true
        const ids = this.listaPontosSelecionados.map((item) => item.value)

        const resultado = await MobileStockApi('api_administracao/tipo_frete/editar_grupo', {
          method: 'PATCH',
          body: JSON.stringify({
            id_grupo: this.idGrupoEntregas,
            nome_grupo: this.nomeGrupoEntregas,
            dia_fechamento: this.diaFechamentoGrupoEntregas,
            ids_tipo_frete: ids,
          }),
        }).then((res) => res.json())

        if (!resultado.status) throw new Error(resultado.message)

        this.limparDadosGrupoEntregas()
        this.dialogNovoGrupoEntregas = false
        this.listaGruposEntregas()

        this.enqueueSnackbar(resultado.message, 'success')
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loadingEditarGrupo = null
        this.disabledEditarGrupo = false
      }
    },
    async imprimeEtiquetasSeparacao() {
      try {
        this.loadingImprimeEtiquetas = true
        const { retiradaCentralTransportadora, pontosRetiradaEntregadoresDia } = this.classificacoesEtiquetas
        let parametros = {
          tipo_logistica: retiradaCentralTransportadora,
        }
        if (pontosRetiradaEntregadoresDia?.value) {
          parametros.dia_da_semana = pontosRetiradaEntregadoresDia?.value
        }
        parametros = new URLSearchParams(parametros)

        const resposta = await api.get(
          `api_estoque/separacao/busca/etiquetas_separacao_produtos_filtradas?${parametros}`,
        )
        const items = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao imprimir etiquetas')
      } finally {
        this.loadingImprimeEtiquetas = false
      }
    },

    async listarEtiquetasSeparacaoCliente(etiquetaMobile) {
      try {
        this.loadingImprimeEtiquetas = true

        this.tipoEtiqueta = etiquetaMobile

        const resposta = await api.get(
          `api_estoque/separacao/lista_produtos_separacao?etiqueta_mobile=${etiquetaMobile}`,
        )

        this.checkboxImprimirMobile = resposta.data
        this.listaClientesParaImprimir = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar as etiquetas')
      } finally {
        this.loadingImprimeEtiquetas = false
      }
    },

    async imprimeEtiquetasSeparacaoCliente() {
      try {
        const uuid_produto = []

        this.checkboxImprimirMobile.forEach((item) =>
          Object.values(item.produtos).map((item) => uuid_produto.push(item.uuid_produto)),
        )

        this.loadingImprimeEtiquetas = true
        const resposta = await api.post('api_estoque/separacao/produtos/etiquetas', {
          uuids: uuid_produto,
          tipo_etiqueta: this.tipoEtiqueta,
          formato_saida: 'ZPL',
        })

        this.fecharModalImprimirEtiquetas()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao imprimir etiquetas')
      } finally {
        this.loadingImprimeEtiquetas = false
        this.tipoEtiqueta = ''
      }
    },

    limparInformacoesModalImprimir() {
      this.listaClientesParaImprimir = []
      this.tipoEtiqueta = ''
    },
  },
  mounted() {
    this.listaGruposEntregas()
  },
  watch: {
    buscarPontosPorNome(pesquisa) {
      if (!pesquisa) return
      this.loadingBuscandoPontosPorNome = true
      this.debounce(() => this.buscarPontosFn(pesquisa), 1000)
    },
    buscarPontosPorIdColaborador(pesquisa) {
      if (!pesquisa || isNaN(Number(pesquisa))) return
      this.loadingBuscandoPontosPorIdColaborador = true
      this.debounce(() => this.buscarPontosFn(pesquisa), 1000)
    },
    'classificacoesEtiquetas.pontosRetiradaEntregadores'(valor) {
      if (valor === 'DIA') {
        const indexDay = new Date().getDay()
        const diaDaSemana = this.diasSemana[indexDay - 1]
        if (!diaDaSemana) {
          this.enqueueSnackbar('Não existem pedidos para esse dia', 'info')
          return
        }
        this.classificacoesEtiquetas.pontosRetiradaEntregadoresDia = diaDaSemana

        return
      }

      this.classificacoesEtiquetas.pontosRetiradaEntregadoresDia = null
    },
  },
})
