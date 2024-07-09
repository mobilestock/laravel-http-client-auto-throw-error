import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var fornecedoresProdutosVUE = new Vue({
  el: '#fornecedoresProdutosVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  mixins: [recursiveFindMixin],
  data: {
    fornecedor: {
      idFornecedor: '',
      nivelAcesso: '',
      id: '',
    },
    produtoSendoDeletado: null,
    openModalPermissao: false,
    rules: {
      required: (value) => !!value || 'Campo obrigatório.',
      counter: (value) => (!!value && value.length <= 100) || 'Max 20 characters',
      email: (value) => {
        const pattern =
          /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        return pattern.test(value) || 'E-mail inválido'
      },
      valorMin: (valor) => (!!valor && parseFloat(valor) >= 0.5) || 'O valor mínimo é 50 centavos.',
      valorMax(value, max, campo) {
        ;(v) => true
        return (value) => (!!value && parseInt(value) <= max) || `O valor máximo para ${campo} é ${max}.`
      },
    },
    filter: {},
    formas: [
      {
        text: 'Pequena (Recomendar numeração maior)',
        value: 'PEQUENA',
      },
      {
        text: 'Normal',
        value: 'NORMAL',
      },
      {
        text: 'Grande (Recomendar numeração menor)',
        value: 'GRANDE',
      },
    ],
    embalagens: [
      {
        text: 'Caixa',
        value: 'CAIXA',
      },
      {
        text: 'Sacola',
        value: 'SACOLA',
      },
    ],
    porcentagemMS: {
      valor: '0.00',
      valor_ida: '0.00',
    },
    porcentagemML: {
      valor: '0.00',
      valor_ida: '0.00',
    },
    configsModalFoto: {
      width: 600,
      height: 600,
    },
    modalFoto: {
      open: false,
      cardTitle: '',
      cardPath: '',
    },
    snackbar: {
      text: '',
      color: 'error',
      open: false,
      button: 'FECHA_AVISO',
      top: false,
    },
    formulario: {
      descricao: '',
      nome_comercial: '',
      id_fornecedor: '',
      id_tabela: '',
      array_id_categoria_formatado: [],
      array_id_categoria: [],
      array_id_tipo: [],
      arrayCategoriesToMerge: [],
      arrayTypesToMerge: [],
      id_linha: '',
      bloqueado: 0,
      data: new Date().toLocaleDateString('en-US'),
      valor_custo_produto: '0,00',
      valor_venda_ms: '0,00',
      valor_venda_ml: '0,00',
      premio: 0,
      premio_pontos: 0,
      altura_solado: '',
      grades: [],
      consignado: '0',
      embalagem: null,
      forma: 'NORMAL',
      tipo_grade: '1',
      sexo: '',
      outras_informacoes: '',
      fotos: [],
      videos: [],
      listaFotosCatalogoAdd: [],
      listaFotosCalcadasAdd: [],
      listaFotosRemover: [],
      listaFotosPendentes: [],
      listaFotosParaCrop: [],
      listaVideosRemover: [],
      permitido_reposicao: 0,
      fora_de_linha: false,
      old_fora_de_linha: false,
    },
    detalhesSalto: {
      caminho: 'images/salto1.jpg',
      descricao: 'Detalhes Salto',
      texto: [
        '- Apoie o sapato sobre uma superfície plana.',
        '- Posicione a régua sobre a região do salto.',
        '- Marque a medida entre a base do sapato e a palmilha.',
      ],
    },
    detalhesPalmilha: {
      caminho: 'images/foto-do-pe.jpg',
      descricao: 'Detalhes Palmilha',
      texto: [
        '- Preencha os valores de tamanho mínimo e máximo para gerar a grade',
        '- Informe a medida em centímetros da palmilha segundo cada numeração',
      ],
    },
    avaliacao: {
      comentarios: [],
      rating: {},
    },
    itemsPerPageArray: [12],
    items: [],
    keys: [
      {
        value: 'id',
        text: 'ID',
      },
      {
        value: 'consignado',
        text: 'Consignado',
      },
      {
        value: 'descricao',
        text: 'Descrição',
      },
      {
        value: 'cores',
        text: 'Cores',
      },
      {
        value: 'tipo_grade',
        text: 'Tipo de grade',
      },
      {
        value: 'array_id_categoria_formatado',
        text: 'Categoria',
      },
      {
        value: 'array_id_tipo',
        text: 'Tipo',
      },
      {
        value: 'fotosProduto',
        text: 'Produtos Sem Foto',
      },
    ],
    categorias: [],
    backupCategorias: [],
    tipos: [],
    backupTipos: [],
    idsCategorias: [],
    fornecedores: [],
    selectFornecedor: [],
    linhas: [],
    tipos_grades: [],
    buscaFornecedor: '',
    search: '',
    sortBy: 'id',
    fornecedorTemp: '0',
    loading_cadastrar_produto: false,
    valid: false,
    sortDesc: true,
    modalMobilePay: false,
    modal: false,
    overlay: false,
    alterouPreco: false,
    desabilitaBotao: false,
    modalAvaliacao: false,
    loading: false,
    loadingVideo: false,
    page: 1,
    itemsPerPage: 15,
    listItem: 0,
    paginaAvaliacao: 1,
    loadingSalvandoProduto: false,
    cores: [],
    materiais: [],
    numberOfPages: 0,
    loadingRemovendoFoto: 0,
    pesquisaLiteral: false,
    valorCorDescrita: '',
    abrirModalAddFoto: false,
    fotoAtivaModalAddFoto: -1,
    showProductsOff: false,
    cropper: null,
    videoUrl: '',
  },
  methods: {
    //------------------- GERAL----------------------------
    hookParaCrop() {
      if (this.formulario.listaFotosParaCrop == null) return
      if (this.formulario.listaFotosParaCrop && this.formulario.listaFotosParaCrop.length > 0) {
        this.formulario.listaFotosPendentes.push(this.formulario.listaFotosParaCrop[0])
      } else {
        this.formulario.listaFotosPendentes = []
        this.abrirModalAddFoto = false
      }
    },
    copiaLink() {
      var $temp = $('<input>')
      $('body').append($temp)
      $temp.val(this.link).select()
      document.execCommand('copy', false, $temp.val())
      $temp.remove()
      this.enqueueSnackbar('Link copiado para área de transferência', 'success')
    },
    salvar() {
      if (!this.$refs.form.validate()) {
        this.enqueueSnackbar('Por favor preencha todos os campos obrigatórios')
        return
      }
      if (this.formulario.old_fora_de_linha !== this.formulario.fora_de_linha && this.formulario.fora_de_linha) {
        this.enqueueSnackbar(
          'Ao marcar este produto como fora de linha, se houver estoque externo, ele será zerado.',
          'info',
          'LIMPA_ESTOQUE',
          true,
        )
        return
      }

      this.salvarProduto()
    },
    editarProduto(produto) {
      this.formulario = {
        ...this.formulario,
        ...produto,
      }
      if (produto.array_id_categoria?.length > 1) {
        this.arrayCategoriesToMerge = [Object.assign({}, produto).array_id_categoria_formatado]
        this.arrayTypesToMerge = [Object.assign({}, produto).array_id_tipo]
        this.loadedFiltered(this.arrayTypesToMerge[0])
      }
      this.modal = true
      this.validate()
    },
    // Modal Foto produto
    visualizarFoto(item) {
      this.modalFoto.cardTitle = item.descricao
      this.modalFoto.cardPath = item.caminho
      this.modalFoto.cardText = item.texto
      this.modalFoto.open = true
    },
    limpaModalProdutos() {
      this.limpaFormulario()
      this.modal = false
      this.modalAvaliacao = false
      this.alterouPreco = false
      this.avaliacao = {
        comentarios: [],
        rating: {},
      }
    },
    limpaFormulario() {
      let data = new Date()
      const dia = data.getDate().toString()
      const diaF = dia.length == 1 ? '0' + dia : dia
      const mes = (data.getMonth() + 1).toString() //+1 pois no getMonth Janeiro começa com zero.
      const mesF = mes.length == 1 ? '0' + mes : mes
      const anoF = data.getFullYear()
      let dataFormatada = mesF + '/' + diaF + '/' + anoF

      this.formulario = {
        descricao: '',
        nome_comercial: '',
        id_fornecedor: this.fornecedor.idFornecedor,
        id_tabela: '',
        array_id_categoria_formatado: [],
        array_id_categoria: [],
        array_id_tipo: [],
        arrayCategoriesToMerge: [],
        arrayTypesToMerge: [],
        id_linha: '',
        bloqueado: 0,
        data: dataFormatada,
        preco: 0,
        premio: 0,
        premio_pontos: 0,
        grades: [],
        consignado: '1',
        embalagem: null,
        forma: 'NORMAL',
        tipo_grade: '1',
        sexo: '',
        outras_informacoes: '',
        cores: [],
        fotos: [],
        videos: [],
        listaFotosCatalogoAdd: [],
        listaFotosCalcadasAdd: [],
        listaFotosRemover: [],
        listaFotosPendentes: [],
        listaFotosParaCrop: [],
        listaVideosRemover: [],
        permitido_reposicao: 0,
        fora_de_linha: false,
        old_fora_de_linha: false,
        desabilitaBotao: false,
      }
    },
    limpaModalDetalhes() {
      this.modalFoto.open = false
      this.avaliacao = {
        comentarios: [],
        rating: {},
      }
      this.modalAvaliacao = false
      this.$refs.form.resetValidation()
    },
    calculoVolta(valor) {
      return valor / (100 - valor)
    },
    calculaValor() {
      let custo = parseInt(this?.formulario?.valor_custo_produto?.replace(/\D/g, '') || '0').toString()
      switch (true) {
        case custo.length == 1:
          custo = '0.0' + custo
          break
        case custo.length == 2:
          custo = '0.' + custo
          break
        case custo.length > 2:
          custo = custo.slice(0, custo.length - 2) + '.' + custo.slice(custo.length - 2)
          break
      }
      this.formulario.valor_venda_ms = (custo * (1 + this.calculoVolta(this.porcentagemMS.valor))).toFixed(2)
      this.formulario.valor_venda_ml = (custo * (1 + this.porcentagemML.valor / 100)).toFixed(2)
      this.formulario.valor_custo_produto = custo
    },
    // -------------AJAXS---------------------
    async buscaFornecedorPeloNome(nome) {
      try {
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })
        const resposta = await api.get(`api_administracao/fornecedor/busca_fornecedores?${parametros}`)

        this.selectFornecedor = resposta.data.map((fornecedor) => ({
          ...fornecedor,
          nome: `${fornecedor.id} - ${fornecedor.nome}`,
        }))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar fornecedores')
      }
    },
    async buscaConfigsCadastro() {
      try {
        await MobileStockApi('api_administracao/produtos/lista_configs_pra_cadastro')
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              this.linhas = resp.data.linhas
              this.tipos_grades = resp.data.tipos_grade
              this.cores = resp.data.cores.map((cor) => cor.nome.replace(/_/g, ' '))
              const porcentagens = resp.data.porcentagens
              this.porcentagemMS.valor = porcentagens.porcentagem_comissao_ms
              this.porcentagemMS.valor_ida = (this.calculoVolta(this.porcentagemMS.valor) * 100).toFixed(2)
              this.porcentagemML.valor =
                porcentagens.porcentagem_comissao_ml + porcentagens.porcentagem_comissao_ponto_coleta
              this.porcentagemML.valor_ida = this.porcentagemML.valor.toFixed(2)
              const categorias = resp.data.categorias_tipos.categorias
              const tipos = resp.data.categorias_tipos.tipos
              this.backupCategorias = categorias
              this.idsCategorias = categorias.map((categoria) => {
                return categoria.id
              })
              this.categorias = Object.values(categorias)
              this.backupTipos = tipos
            } else {
              throw new Error(resp.message)
            }
          })
          .then(() => {
            this.getAllProdutosFornecedor()
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      }
    },
    async getAllProdutosFornecedor() {
      this.loading = true

      try {
        const parametros = new URLSearchParams({
          pagina: this.page,
          pesquisa: this.search || '',
          pesquisa_literal: this.pesquisaLiteral,
          items_por_pagina: this.itemsPerPage,
          fora_de_linha: this.showProductsOff,
        })

        const retorno = await api.get(
          `api_administracao/fornecedor/busca_produtos/${this.fornecedor.idFornecedor}?${parametros}`,
        )
        const produtos = retorno.data.items
        const totalPaginas = retorno.data.qtd_paginas

        if (this.numberOfPages != totalPaginas) {
          this.page = 1
        }
        this.numberOfPages = totalPaginas
        this.items = produtos.map((produto) => {
          produto.old_fora_de_linha = produto.fora_de_linha
          produto.manter_foto = produto.fotos.some((item) => item.eh_foto_salva)
          if (produto.array_id_categoria?.length === 2) {
            const idCategoria = produto.array_id_categoria.find((id) => this.idsCategorias.includes(id))
            produto.array_id_categoria_formatado = [idCategoria]
            produto.array_id_tipo = produto.array_id_categoria.filter((id) => id != idCategoria)
          } else {
            produto.array_id_categoria_formatado = [null]
            produto.array_id_tipo = [null]
          }

          return produto
        })

        if (this.pesquisaLiteral) {
          this.editarProduto(this.items[0])

          this.pesquisaLiteral = false
        }
      } catch (error) {
        this.items = []
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao carregar produtos')
      } finally {
        this.loading = false
      }
    },
    async salvarProduto() {
      try {
        if (this.videoUrl !== '') {
          await this.adicionaVideo(this.videoUrl)
        }
        this.loadingSalvandoProduto = true

        this.$set(this.formulario, 'grades', this.grades)
        this.$set(this.formulario, 'array_id_categoria', this.assembleCategories())
        if (!this.formulario?.id_fornecedor) {
          this.$set(this.formulario, 'id_fornecedor', this.fornecedor.idFornecedor)
        }

        let form = new FormData()
        this.formulario.listaFotosCatalogoAdd.forEach(
          (foto, key) => (this.formulario[`listaFotosCatalogoAdd[${key}]`] = foto),
        )
        this.formulario.listaFotosCalcadasAdd.forEach(
          (foto, key) => (this.formulario[`listaFotosCalcadasAdd[${key}]`] = foto),
        )

        form.append('formulario', JSON.stringify(this.formulario))

        await api.post('api_administracao/produtos', form)
        this.limpaModalProdutos()
        this.getAllProdutosFornecedor()
        this.enqueueSnackbar('Produto salvo com sucesso!', 'success')
        this.loadingSalvandoProduto = false
      } catch (error) {
        this.loadingSalvandoProduto = false
        this.enqueueSnackbar(error.response.data.message, 'error')
      }
    },
    // -------------- VALIDAÇÃO DO FORM--------------
    validate() {
      var form = document.getElementById('formulario')
      form
        ? this.$refs.form.validate()
        : setTimeout(() => {
            this.validate()
          }, 500)
    },
    reset() {
      this.$refs.form.reset()
    },
    resetValidation() {
      this.$refs.form.resetValidation()
    },
    //---------------------- Data Iterator Methods---------------------
    nextPage() {
      if (this.page + 1 <= this.numberOfPages) this.page += 1
    },
    formerPage() {
      if (this.page - 1 >= 1) this.page -= 1
    },
    updateItemsPerPage(number) {
      this.itemsPerPage = number
      this.getAllProdutosFornecedor()
    },
    customSort(items) {
      items.sort((a, b) => {
        if (this.sortBy === 'date') {
          if (!this.sortDesc) {
            return dateHelp.compare(a.date, b.date)
          } else {
            return dateHelp.compare(b.date, a.date)
          }
        } else if (this.sortBy === 'fotosProduto') {
          if (!this.sortDesc) {
            return !a[this.sortBy] ? -1 : 1
          } else {
            return a[this.sortBy] ? -1 : 1
          }
        } else if (this.sortBy === 'id') {
          if (!this.sortDesc) {
            return parseInt(a[this.sortBy]) < parseInt(b[this.sortBy]) ? -1 : 1
          } else {
            return parseInt(b[this.sortBy]) < parseInt(a[this.sortBy]) ? -1 : 1
          }
        } else {
          if (!this.sortDesc) {
            return a[this.sortBy] < b[this.sortBy] ? -1 : 1
          } else {
            return b[this.sortBy] < a[this.sortBy] ? -1 : 1
          }
        }
      })
      return items
    },
    getImgPlaceholder() {
      return 'images/img-placeholder.png'
    },
    adicionarNovoProdutoAPartirDesse(item) {
      const itemCopia = {
        ...item,
        cores: [],
        bloqueado: false,
        fotos: [],
        videos: [],
        id: 0,
        listaFotosCalcadasAdd: [],
        listaFotosCatalogoAdd: [],
        listaFotosPendentes: [],
        listaFotosRemover: [],
        listaVideosRemover: [],
        manter_foto: false,
        permitido_reposicao: 0,
      }
      this.arrayCategoriesToMerge = [itemCopia.array_id_categoria_formatado]
      this.arrayTypesToMerge = [itemCopia.array_id_tipo]
      this.loadedFiltered(this.arrayTypesToMerge[0])
      this.formulario = itemCopia
      this.modal = true
      this.validate()
    },

    deletaProduto(id) {
      self = this
      $.confirm({
        title: 'Confirmar?',
        content: 'Deseja realmente remover esse produto? <br> <b>Essa ação não pode ser revertida</b>',
        buttons: {
          nao() {},
          Sim: {
            btnClass: 'btn btn-danger',
            async action() {
              if (self.produtoSendoDeletado !== null) return

              try {
                self.produtoSendoDeletado = id
                await api.delete('api_administracao/produtos/' + id)
                self.enqueueSnackbar('Produto apagado!', 'success')
                location.reload()
              } catch (error) {
                self.enqueueSnackbar(error.response.data.message, 'error')
              } finally {
                self.produtoSendoDeletado = null
              }
            },
          },
        },
      })
    },

    deletaFotoProduto(foto) {
      if (this.formulario.manter_foto && this.formulario.fotos.length === 1) {
        this.enqueueSnackbar('O produto precisa ter pelo menos uma foto')
        return
      }
      this.formulario.fotos = this.formulario.fotos.filter((el) => el.sequencia != foto.sequencia)
      if (typeof foto.caminho === 'string') {
        this.formulario.listaFotosRemover.push(foto.sequencia)
      }
    },

    deletaVideoProduto(index) {
      this.formulario.listaVideosRemover.push(this.formulario.videos[index])
      this.formulario.videos.splice(index, 1)
    },

    abreSeletorImagem() {
      this.$refs.inputFotoAdd.click()
    },

    insereFotoProduto(e) {
      this.configurarModalFoto()
      let fotosAdd = [...e.target.files].map((file, index) => ({
        file,
        foto_preview: URL.createObjectURL(file),
        id: index,
      }))

      if (fotosAdd.length > 0) {
        this.formulario.listaFotosParaCrop = []
        this.formulario.listaFotosParaCrop.push(...fotosAdd)
        this.hookParaCrop()
        this.abrirModalAddFoto = true
      }
    },

    calculaListaFotos() {
      this.formulario.listaFotosCalcadasAdd = []
      this.formulario.listaFotosCatalogoAdd = []

      this.formulario.fotos.forEach((foto) => {
        if (typeof foto.caminho === 'string') return

        if (foto.tipo_foto === 'LG') {
          this.formulario.listaFotosCalcadasAdd.push(foto.caminho)
        } else {
          this.formulario.listaFotosCatalogoAdd.push(foto.caminho)
        }
      })
    },
    adicionaNovoTamanhoGradeAhPartirDeOutro(item) {
      this.grades.push({
        nome_tamanho: parseInt(item.nome_tamanho) + 1,
        sequencia: parseInt(item.sequencia) + 1,
        valor: 0,
        esta_desabilitado: false,
      })
      this.$nextTick(() => this.$refs.botaoAddNovaGrade[0].$el.focus())
    },

    adicionaNovoTamanhoGrade() {
      this.grades.push({ nome_tamanho: 13, sequencia: 13, valor: 0 })
      this.$nextTick(() => this.$refs.botaoAddNovaGrade[0].$el.focus())
    },

    retornaIdVideo(link) {
      const match = link.match(/(?:youtube\.com.*(?:\?v=|\/embed\/)|youtu.be\/)(.{11})/)
      if (!match) {
        throw new Error('Insira um link válido do Youtube')
      }

      return match[match.length - 1]
    },

    async adicionaVideo(link) {
      try {
        this.loadingVideo = true
        if (!this.formulario.videos?.length) {
          this.formulario.videos = []
        } else if (this.formulario.videos.some((item) => item.link === link)) {
          throw new Error('Esse link já foi adicionado')
        }

        const idYoutube = this.retornaIdVideo(link)
        const resposta = await api.get(`api_administracao/produtos/titulo_video/${idYoutube}`)

        this.formulario.videos.push({
          link: link,
          titulo: resposta.data,
          id_youtube: idYoutube,
        })
        this.videoUrl = ''
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao adicionar vídeo')
      } finally {
        this.loadingVideo = false
      }
    },

    fotoEhDeletavel(idUsuario) {
      return this.fornecedor.nivelAcesso == 30 ? cabecalhoVue.user.id == idUsuario : true
    },

    removeFotoRedimensionamento(key) {
      this.formulario.listaFotosPendentes.splice(key, 1)
      this.fotoAtivaModalAddFoto = -1
      if (this.formulario.listaFotosPendentes.length === 0) {
        this.abrirModalAddFoto = false
      }
    },

    result(output) {
      this.cropped = output
    },

    croppImagemPendente() {
      this.cropper
        .getCroppedCanvas({
          options: {
            imageSmoothingQuality: 'high',
          },
        })
        .toBlob((blob) => {
          let reader = new FileReader()
          reader.readAsDataURL(blob)
          reader.onloadend = async () => {
            this.formulario.listaFotosPendentes[0].foto_preview = reader.result
            this.formulario.listaFotosPendentes[0].file = await this.fotoPreviewParaFile(
              reader.result,
              fornecedoresProdutosVUE.formulario.nome_comercial +
                '_' +
                this.formulario.listaFotosPendentes[0].id +
                '.jpg',
              'image/jpeg',
            )

            let url = URL.createObjectURL(this.formulario.listaFotosPendentes[0].file)
            this.formulario.fotos.push({
              caminho: this.formulario.listaFotosPendentes[0].file,
              foto_preview: url,
              tipo_foto: 'LG',
              eh_foto_salva: false,
              sequencia:
                this.formulario.fotos.reduce((total, item) => (total > item.sequencia ? total : item.sequencia), 0) + 1,
              id_usuario: cabecalhoVue.user.id,
            })

            this.cropper.destroy()
            this.cropper = null

            this.formulario.listaFotosPendentes = []
          }
        }, 'image/jpeg')
    },

    async fotoPreviewParaFile(src, fileName, mimeType) {
      return await fetch(src)
        .then((res) => res.arrayBuffer())
        .then(
          (buf) =>
            new File([buf], fileName, {
              type: mimeType,
            }),
        )
        .catch(console.error)
    },
    loadedFiltered(selected) {
      if (!selected) {
        this.tipos = []
      } else {
        const tiposListados = this.backupTipos
        let tiposCorretos

        tiposListados.forEach((item) => {
          if (item['id'] == selected[0]) {
            tiposCorretos = item
          }
        })
        this.tipos = [tiposCorretos]
      }
    },
    filtering() {
      const selecionado = this.formulario.array_id_categoria_formatado[0]
      this.tipos = []
      // Filtra o input tipos:
      this.filteringTypes(selecionado)
      // Seleciona a categoria:
      this.selectCategories(selecionado)
    },
    // Filtra o input tipos:
    filteringTypes(selected) {
      const backupTipos = this.backupTipos
      let tiposIncompletos = []

      backupTipos.forEach((item) => {
        if (item['id_categoria_pai'] === selected) {
          tiposIncompletos.push(item)
        }
      })

      tiposIncompletos.forEach((icn) => {
        this.temFilho(icn)
      })
      this.tipos = tiposIncompletos
    },
    // Verificação dinâmica se tem filho
    temFilho(array) {
      if (array['children']) {
        array['children'].forEach((filho) => {
          this.temFilho(filho)
        })
      } else {
        return array
      }
    },
    // Seleciona a categoria:
    selectCategories(selected) {
      const arrayCategoriesBase = this.categorias
      let arrayCategoriesToMerge

      arrayCategoriesBase.forEach((cate) => {
        if (cate.id == selected) {
          arrayCategoriesToMerge = [cate.id]
        }
      })
      this.arrayCategoriesToMerge = arrayCategoriesToMerge
    },
    // Seleciona a tipo:
    selectTypes() {
      const selecionado = this.formulario.array_id_tipo[0]
      const arrayTypesBase = this.tipos
      let arrayTypesToMerge

      arrayTypesBase.forEach((type) => {
        if (type.id == selecionado) {
          arrayTypesToMerge = type.id
        }
      })
      this.arrayTypesToMerge = arrayTypesToMerge
    },
    // Junta todos arrays:
    assembleCategories() {
      let array_id_categoria = []

      if (!this.formulario.array_id_categoria_formatado || !this.formulario.array_id_tipo) {
        array_id_categoria = undefined
        this.formulario.array_id_categoria_formatado = []
        this.formulario.array_id_tipo = []
      } else {
        array_id_categoria.push(this.formulario.array_id_categoria_formatado[0])
        array_id_categoria.push(this.formulario.array_id_tipo[0])
      }

      return array_id_categoria
    },
    removeCaracteresEspeciaisReferencia(input) {
      if (input.length <= 10) {
        this.formulario.descricao = this.formataNome(input)
      }
    },
    formataNome(input) {
      const textoFormatado = removeAcentos(input)

      return textoFormatado
    },
    configurarModalFoto() {
      if (window.innerHeight < 375 || window.innerWidth < 667) {
        this.configsModalFoto.width = 300
        this.configsModalFoto.height = 300
      } else {
        this.configsModalFoto.width = 600
        this.configsModalFoto.height = 600
      }
    },
    atualizaPermissao() {
      this.formulario.permitido_reposicao = !this.formulario.permitido_reposicao
      this.openModalPermissao = false
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error', botao = 'FECHA_AVISO', topo = false) {
      this.snackbar = {
        open: true,
        text: texto,
        color: cor,
        button: botao,
        top: topo,
      }
    },
  },
  computed: {
    filteredKeys() {
      return this.keys.filter((key) => {
        return key.value !== 'fotosProduto' && key.value !== 'consignado'
      })
    },
    link() {
      if (typeof this.fornecedor.idFornecedor != 'undefined') {
        return `https://www.mobilestock.com.br/index.php?fornecedor=${this.fornecedor.idFornecedor}&num_pagina=0&ordenar=0`
      }
    },
    grades() {
      const tipoGrade = this.tipos_grades.find((el) => el.id == this.formulario.tipo_grade)
      return tipoGrade?.grade_json || this.formulario.grades
    },
    lengthAvaliacao() {
      return this.avaliacao.rating ? Math.ceil(parseInt(this.avaliacao.rating.avaliacoes) / 5) : 5
    },
    possuiIncompleto() {
      return this.items.some((item) => item.incompleto)
    },

    gradeEhEditavel() {
      let tipoGrade = this.tipos_grades.find((el) => el.id == this.formulario.tipo_grade)
      return !tipoGrade?.grade_json
    },
    redirecionaTutorial() {
      window.open('https://www.youtube.com/watch?v=sq3LGX6MQhE', '_blank')
    },
  },
  async mounted() {
    this.configurarModalFoto()
    this.buscaConfigsCadastro()
    const idFornecedor = document.querySelector('#id_colaborador').value
    this.$set(this.fornecedor, 'idFornecedor', idFornecedor)
    this.$set(this.formulario, 'id_fornecedor', idFornecedor)

    this.$set(this.fornecedor, 'id', $('#cabecalhoVue input[name=userID]').val())
    this.$set(this.fornecedor, 'nivelAcesso', $('#cabecalhoVue input[name=nivelAcesso]').val())

    let idProduto = document.querySelector('#id_produto').value
    if (idProduto) {
      this.search = idProduto
      this.pesquisaLiteral = true
    }
  },
  watch: {
    sortBy(newVal) {
      this.listItem =
        this.filteredKeys
          .map(function (e) {
            return e.value
          })
          .indexOf(newVal) >= 0
          ? this.filteredKeys
              .map(function (e) {
                return e.value
              })
              .indexOf(newVal)
          : this.listItem
    },
    modal(newVal) {
      if (newVal) return
      this.limpaModalProdutos()
      this.$refs.form.resetValidation()
    },
    buscaFornecedor(val) {
      val && val !== this.fornecedor.idFornecedor && this.buscaFornecedorPeloNome(val)
    },
    'formulario.nome_comercial'(valor) {
      this.formulario.nome_comercial = removeAcentos(valor).substr(0, 100)
    },
    'formulario.tipo_grade': {
      handler(newV) {
        if (!['1', '3'].includes(String(newV))) {
          this.formulario.embalagem = null
          this.formulario.forma = 'NORMAL'
        }
        if (newV == 1) {
          this.formulario.grades = this.formulario.grades.map((grade) => {
            if (!grade.esta_desabilitado) grade.nome_tamanho = grade.sequencia
            return grade
          })
        }
      },
    },
    'formulario.fotos': {
      handler(newV) {
        this.calculaListaFotos()
      },
    },
    fornecedor: {
      handler(val) {
        if (val.idFornecedor && val.idFornecedor) {
          if (this.idsCategorias.length) {
            this.getAllProdutosFornecedor()
          }
        }
      },
      deep: true,
    },
    desc: {
      handler(val) {
        this.formulario.descricao =
          (val.descricao ? val.descricao : '') + ' ' + (val.cor ? this.$options.filters.upperCase(val.cor) : '')
      },
      deep: true,
    },
    page(newV) {
      this.getAllProdutosFornecedor()
    },
    items(newV) {
      if (!this.formulario.id) return

      let produtoModal = Object.assign(
        {},
        newV.find((produto) => produto.id === this.formulario.id),
      )
      if (!produtoModal) return

      this.formulario = { ...produtoModal, ...this.formulario }
    },

    'formulario.listaFotosPendentes': {
      handler(newVal) {
        if (newVal.length == 0) {
          this.abrirModalAddFoto = false
          this.fotoAtivaModalAddFoto = -1
        } else {
          this.abrirModalAddFoto = true
        }
      },
    },
    fotoAtivaModalAddFoto(newV) {
      if (this.fotoAtivaModalAddFoto < 0) return
      const image = document.getElementById('image0')
      this.cropper = new Cropper(image, {
        aspectRatio: 1 / 1,
        viewMode: 1,
        zoomable: false,
        scalable: false,
      })
    },

    abrirModalAddFoto(newV) {
      if (newV === false) {
        this.fotoAtivaModalAddFoto = -1
        if (this.formulario.listaFotosParaCrop.length > 0) {
          this.formulario.listaFotosParaCrop.splice(0, 1)
          this.hookParaCrop()
        }
      }
    },
  },
  filters: {
    upperCase(value) {
      //converte o texto pra upperCase
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    toBold(value) {
      //converte o texto pra negrito
      value = value.toString()
      return value.bold()
    },
    decode_utf8(string) {
      //remove caracter especial
      try {
        string = decodeURIComponent(escape(string))
      } catch (err) {
        console.error(err)
      } finally {
        return string
      }
    },
    moneyMask(value) {
      //converte string em formato moeda
      if (value) {
        var v = value.replace(/\D/g, '')
        v = (v / 100).toFixed(2) + ''
        v = v.replace('.', ',')
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, '$1.$2.$3,')
        v = v.replace(/(\d)(\d{3}),/g, '$1.$2,')
        return 'R$ ' + v
      }
    },
    converteData(data) {
      if (!data) return ''
      data = data.toString()
      return data.substring(0, 10).split('-').reverse().join('/')
    },
  },
})
