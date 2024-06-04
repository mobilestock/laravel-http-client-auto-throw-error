import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var monitoraPontosVUE = new Vue({
  el: '#monitoraPontosVUE',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      loading: false,
      detalhes: [],
      headers: [
        {
          text: 'ID',
          align: 'center',
          value: 'id',
        },
        {
          text: 'Nome',
          align: 'center',
          value: 'nome',
        },
        {
          text: 'Telefone',
          align: 'center',
          value: 'telefone',
        },
        {
          text: 'Cidade',
          align: 'center',
          value: 'cidade',
        },
        {
          text: 'Estado',
          align: 'center',
          value: 'estado',
        },
        {
          text: 'Situação',
          align: 'center',
          value: 'situacao_ponto',
        },
        {
          text: 'Previsão de entrega',
          align: 'center',
          value: 'previsao',
        },
        {
          text: 'Última Entrega',
          align: 'center',
          value: 'ultimo_envio',
        },
        {
          text: 'Detalhes',
          align: 'center',
          sortable: false,
          value: 'acoes',
        },
      ],
      headersProdutos: [
        {
          text: 'ID',
          align: 'center',
          value: 'id_produto',
        },
        {
          text: 'Produto',
          align: 'center',
          sortable: false,
          value: 'foto',
        },
        {
          text: 'Nome produto',
          align: 'center',
          value: 'nome_produto',
        },
        {
          text: 'Tamanho',
          align: 'center',
          value: 'nome_tamanho',
        },
        {
          text: 'Nome Cliente',
          align: 'center',
          value: 'razao_social',
        },
        {
          text: 'Telefone Cliente',
          align: 'center',
          value: 'telefone',
        },
        {
          text: 'Nome destinatário',
          align: 'center',
          value: 'nome_destinatario',
        },
        {
          text: 'Data',
          align: 'center',
          value: 'data_atualizacao',
        },
        {
          text: 'Atrasado',
          align: 'center',
          value: 'atrasado',
        },
      ],
      listaPontosRetirada: [],
      pesquisaPontosRetirada: '',
      modal: false,
      produtosEPonto: [],
      qualTabela: 'chegando',
      tempoChegando: false,
      tempoRetirando: false,
      modalQrCode: false,
      expande: false,
      snackbar: {
        mostrar: false,
        cor: '',
        texto: '',
      },
    }
  },
  methods: {
    dataAtual() {
      let data = new Date()
      let dia = String(data.getDate()).padStart(2, '0')
      let mes = String(data.getMonth() + 1).padStart(2, '0')
      let ano = data.getFullYear()

      let dataAtual = dia + '/' + mes + '/' + ano

      return dataAtual
    },
    buscaListaPontos() {
      this.loading = true

      api
        .get('api_administracao/pontos_de_entrega/lista_pontos')
        .then((json) => (this.listaPontosRetirada = json.data))
        .catch((error) =>
          this.enqueueSnackbar(
            error?.response?.data?.message || error?.message || 'Erro ao buscar os pontos de retirada',
          ),
        )
        .finally(() => (this.loading = false))
    },
    async openModal(ponto) {
      try {
        this.loading = true
        this.qualTabela = 'chegando'
        this.produtosEPonto = []
        let produtoPonto = []

        const resultado = await api.get(`api_administracao/pontos_de_entrega/status_produto/${ponto.id}`)

        produtoPonto['produtos'] = resultado.data
        produtoPonto['ponto'] = ponto

        this.chegarAtrasado(resultado.data.chegando)
        this.retirarAtrasado(resultado.data.retirando)
        this.produtosEPonto = produtoPonto

        return (this.modal = true)
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar os pontos de retirada')
      } finally {
        this.loading = false
      }
    },
    chegarAtrasado(infoPonto) {
      this.tempoChegando = false
      const informacoes = infoPonto
      informacoes.forEach((el) => {
        el.telefone = formataTelefone(el.telefone)
        if (el.esta_em_atraso) {
          el['atrasado'] = true
          this.tempoChegando = true
        }
      })
    },
    // conferirAtrasado(infoPonto) {
    //   this.tempoConferindo = false;
    //   const informacoes = infoPonto;
    //   informacoes.forEach((el) => {
    //     el.telefone = formataTelefone(el.telefone);
    //     if (el.em_atraso) {
    //       el["atrasado"] = true;
    //       this.tempoConferindo = true;
    //     }
    //   });
    // },
    retirarAtrasado(infoPonto) {
      this.tempoRetirando = false
      const informacoes = infoPonto
      informacoes.forEach((el) => {
        el.telefone = formataTelefone(el.telefone)
        if (el.esta_em_atraso) {
          el['atrasado'] = true
          this.tempoRetirando = true
        }
      })
    },
    mudaSetor(tabela) {
      this.qualTabela = tabela
    },
    openModalQrCode() {
      this.modalQrCode = true
      this.expande = true
    },
    async downloadEtiqueta(item) {
      try {
        const resposta = await api.post('api_estoque/separacao/produtos/etiquetas', {
          uuids: [item.uuid_produto],
        })

        const blob = new Blob([JSON.stringify(resposta.data)], {
          type: 'text/plain;charset=utf-8',
        })
        saveAs(blob, `etiqueta_cliente_produto_${item.id_produto}.json`)
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar os produtos')
      }
    },
    corPontoDesativado(item) {
      if (item.categoria !== 'ML') {
        return 'blue-grey lighten-4'
      }
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        mostrar: true,
        cor: cor,
        texto: texto,
      }
    },
  },
  mounted() {
    this.buscaListaPontos()
  },
})
