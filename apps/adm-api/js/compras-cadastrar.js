var comprasVue = new Vue({
  el: '#comprasVue',
  name: 'Compras',
  vuetify: new Vuetify(),
  data: {
    rules: {
      required: (value) => !!value || 'Campo obrigatório.',
      counter: (value) => (!!value && value.length <= 100) || 'Max 20 characters',
      valorMin(value, min, campo) {
        return (value || '') >= min || `O valor mínimo para ${campo} é ${min}.`
      },
      valorMax(value, max, campo) {
        return (value || '') <= max || `O valor máximo para ${campo} é ${max}.`
      },
    },
    filtros: {
      fornecedor: '',
      id: '',
      data_previsao: '',
      situacao: '',
    },
    produto: {},
    inputsGrade: {
      caixas: 1,
      novaGrade: [],
    },
    menu: false,
    menu2: false,
    produtoSeraExcluido: null,
    dialogExcluirProduto: false,
    dialogConcluirReposicao: false,
    dialogDetalhesProduto: false,
    dialogDetalhesGrade: false,
    dialogDownloadEtiquetas: false,
    buscaFornecedor: '',
    selectFornecedor: [],
    datesEmissao: [],
    datesPrevisao: [],
    fornecedor: false,
    valid: false,
    pesquisaProdutos: '',
    filtroTabelaAdicionados: '',
    listaProdutosDemanda: [],
    headersTabelaDemanda: [
      {
        text: 'ID',
        align: 'center',
        value: 'id',
        sortable: false,
      },
      {
        text: 'Produto',
        align: 'center',
        value: 'caminho',
        sortable: false,
      },
      {
        text: 'Referência',
        value: 'descricao',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Grade',
        value: 'children',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Adicionar',
        value: 'botao',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        filterable: false,
        sortable: false,
      },
    ],
    headerTabelaAdicionados: [
      {
        text: 'ID',
        align: 'center',
        value: 'id',
        sortable: false,
      },
      {
        text: 'Produto',
        align: 'center',
        value: 'descricao',
        sortable: false,
      },
      {
        text: 'Grade',
        value: 'children',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        filterable: false,
        sortable: false,
      },
      {
        text: 'Caixas',
        value: 'estoqueTotal',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Pares',
        value: 'reservadosTotal',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Valor Total',
        value: 'saldoTotal',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Situação',
        value: 'situacao',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Editar',
        value: 'botao',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
      {
        text: 'Excluir',
        value: 'botao',
        align: 'center',
        class: 'subtitle-2 font-weight-bold',
        sortable: false,
      },
    ],
    listaProdutosAdicionados: [],
    chave: '',
    loading: false,
    snackbar: {
      mostrar: false,
      cor: '',
      mensagem: '',
    },
    erroData: false,
    modalConsignado: false,
    nivelAcesso: 0,
    modalFoto: {
      open: false,
      cardTitle: '',
      cardPath: '',
    },
    edicao_fornecedor: false,
  },
  mounted() {
    this.nivelAcesso = $('#cabecalhoVue input[name=nivelAcesso]').val()
    if ($('#cabecalhoVue input[name=nivelAcesso]').val() == 30) {
      this.fornecedor = true
      this.buscaIdFornecedorUsuario($('#cabecalhoVue input[name=userID]').val())
    }
    if ($('#id_compra').val()) {
      this.$set(this.filtros, 'id', $('#id_compra').val())
      this.buscaUmaCompra()
    }
    if ($('#idFornecedor').val() && this.fornecedor) {
      this.$set(this.filtros, 'fornecedor', $('#idFornecedor').val())
      this.buscaDemandaProdutosFornecedor()
    }
  },
  filters: {
    moneyMask(value) {
      //converte string em formato moeda
      if (value) {
        let sinal = Math.sign(parseFloat(value)) == -1 ? '-' : ''
        var v = value.replace(/\D/g, '')
        v = (v / 100).toFixed(2) + ''
        v = v.replace('.', ',')
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, '$1.$2.$3,')
        v = v.replace(/(\d)(\d{3}),/g, '$1.$2,')
        return 'R$ ' + sinal + v
      }
    },
  },
  computed: {
    textEmissao() {
      return this.filtros.data_emissao ? this.converteData(this.filtros.data_emissao) : 'Selecione uma data'
    },
    textPrevisao() {
      this.erroData = false
      return this.filtros.data_previsao ? this.converteData(this.filtros.data_previsao) : 'Selecione uma data'
    },
    novaGrade() {
      //saldo
      let novaGrade = [].map.call(this.produto.children, function (obj) {
        return {
          tamanho: obj.nome_tamanho,
          total: 0,
        }
      })
      this.inputsGrade.novaGrade.forEach((element) => {
        const produto = this.produto.children.find((item) => item.nome_tamanho == element.nome_tamanho)
        grade = {
          tamanho: produto.nome_tamanho,
          total: (element.quantidade ?? 0) * this.inputsGrade.caixas + parseFloat(produto.total),
        }
        key = novaGrade.findIndex((item) => item.tamanho == element.nome_tamanho)
        novaGrade[key] = grade
      })
      return novaGrade
    },
    quantidadeTotal() {
      let total = 0
      this.inputsGrade.novaGrade.forEach((element) => {
        total += parseFloat(element.quantidade ?? 0)
      })

      return total * this.inputsGrade.caixas
    },
    valorTotal() {
      return this.quantidadeTotal * this.produto.valor_custo_produto
    },
    totalCompra() {
      let total = 0
      this.listaProdutosAdicionados.forEach((element) => {
        total += parseFloat(element.valorTotal)
      })

      return total * this.inputsGrade.caixas
    },
    possuiIncompleto() {
      return this.listaProdutosDemanda.some((item) => item.incompleto)
    },
    totalAdicionados() {
      return this.listaProdutosAdicionados.reduce((soma, atual) => soma + parseInt(atual.quantidadeTotal), 0)
    },
  },
  watch: {
    buscaFornecedor(nome) {
      if (!nome || nome === this.filtros.fornecedor || nome?.length < 2) return

      this.buscaFornecedorPeloNome(nome)
    },
    produto(val) {
      if (val.inputsGrade) {
        if (typeof val.inputsGrade.novaGrade == 'object') {
          listaGrades = []
          val.inputsGrade.novaGrade.map((key, index) => {
            listaGrades[index] = key
          })
          val.inputsGrade.novaGrade = listaGrades
        }
        this.inputsGrade = val.inputsGrade
      }
    },
    'filtros.fornecedor'(val) {
      if (!this.pesquisaProdutos) {
        this.filtros.id || this.buscaDemandaProdutosFornecedor()
      }
      this.pesquisaProdutos = ''
    },
    pesquisaProdutos(valor) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        this.buscaDemandaProdutosFornecedor(valor)
        this.bounce = null
      }, 500)
    },
  },
  methods: {
    converteData: function (data) {
      if (!data) return ''
      data = data.toString()
      return data.substring(0, 10).split('-').reverse().join('/')
    },
    enqueueSnackbar(mensagem, cor = 'error') {
      this.snackbar = {
        mostrar: true,
        cor,
        mensagem,
      }
    },
    calcula(value, index) {
      this.inputsGrade.novaGrade = this.inputsGrade.novaGrade.map((grade, indexTree) => {
        grade.quantidade = indexTree == index ? value : grade.quantidade
        return grade
      })
    },
    async buscaFornecedorPeloNome(nome) {
      try {
        this.loading = true
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
      } finally {
        this.loading = false
      }
    },
    cadastroIncompleto() {
      window.location.href = 'fornecedores-produtos.php'
    },
    async buscaIdFornecedorUsuario(idFornecedor) {
      $.ajax({
        type: 'POST',
        url: 'controle/indexController.php',
        dataType: 'json',
        data: {
          action: 'buscaIdFornecedorUsuario',
          userId: idFornecedor,
        },
      }).done(
        function (json) {
          if (json.status == 'ok') {
            this.$set(this.filtros, 'fornecedor', json.idFornecedor)
            this.filtros.id || this.buscaDemandaProdutosFornecedor()
          } else {
            console.log(json.mensagem)
          }
        }.bind(this),
      )
    },
    async buscaUmaCompra() {
      try {
        this.loading = true
        this.enqueueSnackbar('Buscando dados...', 'blue darken-1')
        this.listaProdutosDemanda = []
        this.listaProdutosAdicionados = []
        const resp = await api.get(`api_administracao/compras/busca_uma_compra/${this.filtros.id}`)
        this.snackbar = false
        this.listaProdutosDemanda = resp.data.listaDemandaProdutos
        this.listaProdutosAdicionados = resp.data.listaProdutosAdicionados
        this.edicao_fornecedor = resp.data.edicao_fornecedor == 1
        this.filtros.fornecedor = resp.data.id_fornecedor
        this.filtros.situacao = resp.data.situacao
        this.filtros.data_previsao = resp.data.data_previsao
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loading = false
      }
    },
    async buscaDemandaProdutosFornecedor(pesquisa = '') {
      this.listaProdutosDemanda = []
      // this.listaProdutosAdicionados = [];
      this.loading = true
      this.enqueueSnackbar('Buscando dados...', 'blue darken-1')
      try {
        const parametros = new URLSearchParams({
          pesquisa,
        })
        await MobileStockApi(
          `api_administracao/compras/busca_lista_produtos_reposicao_interna/${this.filtros.fornecedor}?${parametros}`,
        )
          .then((resp) => resp.json())
          .then((resp) => {
            this.snackbar = false
            if (resp.status) {
              this.listaProdutosDemanda = Object.keys(resp.data).map((key) => resp.data[key])
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loading = false
      }
    },
    async buscaEtiquetasColetivas() {
      this.loading = true
      try {
        await MobileStockApi(`api_administracao/compras/busca_etiqueta_coletiva_compra/${this.filtros.id}`)
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              const etiquetasColetivas = JSON.stringify(resp.data)
              const filename = `etiqueta_coletiva_compra_${this.filtros.id}`
              const blob = new Blob([etiquetasColetivas], {
                type: 'json',
              })
              saveAs(blob, `${filename}.json`)
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loading = false
      }
    },
    async buscaEtiquetasUnitarias() {
      this.loading = true
      try {
        await MobileStockApi(`api_administracao/compras/busca_etiqueta_unitaria_compra/${this.filtros.id}`)
          .then((resp) => resp.json())
          .then((resp) => {
            if (resp.status) {
              const etiquetasUnitarias = JSON.stringify(resp.data)
              const filename = `etiqueta_unitaria_compra_${this.filtros.id}`
              const blob = new Blob([etiquetasUnitarias], {
                type: 'json',
              })
              saveAs(blob, `${filename}.json`)
            } else {
              throw new Error(resp.message)
            }
          })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.loading = false
      }
    },
    async salvarCompra() {
      try {
        if (this.$refs.form.validate()) {
          this.loading = true
          this.$set(this.produto, 'inputsGrade', this.inputsGrade)
          this.$set(this.produto, 'quantidadeTotal', this.quantidadeTotal)
          this.$set(this.produto, 'valorTotal', this.valorTotal)
          this.dialogDetalhesProduto = false
          let compra = this.filtros
          compra.produtos = this.produto

          const resposta = await api.post('api_administracao/compras/salva_compra', {
            id_compra: compra.id,
            id_fornecedor: compra.fornecedor,
            data_previsao: compra.data_previsao,
            produtos: compra.produtos,
          })
          this.filtros.id = resposta.data.id_compra
          this.listaProdutosAdicionados = resposta.data.listaProdutosAdicionados
          this.enqueueSnackbar('Produto adicionado.', 'green')
          this.limpaModal()
        }
      } catch (error) {
        this.enqueueSnackbar(error.message)
      } finally {
        this.loading = false
      }
    },
    limpaModal() {
      this.inputsGrade = {
        caixas: 1,
        novaGrade: [],
      }
      this.$refs.form.resetValidation()
      this.produto = {}
      this.chave = ''
      this.editando = false
    },
    adicionarProduto(item, index) {
      if (this.filtros.data_previsao) {
        this.produto = Object.assign({}, item)
        item.children.forEach((grade, index) => {
          this.inputsGrade.novaGrade[index] = {
            nome_tamanho: grade.nome_tamanho,
            quantidade: null,
          }
        })
        this.chave = index
        this.dialogDetalhesProduto = true
      } else {
        this.erroData = true
        this.$vuetify.goTo(this.$refs.dataEmisao, {
          duration: 300,
          offset: -100,
          easing: 'linear',
        })
        this.enqueueSnackbar('Selecione a data de previsão')
      }
    },
    queroExcluirProduto(produto, index) {
      this.produtoSeraExcluido = {
        id_produto: produto.id,
        sequencia: produto.sequencia,
        index,
      }
      this.dialogExcluirProduto = true
    },
    async excluirProduto() {
      try {
        this.loading = true

        const parametros = new URLSearchParams({
          id_produto: this.produtoSeraExcluido.id_produto,
          sequencia: this.produtoSeraExcluido.sequencia,
        })
        await api.delete(`api_administracao/compras/remove_item/${this.filtros.id}?${parametros}`)
        this.listaProdutosAdicionados.splice(this.produtoSeraExcluido.index, 1)

        this.enqueueSnackbar('Produto excluído com sucesso', 'success')
        this.produtoSeraExcluido = null
        this.dialogExcluirProduto = false

        if (!this.listaProdutosAdicionados.length) {
          this.sair()
        }
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao excluir produto')
      } finally {
        this.loading = false
      }
    },
    sair() {
      window.location.href = 'compras.php'
    },
    baixarTodasEtiquetas() {
      ajaxs = [this.buscaEtiquetasColetivas(), this.buscaEtiquetasUnitarias()]
      Promise.all(ajaxs).then((values) => {
        this.sair()
      })
    },
    visualizarFoto(item) {
      this.modalFoto.cardTitle = item.descricao
      this.modalFoto.cardPath = item.caminho
      this.modalFoto.open = true
    },
    editar(item) {
      this.editando = true
      produto = jQuery.extend(true, {}, item)
      produto.children.forEach((element, index) => {
        element.total = element.total - produto.inputsGrade.novaGrade[index].quantidade * produto.inputsGrade.caixas
        element.previsao =
          element.previsao - produto.inputsGrade.novaGrade[index].quantidade * produto.inputsGrade.caixas
      })
      this.produto = produto
      this.dialogDetalhesProduto = true
    },
    async concluiReposicao() {
      try {
        this.loading = true

        await api.patch(`api_administracao/compras/concluir/${this.filtros.id}`)

        this.enqueueSnackbar('Reposição concluída com sucesso', 'success')
        this.buscaUmaCompra()
        this.dialogConcluirReposicao = false
        this.dialogDownloadEtiquetas = true
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao concluir reposição')
      } finally {
        this.loading = false
      }
    },
  },
})
