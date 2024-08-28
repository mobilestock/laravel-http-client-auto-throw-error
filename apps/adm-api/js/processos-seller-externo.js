import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

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
      carregandoConferir: false,
      carregandoRetirarDevolucao: false,
      modalConfirmarBipagem: false,
      modalRegistrarUsuario: false,
      modalAlertaUsuarioNaoEncontrado: false,

      numeroFrete: null,

      taxaDevolucaoProdutoErrado: null,

      listaColaboradores: [],
      listaColaboradoresFrete: [],
      areaAtual: null,
      colaboradorEscolhido: null,
      pesquisa: null,
      pesquisaFrete: '',
      pesquisaConferente: null,
      input_qrcode: null,

      CONFERENCIA_items: [],
      CONFERENCIA_itens_bipados: [],
      CONFERENCIA_headers: [
        { text: 'Id Produto', value: 'id_produto', width: '5%', height: '8rem', sortable: false },
        { text: 'Produto', value: 'nome_produto', width: 'auto', height: '8rem', sortable: false },
        { text: 'Tamanho', value: 'tamanho', width: '10%', align: 'center' },
        { text: 'Bipado?', value: 'bipado', align: 'center' },
        { text: 'Cliente', value: 'nome_cliente', width: '15%' },
        { text: 'Tempo aguardando', value: 'dias_na_separacao' },
        { text: '', value: 'data-table-select' },
      ],

      modalProdutosDevolucaoAguardando: {
        exibir: false,
        dados: [],
      },

      modalErro: {
        exibir: false,
        mensagem: '',
      },

      snackbar: {
        mostra: false,
        texto: '',
        cor: 'green',
      },
      produtosSelecionados: [],

      conferencia: {
        colaboradorEscolhidoConfirmaBipagem: null,
        possivelConfirmar: false,
        nomeUsuario: null,
        telefoneUsuario: null,
      },
    }
  },

  methods: {
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },

    alteraAreaAtual(areaNova) {
      this.areaAtual = areaNova
      let tamanhoConfig
      let produtoConfig
      if (areaNova === 'CONFERENCIA_FORNECEDOR') {
        this.buscaItemsSeparacaoExterna()
        tamanhoConfig = { text: 'Tamanho', width: '10%' }
        produtoConfig = { text: 'Produto' }
      } else {
        this.buscaFretesDisponiveis()
        tamanhoConfig = { text: 'Telefone', width: '15%' }
        produtoConfig = { text: 'Destinatário' }
      }

      this.CONFERENCIA_headers = this.CONFERENCIA_headers.map((header) => {
        if (header.value === 'tamanho') {
          header = { ...header, ...tamanhoConfig }
        } else if (header.value === 'nome_produto') {
          header = { ...header, ...produtoConfig }
        }

        return header
      })
    },

    async buscaItemsSeparacaoExterna() {
      if (!this.colaboradorEscolhido) return
      try {
        this.loading = true
        const parametros = new URLSearchParams({
          id_colaborador: this.colaboradorEscolhido.id,
        })

        const resposta = await api.get(`api_estoque/separacao/produtos?${parametros}`)
        this.CONFERENCIA_items = resposta.data
        this.produtosSelecionados = resposta.data
        this.CONFERENCIA_itens_bipados = []
      } catch (error) {
        this.mostrarErro(error?.response?.data?.message || error?.message || 'Erro ao buscar os produtos')
      } finally {
        this.loading = false
      }
    },

    async buscaFretesDisponiveis() {
      if (!this.colaboradorEscolhido || !!this.numeroFrete) return

      try {
        this.loading = true
        const resposta = await api.get(`api_estoque/separacao/etiquetas_frete`, {
          params: {
            id_colaborador: this.colaboradorEscolhido.id,
          },
        })
        this.CONFERENCIA_items = resposta.data
        this.produtosSelecionados = resposta.data
        this.CONFERENCIA_itens_bipados = []
      } catch (error) {
        this.mostrarErro(error?.response?.data?.message || error?.message || 'Erro ao buscar os produtos')
      } finally {
        this.loading = false
      }
    },

    async baixarEtiqueta() {
      if (this.loading) return
      try {
        this.loading = true
        const uuidProdutos = this.produtosSelecionados.map((item) => item.uuid)
        const resposta = await api.post('api_estoque/separacao/produtos/etiquetas', {
          uuids: uuidProdutos,
        })

        window.postMessage(
          {
            type: 'zebra_print_label',
            zpl: [...resposta.data],
            url: 'http://192.168.0.124/pstprnt',
          },
          '*',
        )

        this.produtosSelecionados = []
        this.focoInput()
      } catch (error) {
        this.mostrarErro(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao imprimir as etiquetas!',
        )
      } finally {
        this.loading = false
      }
    },

    biparItens(uuidProduto) {
      const item = this.CONFERENCIA_items.findIndex((item) => item.uuid === uuidProduto)

      const agora = formataDataHora(new Date())

      if (item !== -1) {
        this.CONFERENCIA_items[item].bipado = true
        this.CONFERENCIA_items[item].data_bipagem = agora
        this.CONFERENCIA_itens_bipados = [...this.CONFERENCIA_itens_bipados, this.CONFERENCIA_items[item]]

        this.CONFERENCIA_items.sort((a, b) => {
          if (a.bipado && !b.bipado) {
            return -1
          }
          if (!a.bipado && b.bipado) {
            return 1
          }
          return 0
        })

        this.enqueueSnackbar('Item bipado com sucesso!')
      }
    },

    async confirmarItens() {
      this.carregandoConferir = true
      let erroIdentificado = false

      for (let indexItemBipado = 0; indexItemBipado < this.CONFERENCIA_itens_bipados.length; indexItemBipado++) {
        const produto = this.CONFERENCIA_itens_bipados[indexItemBipado]
        try {
          let requisicao = null
          if (this.areaAtual === 'CONFERENCIA_FRETE') {
            requisicao = {
              id_usuario: this.conferencia.colaboradorEscolhidoConfirmaBipagem.id_usuario,
            }
          }
          await api.post(`api_estoque/produtos_logistica/conferir/${produto.uuid}`, requisicao)
          const indexItensTotais = this.CONFERENCIA_items.findIndex((item) => item.uuid === produto.uuid)
          this.CONFERENCIA_items.splice(indexItensTotais, 1)
          await this.delay(100)
        } catch (error) {
          erroIdentificado = true
          this.mostrarErro(
            error?.response?.data?.message ||
              error?.message ||
              `Erro ao conferir ${produto.id_produto} ${produto.nome_tamanho}`,
          )
        }
      }

      if (this.areaAtual === 'CONFERENCIA_FRETE') {
        this.conferencia = {
          colaboradorEscolhidoConfirmaBipagem: null,
          possivelConfirmar: false,
          nomeUsuario: null,
          telefoneUsuario: null,
        }
      }

      this.carregandoConferir = false
      this.loading = false
      if (!erroIdentificado) {
        await this.buscarDevolucoesAguardando()
        this.CONFERENCIA_itens_bipados = []
      }
      this.modalConfirmarBipagem = false

      if (this.modalProdutosDevolucaoAguardando.dados.length === 0) {
        this.enqueueSnackbar('Itens conferidos com sucesso! Recarregando a página em segundos.')
        await this.delay(3000)
        location.reload()
      }
    },

    focoInput() {
      setTimeout(() => {
        this.$nextTick(() => {
          const inputElement = document.querySelector('input#inputBipagem')
          if (inputElement) inputElement.focus()
        })
      }, 500)
    },

    async buscarDevolucoesAguardando() {
      try {
        const resultado = await api.get(
          `api_administracao/fornecedor/busca_produtos_defeituosos/${this.colaboradorEscolhido.id}`,
        )

        if (resultado.data.length) {
          this.modalProdutosDevolucaoAguardando.dados = resultado.data
          this.modalProdutosDevolucaoAguardando.exibir = true
        } else {
          this.modalProdutosDevolucaoAguardando.dados = []
          this.modalProdutosDevolucaoAguardando.exibir = false
        }
      } catch (error) {
        this.mostrarErro(
          error?.response?.data?.message ||
            error?.message ||
            'Ocorreu um erro ao buscar as devoluções aguardando retirada',
        )
      }
    },

    async retirarDevolucaoDefeito(uuidProduto) {
      try {
        this.carregandoRetirarDevolucao = true
        await api.patch(`api_administracao/fornecedor/retirar_produto_defeito/${uuidProduto}`)
        this.modalProdutosDevolucaoAguardando.dados = this.modalProdutosDevolucaoAguardando.dados.filter(
          (item) => item.uuid !== uuidProduto,
        )
        if (this.modalProdutosDevolucaoAguardando.dados.length === 0) {
          this.modalProdutosDevolucaoAguardando.exibir = false
        }
        await this.buscarDevolucoesAguardando()
      } catch (error) {
        this.mostrarErro(
          error?.response?.data?.message || error?.message || 'Ocorreu um erro ao retirar a devolução de defeito',
        )
      } finally {
        this.carregandoRetirarDevolucao = false
      }
    },

    buscarTaxaProdutoErrado() {
      api
        .get('api_administracao/configuracoes/busca_taxa_produto_errado')
        .then((res) => {
          this.taxaDevolucaoProdutoErrado = formataMoeda(res.data)
        })
        .catch((error) => {
          this.mostrarErro(
            error?.response?.data?.message ||
              error.message ||
              'Falha ao buscar a taxa de devolução de produto enviado errado',
          )
        })
    },

    mostrarErro(mensagem) {
      this.modalErro.mensagem = mensagem
      this.modalErro.exibir = true
    },

    fecharModalErro() {
      this.modalErro.exibir = false
      this.focoInput()
    },

    enqueueSnackbar(mensagem, cor = 'success') {
      this.snackbar = {
        mostra: true,
        texto: mensagem,
        cor,
      }
    },

    async delay(ms) {
      return new Promise((resolve) => setTimeout(resolve, ms))
    },

    async cadastroRapidoUsuario() {
      try {
        this.loading = true
        const requisicao = {
          telefone: this.conferencia.telefoneUsuario,
          nome: this.conferencia.nomeUsuario,
          id_cidade: 19479,
        }
        await api.post('api_cliente/cliente/', requisicao)
        this.fecharModais()
      } catch (error) {
        this.mostrarErro(error?.response?.data?.message || error?.message || 'Erro ao cadastrar o usuário')
      } finally {
        this.loading = false
      }
    },

    fecharModais() {
      this.modalRegistrarUsuario = false
      this.modalAlertaUsuarioNaoEncontrado = false
    },

    fazerPesquisa(texto) {
      if (this.loading || texto?.length <= 2 || !texto) return
      this.debounce(async () => {
        this.loading = true
        const parametros = new URLSearchParams({
          pesquisa: texto,
        })

        if (this.numeroFrete !== null && this.colaboradorEscolhido !== null) {
          this.numeroFrete = null
          this.CONFERENCIA_items = []
          this.CONFERENCIA_itens_bipados = []
          this.produtosSelecionados = []
          this.areaAtual = null
        }

        api
          .get(`api_administracao/cadastro/colaboradores_processo_seller_externo?${parametros}`)
          .then((res) => {
            this.listaColaboradores = (res.data || []).map((colaborador) => {
              colaborador.descricao = `${colaborador.razao_social} - ${colaborador.telefone}`
              return colaborador
            })
          })
          .catch((err) => {
            this.mostrarErro(err?.response?.data?.message || err?.message || 'Ocorreu um erro ao pesquisar seller')
          })
          .finally(() => (this.loading = false))
      }, 800)
    },

    fazerPesquisaConferente(texto) {
      if (this.loading || texto?.length <= 2 || !texto) return
      this.debounce(async () => {
        this.loading = true
        const parametros = new URLSearchParams({
          pesquisa: texto,
        })

        api
          .get(`api_administracao/cadastro/colaboradores_processo_seller_externo?${parametros}`)
          .then((res) => {
            this.listaColaboradoresFrete = (res.data || []).map((colaborador) => {
              colaborador.descricao = `${colaborador.razao_social} - ${colaborador.telefone}`
              return colaborador
            })
            this.modalAlertaUsuarioNaoEncontrado = !res.data?.length && this.modalConfirmarBipagem
            if (texto !== null && texto.length >= 11) {
              if (/^\d+$/.test(texto)) {
                this.conferencia.telefoneUsuario = formataTelefone(texto)
              } else if (/^[a-zA-Z\s]*$/.test(texto)) {
                this.conferencia.nomeUsuario = texto
              }
            }
            return
          })
          .catch((err) => {
            this.mostrarErro(err?.response?.data?.message || err?.message || 'Ocorreu um erro ao pesquisar conferente')
          })
          .finally(() => (this.loading = false))
      }, 800)
    },

    async buscarProdutoFrete() {
      if (this.loading || this.numeroFrete.length < 7 || !this.numeroFrete) return
      this.debounce(async () => {
        try {
          this.loading = true
          const resposta = await api.get(`api_estoque/separacao/etiquetas_frete`, {
            params: {
              numero_frete: this.numeroFrete,
            },
          })

          this.colaboradorEscolhido = {
            id: resposta.data[0].id_colaborador,
            existe_frete_pendente: true,
          }
          this.CONFERENCIA_items = resposta.data
          this.produtosSelecionados = resposta.data
          this.CONFERENCIA_itens_bipados = []
        } catch (error) {
          this.mostrarErro(error?.response?.data?.message || error?.message || 'Erro ao buscar o frete')
        } finally {
          this.loading = false
        }
      }, 950)
    },
  },

  watch: {
    'conferencia.colaboradorEscolhidoConfirmaBipagem'(valor) {
      this.conferencia.nomeUsuario = valor.razao_social
      this.conferencia.possivelConfirmar = !!valor
    },

    pesquisa(texto) {
      this.fazerPesquisa(texto)
    },

    pesquisaConferente(texto) {
      this.fazerPesquisaConferente(texto)
    },

    colaboradorEscolhido(valor) {
      this.alteraAreaAtual(valor.existe_frete_pendente ? 'CONFERENCIA_FRETE' : 'CONFERENCIA_FORNECEDOR')

      this.focoInput()
    },

    input_qrcode(valor = '') {
      this.debounce(() => {
        if (valor?.split('w=')?.[1]?.length) {
          if (this.carregandoConferir) {
            return
          }

          try {
            this.carregandoConferir = true
            const uuid_bipado = valor.split('w=')[1] || null
            const index = this.CONFERENCIA_items.findIndex((item) => item.uuid === uuid_bipado)
            if (index < 0) {
              throw new Error('Item não disponível para conferência. Favor chamar o responsável pelo estoque!')
            }

            const itemBipado = this.CONFERENCIA_itens_bipados.find((item) => item.uuid === uuid_bipado)
            if (itemBipado) {
              throw new Error('Esse produto já foi bipado, confira na lista!')
            }

            this.biparItens(uuid_bipado)
          } catch (error) {
            this.mostrarErro(error?.message || 'Ocorreu um erro ao conferir o item!')
          } finally {
            this.input_qrcode = null
            this.focoInput()
            this.carregandoConferir = false
          }
        }
      }, 250)
    },

    areaAtual(novoValor) {
      this.conferencia.possivelConfirmar = novoValor !== 'CONFERENCIA_FRETE'
    },

    'conferencia.telefoneUsuario'(novoValor) {
      this.conferencia.telefoneUsuario = formataTelefone(novoValor)
    },
  },

  computed: {
    itemClass() {
      return (item) => {
        return item.bipado ? 'item_bipado' : ''
      }
    },
  },

  mounted() {
    this.buscarTaxaProdutoErrado()
  },
})
