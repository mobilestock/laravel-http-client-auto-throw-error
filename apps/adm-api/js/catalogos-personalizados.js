import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#catalogos-personalizados',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      meuId: null,
      cabecalho: [
        this.itemGrades('Id', 'id', true),
        this.itemGrades('Nome', 'nome', true),
        this.itemGrades('Criado por', 'razao_social', true),
        this.itemGrades('Tipo', 'tipo', true),
        this.itemGrades('Qtd Produtos', 'quantidade_produtos', true),
        this.itemGrades('Links', 'link'),
        this.itemGrades('Ativo', 'esta_ativo', true),
        this.itemGrades('Ações', 'acoes'),
      ],
      carregandoTrocarOrdem: false,
      carregandoAtivarDesativar: false,
      catalogos: [],
      carregandoCatalogos: false,
      filtros: [],
      carregandoFiltros: false,
      ordenamentoFiltros: {
        arrastandoFiltroIndex: null,
        arrastandoSobreFiltro: null,
      },
      snackbar: {
        mostrar: false,
        mensagem: '',
      },
      configuracoes: {
        tempoCache: 0,
      },
      dialogDuplicarCatalogo: {
        carregando: false,
        mostrar: false,
        nome: '',
        ativo: true,
        plataformas: [],
      },
      dialogDeletarCatalogo: {
        carregando: false,
        mostrar: false,
        id_catalogo: null,
      },
      plataformas: [
        { nome: 'Mobile Stock', valor: 'MS' },
        { nome: 'Meulook', valor: 'ML' },
        { nome: 'Meu Estoque Digital', valor: 'MED' },
      ],
    }
  },
  methods: {
    async buscarCatalogos() {
      if (this.carregandoCatalogos) return
      try {
        this.carregandoCatalogos = true
        const requisicao = await api.get('api_administracao/catalogo_personalizado/buscar')
        this.catalogos = requisicao.data
      } catch (error) {
        this.onError(error)
      } finally {
        this.carregandoCatalogos = false
      }
    },
    async buscarFiltros() {
      if (this.carregandoFiltros) return
      try {
        this.carregandoFiltros = true
        const requisicao = await api.get('api_meulook/publicacoes/filtros')
        this.filtros = requisicao.data
      } catch (error) {
        this.onError(error)
      } finally {
        this.carregandoFiltros = false
      }
    },
    buscarConfiguracoesCache() {
      api
        .get('api_administracao/configuracoes/buscar_tempo_cache_filtros')
        .then((response) => (this.configuracoes.tempoCache = response.data))
        .catch((error) => this.onError(error))
    },
    itemGrades(campo, valor, ordernavel = false, estilizacao = '') {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        align: 'center',
        class: estilizacao,
      }
    },
    onDragStart(indexFiltroSelecionado) {
      this.ordenamentoFiltros.arrastandoFiltroIndex = indexFiltroSelecionado
    },
    onDragOver(indexFiltroArrastandoSobre) {
      this.ordenamentoFiltros.arrastandoSobreFiltro = indexFiltroArrastandoSobre
    },
    async onDrop(index) {
      if (this.carregandoTrocarOrdem) return
      try {
        this.carregandoTrocarOrdem = true
        let filtrosAux = Array.from(this.filtros)
        const indexFiltro1 = this.ordenamentoFiltros.arrastandoFiltroIndex
        const indexFiltro2 = index
        if (indexFiltro1 === indexFiltro2) return
        const filtro1 = filtrosAux[indexFiltro1]
        const filtro2 = filtrosAux[indexFiltro2]
        filtrosAux[indexFiltro1] = filtro2
        filtrosAux[indexFiltro2] = filtro1
        filtrosAux = filtrosAux.map((filtro) => filtro.id)
        await api.put('api_administracao/configuracoes/alterar_ordenamento_filtros', filtrosAux)
        await this.buscarFiltros()
      } catch (error) {
        this.onError(error)
      } finally {
        this.ordenamentoFiltros.arrastandoFiltroIndex = null
        this.ordenamentoFiltros.arrastandoSobreFiltro = null
        this.carregandoTrocarOrdem = false
      }
    },
    async ativarDesativarCatalogo(catalogo) {
      if (this.carregandoAtivarDesativar) return
      try {
        this.carregandoAtivarDesativar = true
        await api.put(`api_administracao/catalogo_personalizado/ativar_desativar/${catalogo.id}`)
        const index = this.catalogos.findIndex((c) => c.id === catalogo.id)
        this.catalogos[index].esta_ativo = !this.catalogos[index].esta_ativo
        await this.buscarFiltros()
      } catch (error) {
        this.onError(error)
      } finally {
        this.carregandoAtivarDesativar = false
      }
    },
    abrirDialogDuplicarCatalogo(catalogo) {
      this.dialogDuplicarCatalogo.nome = catalogo.nome
      this.dialogDuplicarCatalogo.json_produtos = catalogo.produtos
      this.dialogDuplicarCatalogo.mostrar = true
      this.dialogDuplicarCatalogo.json_plataformas_filtros = this.plataformas.map((plataforma) => plataforma.valor)
    },
    async criarCatalogo(catalogo) {
      try {
        this.dialogDuplicarCatalogo.carregando = true
        if (catalogo.plataformas.length === 0) throw new Error('Selecione pelo menos uma plataforma')
        await api.post('api_cliente/catalogo_personalizado/', { ...catalogo, tipo: 'PUBLICO' })
        await this.buscarCatalogos()
        await this.buscarFiltros()
        this.dialogDuplicarCatalogo.mostrar = false
      } catch (error) {
        this.onError(error)
      } finally {
        this.dialogDuplicarCatalogo.carregando = false
      }
    },
    abrirDialogDeletarCatalogo(catalogo) {
      this.dialogDeletarCatalogo.id_catalogo = catalogo.id
      this.dialogDeletarCatalogo.nome = catalogo.nome
      this.dialogDeletarCatalogo.mostrar = true
    },
    async deletarCatalogo(catalogo) {
      try {
        this.dialogDeletarCatalogo.carregando = true
        await api.delete(`api_cliente/catalogo_personalizado/${catalogo.id_catalogo}`)
        await this.buscarCatalogos()
        await this.buscarFiltros()
        this.dialogDeletarCatalogo.mostrar = false
      } catch (error) {
        this.onError(error)
      } finally {
        this.dialogDeletarCatalogo.carregando = false
      }
    },
    onError(error) {
      this.snackbar.mensagem = error.response?.data.message || error.message || 'Erro ao realizar operação'
      this.snackbar.mostrar = true
    },
    abrirLinkExterno(link) {
      window.open(link, '_blank')
    },
  },
  async mounted() {
    await this.buscarCatalogos()
    await this.buscarFiltros()
    this.buscarConfiguracoesCache()
    this.meuId = cabecalhoVue.user.idColaborador
  },
})
