import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

new Vue({
  el: '#monitoraEntregadoresVue',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      oldPesquisa: '',
      carregando: false,
      maisPags: false,
      listaInformacoes: [],
      headersInformacoes: [
        this.itemGrades('Entrega / Transação', 'id_entrega_transacao'),
        this.itemGrades('ID produto - Tamanho', 'id_produto_tamanho'),
        this.itemGrades('Produto', 'foto_produto'),
        this.itemGrades('Cliente', 'cliente.nome'),
        this.itemGrades('Entregador', 'entregador.nome'),
        this.itemGrades('Data Coletado', 'data_coleta'),
        this.itemGrades('Cidade Entrega', 'cidade'),
      ],
      filtros: {
        pagina: 0,
        pesquisa: '',
      },
      modalQrCode: {
        ativar: false,
        nome: '',
        qrCode: '',
      },
      snackbar: {
        ativar: false,
        cor: '',
        tempo: 5000,
        texto: '',
      },
    }
  },
  methods: {
    async buscaListaEntregadoresComProdutos() {
      try {
        this.carregando = true

        let pagina = this.filtros.pagina
        if (this.oldPesquisa !== this.filtros.pesquisa) this.filtros.pagina = 0
        let pesquisa = this.filtros.pesquisa

        const parametros = new URLSearchParams({
          pagina: pagina || 0,
          pesquisa: pesquisa || '',
        })

        const retorno = await api.get(
          `api_administracao/pontos_de_entrega/busca/lista_entregadores_com_produtos?${parametros}`,
        )

        const consulta = retorno.data
        this.oldPesquisa = consulta.old_pesquisa
        this.maisPags = consulta.mais_pags
        if (this.filtros.pagina < 1) {
          this.listaInformacoes = consulta.informacoes
        } else {
          this.listaInformacoes = this.listaInformacoes.concat(consulta.informacoes)
        }
      } catch (error) {
        this.enqueueSnackbar(error)
      } finally {
        this.carregando = false
      }
    },
    cortaNome(nome = '') {
      return nome.substring(0, 35)
    },
    gerirModal(abrir = false, ehEntregador = false, dados = {}) {
      if (!abrir) {
        this.modalQrCode.ativar = abrir
        return
      }

      let qrCode = ''
      if (ehEntregador) {
        const nomeCliente = dados.cliente.nome.replace(/[^a-z\s]/gi, '').trim()
        const telefoneCliente = formataTelefone(dados.cliente.telefone)
        const nomeEntregador = dados.entregador.nome.replace(/[^a-z]/gi, '').trim()
        let texto = `Olá ${nomeEntregador}!\n`
        texto = `${texto}A entrega do(a) ${nomeCliente} - ${telefoneCliente} ${dados.endereco} que foi coletada por você dia ${dados.data_coleta} ainda não foi entregue.\n`
        texto = `${texto}Favor entre em contato com o(a) cliente pelo link abaixo e entregue seus produtos urgentemente para que seus valores não sejam bloqueados.\n\n`
        texto = `${texto}${dados.cliente.link}`
        qrCode = encodeURIComponent(
          new MensagensWhatsApp({
            mensagem: texto,
            telefone: dados.entregador.telefone,
          }).resultado,
        )
      } else {
        qrCode = encodeURIComponent(new MensagensWhatsApp({ telefone: dados.cliente.telefone }).resultado)
      }

      this.modalQrCode = {
        ativar: abrir,
        nome: ehEntregador ? dados.entregador.nome : dados.cliente.nome,
        qrCode: qrCode,
      }
    },
    itemGrades(campo, valor, ordenavel = false, estilizacao = 'text-light grey darken-3') {
      return {
        text: campo,
        value: valor,
        sortable: ordenavel,
        class: estilizacao,
        align: 'center',
      }
    },
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error', tempo = 5000) {
      this.snackbar = {
        ativar: true,
        cor: cor,
        tempo: tempo,
        texto: texto,
      }
    },
  },
  mounted() {
    this.buscaListaEntregadoresComProdutos()
  },
  watch: {
    'filtros.pagina'() {
      this.buscaListaEntregadoresComProdutos()
    },
  },
})
