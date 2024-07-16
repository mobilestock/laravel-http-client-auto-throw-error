import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#cadastrar-reposicao',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),

  data() {
    return {
      editando: false,
      atualizavel: false,
      backupInputGrade: [],
      delay: null,
      verificaFornecedor: false,
      buscaFornecedor: '',
      filtroCarrinho: '',
      isLoading: false,
      isLoadingFinaliza: false,
      isLoadingFornecedor: false,
      estaBuscando: false,
      erroData: false,
      tentandoReporMais: false,
      idReposicao: new URLSearchParams(window.location.search).get('id_reposicao') || 0,
      dataHoje: new Date().toLocaleString('pt-br', { dateStyle: 'full' }),
      dataFormatada: '',
      rules: {
        valorMin: (valor, min, campo) => (!!valor && parseInt(valor) >= min) || `O valor mínimo para ${campo} é ${min}`,
        valorMinEMax: (valor, min, max, campo) =>
          (!!valor && parseInt(valor) >= min && parseInt(valor) <= max) || `Zerando reposição para ${campo}`,
      },
      snackbar: {
        ativar: false,
        texto: '',
        cor: 'error',
      },
      filtros: {
        fornecedor: '',
        idFornecedor: '',
        situacao: '',
      },
      filtrosProdutosDisponiveis: {
        pagina: 1,
        pesquisa: '',
        maisPags: false,
      },
      disponiveisRepor: [],
      produtoEscolhido: {
        idProduto: 0,
        valorUnitario: 0,
        quantidadeTotal: 0,
        quantidadePermitida: 0,
        valorTotal: 0,
        caixas: 1,
        fotoProduto: '',
        nomeComercial: '',
        permitidoManualmente: false,
        gradeNova: [],
      },
      inputGrade: {
        caixas: 1,
        novaGrade: [],
      },
      headersProdutosDisponiveis: [
        this.itemGrade('ID produto', 'id', true),
        this.itemGrade('Produto', 'foto'),
        this.itemGrade('Estoque atual', 'grades'),
        this.itemGrade('Adicionar', 'adicionar_carrinho'),
      ],
      headersProdutosCarrinho: [
        this.itemGrade('ID produto', 'id_produto'),
        this.itemGrade('Produto', 'foto'),
        this.itemGrade('Grade', 'grades'),
        this.itemGrade('Pares', 'quantidadeTotal'),
        this.itemGrade('Valor Total', 'valorTotalFormatado'),
        this.itemGrade('Situação', 'situacao'),
        this.itemGrade('Editar', 'editar'),
        this.itemGrade('Excluir', 'excluir'),
      ],
      carrinhoRepor: [],
      qtdProdutosCarrinho: 0,
      listaFornecedores: [],
      modalCriarReposicao: false,
      modalCancelarCompra: false,
      modalReposicao: false,
      modalFotos: false,
    }
  },

  methods: {
    itemGrade(campo, valor, ordernavel = false) {
      return {
        text: campo,
        value: valor,
        sortable: ordernavel,
        align: 'center',
      }
    },

    converteData(data) {
      if (!data) return ''
      const dataFormatada = data.toString().substring(0, 10).split('-').reverse().join('/')

      return dataFormatada
    },

    adicionarProduto(item, editar = false) {
      if (editar) {
        this.produtoEscolhido = {
          idProduto: item.id_produto,
          valorUnitario: item.valorUnitario,
          valorTotal: item.valorTotal,
          quantidadeTotal: item.quantidadeTotal,
          quantidadePermitida: item.quantidadePermitida,
          permitidoManualmente: item.permitidoManualmente,
          fotoProduto: item.foto,
          nomeComercial: item.nomeComercial,
        }

        const novaGrade = item.grades.map((grade) => ({ ...grade, quantidadeRemover: 0 }))
        this.inputGrade = {
          caixas: item.caixas,
          novaGrade,
        }
        const backup = JSON.stringify(novaGrade)
        this.backupInputGrade = JSON.parse(backup)
        this.modalReposicao = true

        return
      }
      this.produtoEscolhido = {
        idProduto: item.id_produto,
        valorUnitario: item.valor_custo_produto,
        quantidadeTotal: 0,
        quantidadePermitida: item.quantidade_permitido_repor,
        valorTotal: 0,
        caixas: 1,
        fotoProduto: item.foto,
        nomeComercial: item.nome_comercial,
        gradeNova: item.grades.map((grade, index) => ({
          key: index,
          nomeTamanho: grade.nome_tamanho,
          emEstoque: grade.estoque,
          jaPrevistos: grade.previsao,
          reservados: grade.reservado,
          total: grade.total,
          novoEstoque: 0,
          novoSaldo: 0,
        })),
      }
      this.inputGrade = {
        caixas: 1,
        novaGrade: item.grades.map((grade, index) => ({
          key: index,
          nomeTamanho: grade.nome_tamanho,
          emEstoque: grade.estoque,
          jaPrevistos: grade.previsao,
          reservados: grade.reservado,
          total: grade.total,
          novoEstoque: 0,
          novoSaldo: 0,
        })),
      }
      this.modalReposicao = true
    },

    filtroPesquisaCarrinho(_valorColuna, pesquisa, valoresLinha) {
      const resultado = Object.values(valoresLinha).some(
        (campo) => campo && campo.toString().toLowerCase().includes(pesquisa.toLowerCase()),
      )

      return resultado
    },

    removerDoCarrinho(idProduto) {
      this.carrinhoRepor = this.carrinhoRepor.filter((produto) => produto.id_produto !== idProduto)
      if (this.carrinhoRepor.length === 0) {
        this.modalCancelarCompra = true
        return
      }
      if (this.disponiveisRepor.length) {
        const key = this.disponiveisRepor.findIndex((produto) => produto.id_produto === idProduto)
        if (key >= 0) {
          document.getElementById(`adicionar-${idProduto}`).disabled = false
        }
      }
    },

    debounce(funcao, atraso) {
      clearTimeout(this.delay)
      this.delay = setTimeout(() => {
        funcao()
        this.delay = null
      }, atraso)
    },

    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },

    async buscaFornecedorPeloNome(nome) {
      try {
        this.isLoading = true
        const parametros = new URLSearchParams({
          pesquisa: nome,
        })
        const resposta = await api.get(`api_administracao/fornecedor/busca_fornecedores?${parametros}`)

        this.listaFornecedores = resposta.data.map((fornecedor) => ({
          ...fornecedor,
          idFornecedor: fornecedor.id,
          nome: `${fornecedor.id} - ${fornecedor.nome}`,
        }))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar fornecedores')
      } finally {
        this.isLoading = false
      }
    },

    async buscaProdutosDisponiveis() {
      try {
        if (this.isLoading) return

        this.isLoading = true
        const parametros = new URLSearchParams({
          id_fornecedor: this.filtros.idFornecedor,
          pagina: this.filtrosProdutosDisponiveis.pagina || 1,
          pesquisa: this.filtrosProdutosDisponiveis.pesquisa || '',
        })

        const response = await api.get(`api_administracao/reposicoes/produtos_reposicao_interna?${parametros}`)

        this.disponiveisRepor = response.data.produtos
        this.filtrosProdutosDisponiveis.maisPags = response.data.mais_pags
        this.qtdProdutosCarrinho = 0
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar produtos disponíveis')
      } finally {
        this.isLoading = false
      }
    },

    limpaModal() {
      this.modalReposicao = false
      this.tentandoReporMais = false
      this.produtoEscolhido = {
        idProduto: 0,
        fotoProduto: '',
        nomeComercial: '',
        valorUnitario: 0,
        valorTotal: 0,
        quantidadeTotal: 0,
        quantidadePermitida: 0,
        caixas: 1,
        gradeNova: [],
        permitidoManualmente: false,
      }
      this.inputGrade = {
        caixas: 1,
        novaGrade: [],
      }
    },

    voltar() {
      window.location.href = 'reposicoes.php'
    },

    calculaValorEmReais(valor = 0) {
      const reais = formataMoeda(valor)
      return reais
    },

    corNovoSaldo(total = 0, novoEstoque = 0, caixas = 0) {
      const quantidadeLiquida = total + novoEstoque * caixas
      if (quantidadeLiquida === 0) {
        return 'text-blue'
      } else if (quantidadeLiquida > 0) {
        return 'text-green'
      } else {
        return 'text-red'
      }
    },

    adicionarAoCarrinho() {
      const quantidadeTotal =
        this.inputGrade.novaGrade.reduce((total, grade) => total + parseInt(grade.novoEstoque || 0), 0) *
        this.inputGrade.caixas

      switch (true) {
        case quantidadeTotal < 1:
          this.tentandoReporMais = false
          this.enqueueSnackbar('Nenhuma quantidade para repor cadastrada')

          return
        case quantidadeTotal > this.produtoEscolhido.quantidadePermitida && !this.produtoEscolhido.permitidoManualmente:
          this.tentandoReporMais = true
          this.enqueueSnackbar('Você está tentando ultrapassar quantidade permitida para reposição desse produto')

          return
        default:
          this.tentandoReporMais = false
          if (!this.idReposicao) {
            document.getElementById(`adicionar-${this.produtoEscolhido.idProduto}`).disabled = true
          }

          let newProduto = {
            id_produto: this.produtoEscolhido.idProduto,
            nomeComercial: this.produtoEscolhido.nomeComercial,
            valorUnitario: this.produtoEscolhido.valorUnitario,
            foto: this.produtoEscolhido.fotoProduto,
            caixas: this.inputGrade.caixas,
            quantidadeTotal: this.produtoEscolhido.quantidadeTotal,
            quantidadePermitida: this.produtoEscolhido.quantidadePermitida,
            permitidoManualmente: this.produtoEscolhido.permitidoManualmente,
            valorTotal: this.produtoEscolhido.valorTotal,
            valorTotalFormatado: this.calculaValorEmReais(this.produtoEscolhido.valorTotal),
            situacao: 'Em aberto',
            situacaoId: 1,
            grades: this.inputGrade.novaGrade,
            key: this.carrinhoRepor.length,
          }

          const produtos = this.carrinhoRepor?.map((produto) => produto.id_produto)

          if (produtos.includes(newProduto.id_produto)) {
            this.carrinhoRepor = this.carrinhoRepor?.map((produto, index) => {
              if (produto.id_produto !== newProduto.id_produto) return produto
              newProduto.key = index

              return newProduto
            })
            this.enqueueSnackbar('Produto substituído no carrinho. Finalize a reposição para salvá-la', 'primary')
          } else {
            this.carrinhoRepor.push(newProduto)
            this.enqueueSnackbar('Produto adicionado ao carrinho. Finalize a reposição para salvá-la', 'success')
          }

          this.limpaModal()
          break
      }
    },

    async criarReposicao() {
      try {
        if (this.isLoadingFinaliza) return

        this.isLoadingFinaliza = true

        const dados = {
          id_fornecedor: this.filtros.idFornecedor,
          produtos: this.carrinhoRepor.map((produto) => ({
            id_produto: produto.id_produto,
            preco_custo_unitario: produto.valorUnitario,
            grades: produto.grades.map((grade) => ({
              nome_tamanho: grade.nomeTamanho,
              quantidade_total: grade.novoEstoque * produto.caixas,
            })),
          })),
        }

        await api.post('api_administracao/reposicoes', dados)

        this.enqueueSnackbar('Reposição criada com sucesso', 'success')
        this.voltar()
      } catch (error) {
        this.isLoadingFinaliza = false
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao criar reposição')
      }
    },

    async atualizarReposicao() {
      try {
        if (this.isLoadingFinaliza) return
        this.isLoadingFinaliza = true

        const dados = {
          id_fornecedor: this.filtros.idFornecedor,
          produtos: this.carrinhoRepor.map((produto) => ({
            id_produto: produto.id_produto,
            preco_custo_unitario: produto.valorUnitario,
            grades: produto.grades.map((grade) => ({
              id_grade: grade.idGrade,
              nome_tamanho: grade.nomeTamanho,
              quantidade_total: grade.novoEstoque,
              quantidade_falta_entregar: grade.faltaEntregar,
              quantidade_remover: grade.quantidadeRemover || 0,
            })),
          })),
        }
        await api.put(`api_administracao/reposicoes/${this.idReposicao}`, dados)

        this.enqueueSnackbar('Reposição atualizada com sucesso', 'success')
        this.voltar()
      } catch (error) {
        this.isLoadingFinaliza = false
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar reposição')
      }
    },

    verificaSeAtualizavel() {
      this.atualizavel = this.inputGrade.novaGrade.some((grade) => grade.quantidadeRemover > 0)
    },

    calculaFaltaEntregar(grade) {
      const backup = this.backupInputGrade.find((item) => item.nomeTamanho === grade.nomeTamanho)
      grade.quantidadeRemover = Math.min(parseInt(grade.quantidadeRemover || 0), backup.faltaEntregar)
      const novoEstoque = backup.novoEstoque - Math.abs(grade.quantidadeRemover)
      const faltaEntregar = backup.faltaEntregar - Math.abs(grade.quantidadeRemover)
      if (novoEstoque < 0 || faltaEntregar < 0) return

      this.inputGrade.novaGrade = this.inputGrade.novaGrade.map((item) => {
        if (item.nomeTamanho === grade.nomeTamanho) {
          item.novoEstoque = novoEstoque
          item.faltaEntregar = faltaEntregar
        }
        return item
      })
    },

    fecharModalEditarReposicao(gradesAtualizadas) {
      this.carrinhoRepor = this.carrinhoRepor.map((produto) => {
        produto.grades = produto.grades.map((grade) => {
          const gradeEncontrada = gradesAtualizadas.find((gradeAtualizada) => gradeAtualizada.idGrade === grade.idGrade)
          return gradeEncontrada
        })

        produto.valorTotal = produto.grades.reduce((acc, grade) => acc + grade.novoEstoque * produto.valorUnitario, 0)
        produto.valorTotalFormatado = this.calculaValorEmReais(produto.valorTotal)

        return produto
      })
      this.limpaModal()
    },

    async buscaProdutosReposicao() {
      try {
        this.isLoading = true
        const resposta = await api.get(`api_administracao/reposicoes/${this.idReposicao}`)

        this.filtros = {
          idFornecedor: resposta.data.id_fornecedor,
          situacao: resposta.data.situacao,
        }

        this.carrinhoRepor = resposta.data.produtos.map((produto) => ({
          id_produto: produto.id_produto,
          caixas: 1,
          situacao: produto.situacao_grade,
          foto: produto.foto,
          valorUnitario: produto.preco_custo_produto,
          quantidadeTotal: produto.quantidade_total_grade,
          valorTotal: produto.preco_total_grade,
          valorTotalFormatado: this.calculaValorEmReais(produto.preco_total_grade),
          grades: produto.grades.map((grade) => ({
            idGrade: grade.id_grade,
            nomeTamanho: grade.nome_tamanho,
            emEstoque: grade.quantidade_em_estoque,
            novoEstoque: grade.quantidade_total,
            faltaEntregar: grade.quantidade_falta_entregar,
            editavel: grade.quantidade_falta_entregar > 0,
          })),
        }))

        this.qtdProdutosCarrinho = this.carrinhoRepor.length
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar produtos da reposição')
      } finally {
        this.isLoading = false
        this.modalCancelarCompra = false
      }
    },

    async buscaEtiquetasUnitarias() {
      this.isLoading = true
      try {
        const resposta = await api.get(`api_administracao/reposicoes/etiquetas_unitarias/${this.idReposicao}`)

        const etiquetasUnitarias = JSON.stringify(resposta.data)
        const filename = `etiquetas_unitarias_reposicao_${this.idReposicao}`
        const blob = new Blob([etiquetasUnitarias], {
          type: 'json',
        })
        saveAs(blob, `${filename}.json`)
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao imprimir etiquetas unitárias')
      } finally {
        this.isLoading = false
      }
    },
  },

  computed: {
    valorTotal() {
      const valorUnitario = this.produtoEscolhido.valorUnitario
      const qtdTotal =
        this.inputGrade.novaGrade.reduce((total, grade) => total + parseInt(grade.novoEstoque), 0) *
        this.inputGrade.caixas
      const valorTotal = qtdTotal * valorUnitario
      this.produtoEscolhido.valorTotal = valorTotal

      return valorTotal
    },

    totalValorReposicao() {
      const total = this.carrinhoRepor?.reduce((total, produto) => (total += parseFloat(produto.valorTotal)), 0)
      return this.calculaValorEmReais(total)
    },

    quantidadeEstoqueTotal() {
      const qtdTotal =
        this.inputGrade.novaGrade.map((grade) => parseInt(grade.novoEstoque)).reduce((a, b) => a + b, 0) *
        this.inputGrade.caixas
      this.produtoEscolhido.quantidadeTotal = qtdTotal

      return qtdTotal
    },
  },

  mounted() {
    const nivelAcesso = $('#cabecalhoVue input[name=nivelAcesso]').val()
    if (nivelAcesso == 30) {
      this.verificaFornecedor = true
      this.filtros.idFornecedor = $('#cabecalhoVue input[name=userIDCliente]').val()
    }

    switch (true) {
      case !this.verificaFornecedor && !this.idReposicao:
        this.filtros.situacao = 1
        break
      case !this.idReposicao:
        this.filtros.situacao = 1
        this.buscaProdutosDisponiveis()
        break
      case !!this.idReposicao:
        this.buscaProdutosReposicao()
        this.editando = true
        break
    }
  },

  watch: {
    buscaFornecedor(nome) {
      this.debounce(() => {
        if (!nome || nome === this.filtros.fornecedor || nome?.length < 2) return

        this.buscaFornecedorPeloNome(nome)
      }, 750)
    },

    'filtros.idFornecedor'() {
      if (!this.verificaFornecedor && this.filtros.idFornecedor > 0) {
        this.buscaProdutosDisponiveis()
      }
    },

    estaBuscando(valor) {
      if (valor) {
        this.buscaFornecedorPorNome(this.buscaFornecedor)
      }
    },
    'filtrosProdutosDisponiveis.pagina'() {
      this.buscaProdutosDisponiveis()
    },
    'filtrosProdutosDisponiveis.pesquisa'() {
      this.debounce(() => this.buscaProdutosDisponiveis(), 500)
    },
  },
})
