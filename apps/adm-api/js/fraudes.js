import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var request = null

var app = new Vue({
  el: '#menufraudes',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      colaboradorAlterar: null,
      cabecalho: [
        this.itemGrade('Id', 'colaborador.id'),
        this.itemGrade('Data primeira compra', 'transacoes.data_primeira_compra', true),
        this.itemGrade('Nome', 'colaborador.nome'),
        this.itemGrade('Telefone', 'colaborador.telefone'),
        this.itemGrade('Cidade', 'colaborador.cidade'),
        this.itemGrade('Valor total', 'transacoes.valor_liquido'),
        this.itemGrade('Qtd. Tentativas Cartão', 'qtd_tentativas_cartao'),
        this.itemGrade('Origem da Transação', 'origem_transacao'),
        this.itemGrade('Dispositivo', 'user_agent'),
        this.itemGrade('Ultimo Ponto', 'ponto_ultima_compra'),
        this.itemGrade('Situação', 'situacao_fraude'),
      ],
      cabecalhoSuspeitos: [
        this.itemGrade('Nome', 'razao_social'),
        this.itemGrade('Transações', 'transacoes'),
        this.itemGrade('Ip', 'ip'),
        this.itemGrade('Latitude', 'latitude'),
        this.itemGrade('Longitude', 'longitude'),
        this.itemGrade('Data', 'data_criacao', true),
      ],
      colaboradores: [],
      colaboradoresComCaracteristicasEmComum: [],
      opcoesItensPorPag: [30, 50, 100, 200, 300],
      pesquisa: '',
      qtdTotalItens: 0,
      itensPorPagina: null,
      pagina: 1,
      carregando: false,
      cabecalhoListaTransacoes: [
        this.itemGrade('Id', 'id'),
        this.itemGrade('Cod Transacao', 'cod_transacao'),
        this.itemGrade('Emissor Transação', 'emissor_transacao'),
      ],
      tela: 'fraudes',
      campoOrdenar: {
        nome_campo: 'transacoes.data_primeira_compra',
        decrescente: true,
      },
      telefoneModal: null,
    }
  },
  methods: {
    async buscarColaboradoresFraudulentos() {
      this.carregando = true
      try {
        const parametros = new URLSearchParams({
          itens_por_pagina: this.itensPorPagina || this.opcoesItensPorPag[0],
          pesquisa: this.pesquisa,
          pagina: this.pagina,
          ordenar_decrescente: this.campoOrdenar.decrescente,
        })

        const retorno = await api.get(`api_administracao/fraudes?${parametros}`)
        this.colaboradores = retorno.data.colaboradores
        this.qtdTotalItens = retorno.data.qtd_itens
        this.itensPorPagina = retorno.data.itens_por_pagina
        this.colaboradoresComCaracteristicasEmComum = retorno.data.colaboradores_com_caracteristicas_em_comum
        this.colaboradoresComCaracteristicasEmComum.forEach((el) => {
          if (el.length > 0) {
            this.colaboradores.forEach((colaborador) => {
              if (colaborador.colaborador.id == el[0].suspeito) {
                colaborador.caracteristicas = true
              }
            })
          }
        })
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.carregando = false
        request = null
      }
    },

    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        align: 'start',
        sortable: ordenavel,
        value: valor,
      }
    },

    async mudaSituacaoFraude(val, item) {
      this.colaboradorAlterar = {
        id: item.colaborador.id,
        situacao: val,
      }

      if (val === 'FR') {
        this.carregando = true
        try {
          const resposta = await api.get(`api_administracao/fraudes/${item.colaborador.id}/transacoes`)
          this.colaboradorAlterar.transacoes_remover = resposta.data
        } catch (error) {
          this.enqueueSnackbar(
            error?.response?.data?.message || error?.message || 'Erro ao buscar as transações para cancelar.',
          )
        } finally {
          this.carregando = false
        }
      }

      this.$refs['select_' + item.colaborador.id].lazyValue = item.situacao_fraude
    },

    async confirmaMudancaSituacaoFraude() {
      this.carregando = true
      try {
        await api.put(`api_administracao/fraudes/${this.colaboradorAlterar.id}`, {
          situacao: this.colaboradorAlterar.situacao,
          origem: 'CARTAO',
          transacoes:
            this?.colaboradorAlterar?.transacoes_remover?.map((transacao) => {
              return {
                id_transacao: transacao.id,
              }
            }) || [],
        })

        if (this.colaboradorAlterar.situacao === 'FR') {
          var promises = []
          this.colaboradorAlterar.transacoes_remover.forEach((transacao) => {
            let promise = api
              .delete(`api_cliente/cancela_transacao/${transacao.id}?motivo_cancelamento=FRAUDE`)
              .catch((error) => {
                this.enqueueSnackbar(
                  `Ocorreu um erro ao cancelar a transação ${transacao.id} ${
                    error?.response?.data?.message || error?.message || error
                  }`,
                )
              })

            promises.push(promise)
          })

          await Promise.all(promises)
        }

        this.colaboradores.find((col) => col.colaborador.id === this.colaboradorAlterar.id).situacao_fraude =
          this.colaboradorAlterar.situacao
        this.colaboradorAlterar = null
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message ||
            error?.message ||
            error ||
            'Ocorreu um erro ao confirmar a mudança de situação.',
        )
      } finally {
        this.carregando = false
      }
    },

    async buscarColaboradoresSuspeitos() {
      this.carregando = true
      await MobileStockApi('api_administracao/fraudes/suspeitos')
        .then((response) => response.json())
        .then((json) => {
          this.colaboradoresSuspeitos = json.data
        })
        .finally(() => {
          this.carregando = false
        })
    },

    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error', tempo = 5000) {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
        tempo: tempo,
      }
    },
  },

  filters: {
    dinheiro(value) {
      if (isNaN(parseFloat(value))) return 'R$0,00'
      return parseFloat(value).toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
  },

  watch: {
    itensPorPagina(newV, oldV) {
      if (newV !== oldV && oldV !== null) {
        this.buscarColaboradoresFraudulentos()
      }
    },

    tela(newV, oldV) {
      if (newV === 'suspeitos' && newV !== oldV) {
        this.buscarColaboradoresSuspeitos()
      }
    },
  },

  computed: {
    qrcodeCliente() {
      if (!this.telefoneModal?.telefone) return ''

      const mensagem = new MensagensWhatsApp({
        mensagem: '',
        telefone: this.telefoneModal?.telefone,
      }).resultado

      return `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(mensagem)}`
    },
  },

  async mounted() {
    await this.buscarColaboradoresFraudulentos()
  },
})
