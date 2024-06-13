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
      idTransacao: document.getElementById('id-transacao').value,
      transacao: {},
      modalQrCode: false,
      qrcodeProduto: [],
      exibirQrcodeProduto: false,
      loadingImprimeEtiquetas: false,
      telaAtual: 'produtos',
      lancamentos: {
        carregando: false,
        jaFoiAcessado: false,
        itens: [],
        headers: [
          {
            text: 'ID',
            value: 'id',
          },
          {
            text: 'Origem lancamento',
            value: 'origem',
          },
          {
            text: 'Colaborador',
            value: 'colaborador',
          },
          {
            text: 'Crédito',
            value: 'credito',
          },
          {
            text: 'Débito',
            value: 'debito',
          },
          {
            text: 'Data criação',
            value: 'data_emissao',
          },
        ],
      },
      transferencias: {
        jaFoiAcessado: false,
        carregando: false,
        itens: [],
        headers: [
          { text: 'ID', value: 'id' },
          { text: 'Transferencia', value: 'id_transferencia' },
          { text: 'Recebedor', value: 'recebedor' },
          { text: 'Situação', value: 'situacao' },
          { text: 'Valor Direcionado', value: 'valor' },
          { text: 'Valor Pago', value: 'valor_pago' },
        ],
      },
      historico: {
        modal: false,
        jaFoiAcessado: false,
        carregando: false,
        itens: [],
        headers: [
          { text: 'ID', value: 'id_produto' },
          { text: 'Foto', value: 'foto' },
          { text: 'Nome', value: 'nome' },
          { text: 'Tamanho', value: 'tamanho' },
          { text: 'Responsável', value: 'responsavel_estoque' },
          { text: 'Localização', value: 'localizacao' },
          { text: 'Situação', value: 'situacao' },
          { text: 'Troca?', value: 'situacao_troca' },
          { text: 'Tipo troca', value: 'tipo_troca' },
          { text: 'Valor', value: 'preco' },
          { text: 'Usuário', value: 'usuario' },
          { text: 'Data atualização', value: 'data_atualizacao' },
          { text: 'Previsão entrega', value: 'previsao', align: 'center' },
        ],
      },
      trocas: {
        jaFoiAcessado: false,
        carregando: false,
        itens: [],
        headers: [
          { text: 'ID', value: 'id' },
          { text: 'Transação Origem', value: 'transacao_origem' },
          { text: 'ID produto', value: 'id_produto' },
          { text: 'Tamanho', value: 'nome_tamanho' },
          { text: 'Foto', value: 'foto' },
          { text: 'Preco venda', value: 'valor' },
          { text: 'Saldo troca utilizado', value: 'valor_pago' },
          { text: 'Situação', value: 'situacao' },
          { text: 'Detalhes do produto', value: 'detalhes_produto' },
        ],
        itensModal: null,
      },
      produtos: {
        itens: [],
        // headers: [
        //   { text: "ID", value: "id" },
        //   { text: "Entrega", value: "id_entrega" },
        //   { text: "ID Produto", value: "id_produto" },
        //   { text: "Nome do Produto", value: "nome" },
        //   { text: "Tipo produto", value: "tipo_item" },
        //   { text: "Comissionado", value: "comissionado" },
        //   { text: "Preco", value: "preco" },
        //   { text: "Valor comissao", value: "valor_comissao" },
        // ],
      },
      tentativas: {
        jaFoiAcessado: false,
        carregando: false,
        itens: [],
        json: [],
        headers: [
          { text: 'ID', value: 'id' },
          { text: 'Id transação', value: 'id_transacao' },
          { text: 'Emissor transação', value: 'emissor_transacao' },
          { text: 'Cod. Transação', value: 'cod_transacao' },
          { text: 'Mensagem erro', value: 'mensagem_erro' },
          { text: 'Json', value: 'json' },
          { text: 'Data', value: 'data_criacao' },
        ],
      },
      carregando: true,
      modalTentativas: false,
      snackbar: {
        aberto: false,
        cor: 'error',
        mensagem: '',
      },
      situacaoModalCancelaTransacao: false,
      motivoCancelamento: '',
    }
  },

  methods: {
    traduzSituacao(situacao) {
      switch (situacao) {
        case 'EXPEDIDO':
          return 'Em transporte'
        case 'ENTREGUE':
          return 'Entregue'
        case 'ENTREGUE_AO_DESTINATARIO':
          return 'Produto chegou no ponto mas não bipou a chegada'
        case 'PONTO_RETIRADA':
          return 'No ponto de retirada'
        case 'ENTREGADOR':
          return 'Com o entregador'
        case 'PREPARADO_ENVIO':
          return 'Adicionado na Entrega'
        case 'SEPARADO':
          return 'Separado'
        case 'CONFERIDO':
          return 'Conferido'
        case 'LIBERADO_LOGISTICA':
          return 'Liberado para logística'
        case 'CANCELADO':
          return 'Cancelado'
        case 'NAO_ENTREGUE':
          return 'Ainda não entregue'
        case 'TROCADO':
          return 'Produto trocado'
        case 'PRAZO_EXPIRADO_GARANTIA':
          return 'Prazo de garantia expirado'
        case 'PRAZO_EXPIRADO':
          return 'Prazo de troca expirado'
        case 'DISPONIVEL':
          return 'Disponível'
        case 'INDISPONIVEL':
          return 'Indisponível'
        case 'AGUARDANDO_PAGAMENTO':
          return 'Aguardando pagamento'
        case 'AGUARDANDO_LOGISTICA':
          return 'Aguardando logística (Produto pago ou fraude)'
        case 'TRANSACAO_CRIADA':
          return 'Transação criada'
        default:
          return situacao
      }
    },
    async buscaInfoTransacao() {
      try {
        const response = await api.get(`api_administracao/transacoes/${this.idTransacao}`)

        let itemAnterior = ''
        let usarTemaDark = true
        this.produtos.itens = response.data?.produtos?.map((item) => {
          item.icone = this.iconeTipoItem(item.tipo_item)
          if (itemAnterior !== item.uuid_produto) {
            usarTemaDark = !usarTemaDark
            itemAnterior = item.uuid_produto
          }
          item.usarTemaDark = usarTemaDark

          return item
        })
        let historico = []
        response.data.produtos.forEach((item) => {
          switch (item?.tipo_troca) {
            case 'NO':
              item.tipo_troca = 'Normal'
              break
            case 'DE':
              item.tipo_troca = 'Defeito'
              break
            case null:
              item.tipo_troca = 'Sem Troca'
              break
          }
          if (['PR', 'RF'].includes(item.tipo_item)) {
            historico.push({
              id_produto: item.id_produto,
              nome: item.nome,
              tamanho: item.tamanho,
              foto: item?.foto,
              localizacao: item.localizacao,
              situacao: this.traduzSituacao(item?.situacao?.situacao),
              situacao_troca: this.traduzSituacao(item?.situacao?.situacao_troca),
              usuario: item?.situacao?.usuario,
              data_atualizacao: item?.situacao?.data_atualizacao,
              tipo_troca: item.tipo_troca,
              negociacao_aceita: item.negociacao_aceita,
              responsavel_estoque: item.id_responsavel_estoque > 1 ? 'EXTERNO' : 'FULFILLMENT',
              previsao: item.previsao,
              preco: formataMoeda(item.preco),
            })
          }
        })
        this.historico.itens = historico
        delete response.data.produtos
        this.transacao = response.data
      } catch (err) {
        this.snackbar.aberto = true
        this.snackbar.cor = 'error'
        this.snackbar.mensagem =
          err?.response?.data?.message || err?.message || err || 'Não foi possivel buscar informações da transação'
      }
    },
    buscaLancamentos() {
      return MobileStockApi('api_administracao/transacoes/' + this.idTransacao + '/lancamentos')
        .then((r) => r.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
          this.lancamentos.itens = json.data.lancamentos
          this.lancamentos.jaFoiAcessado = true
        })
        .catch((err) => {
          this.snackbar.aberto = true
          this.snackbar.cor = 'error'
          this.snackbar.mensagem = err?.message || err || 'Não foi possivel buscar os lançamentos da transação'
        })
    },

    buscaTransferencias() {
      return MobileStockApi('api_administracao/transacoes/' + this.idTransacao + '/transferencias')
        .then((r) => r.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
          this.transferencias.itens = json.data.transferencias
          this.transferencias.jaFoiAcessado = true
        })
        .catch((err) => {
          this.snackbar.aberto = true
          this.snackbar.cor = 'error'
          this.snackbar.mensagem = err?.message || err || 'Não foi possivel buscar as transferencias da transação'
        })
    },

    buscaTentativas() {
      return MobileStockApi('api_administracao/transacoes/' + this.idTransacao + '/tentativa')
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status) {
            throw new Error(json.message)
          }
          if (json.data.tentativas.length === 0) {
            throw new Error('Ainda não houve tentativas de transação!')
          }
          this.tentativas.itens = json.data.tentativas
          this.tentativas.json = this.tentativas.itens[0].transacao_json
          this.tentativas.jaFoiAcessado = true
        })
        .catch((err) => {
          this.snackbar.aberto = true
          this.snackbar.cor = 'error'
          this.snackbar.mensagem = err?.message || err || 'Não foi possível buscar as tentativas de transação!'
        })
    },

    async cancelaTransacao() {
      try {
        if (!this.$refs.formularioCancelaTransacao.validate()) {
          this.snackbar.aberto = true
          this.snackbar.cor = 'error'
          return (this.snackbar.mensagem = 'Preencha o dados para cancelar a transação')
        }

        this.situacaoModalCancelaTransacao = 'carregando'
        await api.delete(
          `api_cliente/cancela_transacao/${this.idTransacao}?motivo_cancelamento=${this.$refs.formularioCancelaTransacao.inputs[0].value}`,
        )
        window.location.reload()
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Não foi possivel cancelar a transação',
        )
        this.situacaoModalCancelaTransacao = 'aberto'
      }
    },

    async atualizaComApi() {
      this.carregando = true
      try {
        const resposta = await api.post('api_administracao/pagamento/sync', {
          id: this.idTransacao,
          transacao: this.transacao.cod_transacao,
          id_pagador: 1,
          tipo: this.transacao.emissor_transacao,
        })

        if (resposta.status === 204) {
          return this.enqueueSnackbar('Não foi possível atualizar a transação com a API', 'grey')
        }

        await esperaFila(`api_pagamento/link_pagamento/fila/${resposta.data}`)

        return window.location.reload()
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Não foi possível atualizar a transação com a API',
        )
      } finally {
        this.carregando = false
      }
    },

    async buscaTrocas() {
      try {
        const resposta = await api.get(`api_administracao/transacoes/${this.idTransacao}/trocas`)
        this.trocas.itens = resposta.data
        this.trocas.jaFoiAcessado = true
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || error || 'Não foi possivel buscar as trocas da transação',
        )
      }
    },
    iconeTipoItem(tipo) {
      let icone = ''
      switch (true) {
        case tipo === 'DIREITO_COLETA':
          icone = 'mdi-motorbike'
          break
        case tipo === 'FR':
          icone = 'mdi-airplane'
          break
        case tipo === 'PR':
          icone = 'fas fa-boxes'
          break
        case ['CM_ENTREGA', 'CE'].includes(tipo):
          icone = 'mdi-target-account'
          break
        case tipo === 'CM_PONTO_COLETA':
          icone = 'mdi-truck-delivery'
          break
        case tipo === 'CL':
          icone = 'mdi-link'
          break
        case tipo === 'FOTO_PRODUTO':
          icone = 'mdi-image'
          break
        case !!tipo.match(/TAXA_/):
          icone = 'mdi-percent-outline'
          break
      }

      return icone
    },
    corSituacaoPagamento(situacao) {
      let cor = ''
      switch (situacao) {
        case 'PAGO':
          cor = 'text-success'
          break
        case 'PAGAMENTO_PENDENTE':
          cor = 'text-warning'
          break
        default:
          cor = 'text-danger'
          break
      }

      return `font-weight-bold ${cor}`
    },
    exibirQrcodeProdutoFn(produto) {
      this.qrcodeProduto = produto
      this.exibirQrcodeProduto = true
    },
    async imprimeEtiquetasSeparacaoCliente(uuid_produto) {
      try {
        this.loadingImprimeEtiquetas = true
        const resposta = await api.post('api_estoque/separacao/produtos/etiquetas', {
          uuids: [uuid_produto],
        })

        const blob = new Blob([JSON.stringify(resposta.data)], {
          type: 'json',
        })
        saveAs(blob, 'etiquetas_cliente.json')
      } catch (error) {
        this.snackbar.aberto = true
        this.snackbar.cor = 'error'
        this.snackbar.mensagem =
          error?.response?.data?.message || error?.message || 'Não foi possível imprimir a etiqueta'
      } finally {
        this.loadingImprimeEtiquetas = false
      }
    },

    enqueueSnackbar(mensagem = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        aberto: true,
        cor: cor,
        mensagem: mensagem,
      }
    },
    formataSituacaoTroca(troca) {
      switch (troca.situacao) {
        case 'TROCA_ACEITA':
          return `Troca bipada por ${troca.usuario} em ${troca.data_atualizacao}`
        case 'TROCA_PENDENTE':
          return `Troca agendada por ${troca.usuario} em ${troca.data_atualizacao}`
        case 'PIX_ESQUECI_TROCA':
          return `Desistiu da troca e pagou débito na transação <a target="_blank" href="transacao-detalhe.php?id=${troca.transacao_origem}">${troca.transacao_origem}</a>`
      }
    },
    async buscaDebitosDaTroca(troca) {
      try {
        this.trocas.carregando = true
        const resposta = await api.get(`api_administracao/pay/abates/${troca.id}`)

        this.trocas.itensModal = resposta.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Não foi possível buscar os débitos')
      } finally {
        this.trocas.carregando = false
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
    telaAtual(newV) {
      if (newV === 'lancamentos' && !this.lancamentos.jaFoiAcessado && !this.lancamentos.carregando) {
        this.lancamentos.carregando = true
        this.buscaLancamentos().finally(() => {
          this.lancamentos.carregando = false
        })
      }

      if (newV === 'transferencias' && !this.transferencias.jaFoiAcessado && !this.transferencias.carregando) {
        this.transferencias.carregando = true
        this.buscaTransferencias().finally(() => {
          this.transferencias.carregando = false
        })
      }

      if (newV === 'trocas' && !this.trocas.jaFoiAcessado && !this.trocas.carregando) {
        this.trocas.carregando = true
        this.buscaTrocas().finally(() => {
          this.trocas.carregando = false
        })
      }

      if (newV === 'tentativas' && !this.tentativas.jaFoiAcessado && !this.tentativas.carregando) {
        this.tentativas.carregando = true
        this.buscaTentativas().finally(() => {
          this.tentativas.carregando = false
        })
      }
    },
  },

  mounted() {
    document.getElementById('app').classList.remove('d-none')
    this.buscaInfoTransacao().finally(() => (this.carregando = false))
  },
})
