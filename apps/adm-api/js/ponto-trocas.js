import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

let timeout
let interval

var app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    lang: {
      locales: {
        pt,
      },
      current: 'pt',
    },
  }),
  data() {
    return {
      search: '',
      headers: [
        {
          text: 'Data do ultimo envio',
          align: 'start',
          filterable: false,
          value: 'data_ultimo_envio_ts',
        },
        {
          text: 'ID Ponto',
          value: 'id',
        },
        {
          text: 'Nome',
          value: 'nome',
        },
        {
          text: 'Cidade',
          value: 'cidade',
        },
        {
          text: 'Devoluções do ponto',
          value: 'devolucoes',
        },
        {
          text: 'Detalhes',
          value: 'detalhes',
        },
        {
          text: 'Gerar PAC Reverso',
          value: 'gerar_pac_reverso',
        },
        {
          text: 'Número último Pac gerado',
          value: 'pac_reverso',
        },
        {
          text: 'Avisar recolhimento',
          value: 'avisar_recolhimento',
        },
      ],
      listaDePontos: [],
      password: '',
      dialog: false,
      show1: false,
      dialogPacReverso: false,
      produtoADescontar: null,
      snackbar: false,
      modalDescontar: false,
      text: ``,
      loading: false,
      loadingDescontar: false,
      tipoDeDesconto: null,
      headersModalProdutos: [
        {
          text: 'ID',
          sortable: true,
          value: 'id',
        },
        {
          text: 'Foto',
          sortable: true,
          value: 'foto',
        },
        {
          text: 'Tamanho',
          sortable: true,
          value: 'tamanho',
        },
        {
          text: 'Consumidor',
          sortable: true,
          value: 'consumidor',
        },
        {
          text: 'Valor',
          sortable: true,
          value: 'valor',
        },
        {
          text: 'Data da troca',
          sortable: true,
          value: 'data_criacao_ts',
        },
        {
          text: 'Último Bip',
          sortable: true,
          value: 'id_usuario_bip',
        },
        {
          text: 'Ações',
          sortable: true,
          value: 'acoes',
          align: 'center',
        },
      ],
      listaDeProdutosAEnviar: [],
      listaDeProdutosEmTransito: [],
      detalhesDoPontoSelecionado: null,
      listaEtiquetas: [],
    }
  },

  methods: {
    async buscaRelacaoPontoDevolucoes() {
      try {
        const resultado = await api.get('api_estoque/devolucao/buscaRelacao')

        this.listaDePontos = resultado.data.map((item) => {
          return {
            ...item,
            detalhes: 0,
            gerar_pac_reverso: 0,
            avisar_recolhimento: 0,
            produtos: {
              ...item.produtos,
              a_enviar: {
                ...item.produtos.a_enviar,
                items: item.produtos.a_enviar.items.map((item1) => {
                  return {
                    ...item1,
                    acoes: {
                      podeDescontar: true,
                    },
                  }
                }),
              },
            },
          }
        })

        this.listaCidadesAutocomplete = resultado.data
      } catch (error) {
        this.enqueueSnackbar(true, error?.response?.data?.message || error?.message || 'Falha ao descontar do ponto.')
      }
    },
    backgroundLinhaTabela(item) {
      if (item.dias_devolucao > 30) {
        return 'bg-danger'
      }
      return ''
    },
    abreModalDescontar(item, tipo) {
      this.produtoADescontar = item
      this.modalDescontar = true
      this.tipoDeDesconto = tipo
    },
    async confirmaDescontoDePonto() {
      if (!this.password) {
        this.enqueueSnackbar(true, 'Digite sua senha')
        return
      }
      this.loadingDescontar = true
      try {
        /**
         * A requisição para a autenticação aqui é feita como medida de confirmação de que o usuário
         * tem certeza que deseja descontar o produto do ponto, por isso perguntamos a senha do usuário.
         * Mas se o usuário errar a senha o endpoint retorna 401 e o axios redireciona para a tela de login.
         * O que não é o comportamento desejado nesse caso. Por isso a requisição é feita com fetch.
         * Mas se houverem mais casos como esse, é melhor ver uma tratativa melhor que utilizarmos o fetch.
         */
        const baseUrl = document.querySelector("[name='url-mobile']").value
        const login = await fetch(`${baseUrl}api_cliente/autenticacao`, {
          method: 'POST',
          body: JSON.stringify({
            id_colaborador: document.querySelector('input[name="userIDCliente"]').value,
            senha: this.password,
          }),
        })
        if (login.status !== 200) {
          const json = await login.json()
          throw new Error(json?.message || 'Falha ao autenticar usuario')
        }

        const indicePontos = this.listaDePontos.indexOf(this.detalhesDoPontoSelecionado)
        const indiceItems = this.listaDePontos[indicePontos].produtos[this.tipoDeDesconto].items.indexOf(
          this.produtoADescontar,
        )
        const uuid_produto = this.listaDePontos[indicePontos].produtos[this.tipoDeDesconto].items[indiceItems].uuid

        const resultado = await MobileStockApi('api_estoque/devolucao/descontar', {
          method: 'PUT',
          body: JSON.stringify({
            uuid_produto,
            descontar: 'Ponto',
          }),
        }).then((res) => res.json())

        if (!resultado?.status) throw new Error(resultado?.message || 'Falha ao descontar do ponto.')

        this.listaDePontos[indicePontos].produtos[this.tipoDeDesconto].items[indiceItems].acoes.podeDescontar = false

        this.produtoADescontar = null
        this.modalDescontar = false
        this.password = ''

        this.enqueueSnackbar(true, resultado?.message)
      } catch (error) {
        this.enqueueSnackbar(true, error?.message || 'Falha ao descontar do ponto.')
      } finally {
        this.loadingDescontar = false
      }
    },
    gerarPacReversoParaDevolucao(item, emTransito = false) {
      const index = this.listaDePontos.indexOf(item)
      this.$set(this.listaDePontos[index], 'gerar_pac_reverso', 1)
      this.loading = true
      MobileStockApi('api_estoque/devolucao/gera_pac_reverso', {
        method: 'POST',
        body: JSON.stringify({
          id_ponto: item.id,
          id_cliente: item.id_colaborador,
          gerarEmTransito: !!emTransito,
        }),
      })
        .then((res) => res.json())
        .then((numeroPacReverso) => {
          this.text = 'PAC Reverso gerado com sucesso!'
          this.snackbar = true
          if (numeroPacReverso) {
            this.$set(this.listaDePontos[index], 'pac_reverso', numeroPacReverso)

            const mensagemWhatsApp = `Olá Ponto chegou a hora de enviar as Devoluções de Pedidos que não foram buscados após 15 dias, siga os seguinte:\n\n
                1- abra o App de Entregas e clique em Devoluções.\n\n
                2- Bipe as Devoluções. O sistema informa quais são os produtos a serem bipados.\n\n
                3- Após a bipagem embale a mercadoria e dirija a agência de correios mais proximas com o seguinte código ${numeroPacReverso}`

            const mensagem = new MensagensWhatsApp({
              mensagem: mensagemWhatsApp,
              telefone: item.telefone,
            }).resultado

            window.open(mensagem, '_blank')
          }
        })
        .catch((error) => {
          this.text = error
          this.snackbar = true
        })
        .finally(() => {
          this.$set(this.listaDePontos[index], 'gerar_pac_reverso', 0)
          this.loading = false
        })
    },
    notificarDescontoDePonto(item) {
      const mensagem = new MensagensWhatsApp({
        mensagem:
          `Olá ${this.detalhesDoPontoSelecionado?.nome} , identificamos que o produto ${item?.id} - ${item?.nome_produto} com o tamanho [ ${item?.tamanho} ],` +
          ` não foi bipado na área de devolução.\n\n
                Por favor refaça o processo.\n\n
                É de grande importância quer você não se esqueça de bipar os pares para envio nas próximas vezes.`,
        telefone: this.detalhesDoPontoSelecionado.telefone,
      }).resultado

      window.open(mensagem, '_blank')
    },
    clickPACReverso(item, modal = false, emTransito = false) {
      if (modal && !emTransito) {
        const items = JSON.parse(JSON.stringify(this.item.produtos.a_enviar.faturamentos))
        let arrayItems = Object.values(items).map((item) => {
          return item
        })
        this.gerarPacReversoParaDevolucao(this.item, arrayItems.join(','))
        return
      } else if (modal && emTransito) {
        let faturamentosArray = this.item.produtos.em_transito.items.map((obj) => {
          return obj.faturamento
        })
        this.gerarPacReversoParaDevolucao(this.item, faturamentosArray.join(','), true)
        return
      }
      if (item.devolucoes_em_transito > 0 && item.devolucoes_a_enviar == 0) {
        let faturamentosArray = item.produtos.em_transito.items.map((obj) => {
          return obj.faturamento
        })
        this.gerarPacReversoParaDevolucao(item, faturamentosArray.join(','), true)
      } else if (item.devolucoes_a_enviar > 0 && item.devolucoes_em_transito > 0) {
        this.item = item
        this.dialogPacReverso = true
      } else {
        this.gerarPacReversoParaDevolucao(item, item.produtos.a_enviar.faturamentos.join(','))
      }
    },
    avisaPonto(item) {
      const mensagemWhatsApp = `Olá Ponto chegou a hora de enviar as Devoluções de Pedidos que não foram buscados após 15 dias, siga os seguinte:\n\n
                1- Bipe as devoluções. O sistema informa quais são os produtos a serem bipados.\n\n
                2- Nosso transportador irá recolher a mercadoria.`

      const mensagem = new MensagensWhatsApp({
        mensagem: mensagemWhatsApp,
        telefone: item.telefone,
      }).resultado

      window.open(mensagem, '_blank')
    },
    abreModalDeDetalhesDoPonto(item) {
      this.detalhesDoPontoSelecionado = item
      this.dialog = true
    },
    formataValores(valor) {
      const novoValor = new Intl.NumberFormat('pt-br', {
        style: 'currency',
        currency: 'BRL',
      }).format(valor || 0)
      return novoValor
    },
    informaDesabilitado(item) {
      return item.categoria === 'PE' ? 'bg-gray' : ''
    },
    enqueueSnackbar(ativar = false, texto = 'Erro') {
      this.snackbar = ativar
      this.text = texto
    },
  },
  mounted() {
    this.buscaRelacaoPontoDevolucoes()
  },
})
