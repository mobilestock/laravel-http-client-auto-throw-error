var reposicoesEtiquetasVue = new Vue({
  el: '#reposicoesEtiquetasVue',
  vuetify: new Vuetify(),

  data: {
    loading: false,
    idProduto: null,
    idColaborador: null,
    snackbar: {
      ativar: false,
      texto: '',
      cor: 'error',
    },
    produto: {
      idProduto: null,
      referencia: null,
      fornecedor: null,
      foto: null,
      grades: [],
    },
    headersGrades: [
      {
        text: 'Tamanho',
        value: 'nome_tamanho',
        align: 'center',
      },
      {
        text: 'Remover',
        value: 'remover',
        align: 'center',
      },
      {
        text: 'Estoque',
        value: 'estoque',
        align: 'center',
      },
      {
        text: 'Adicionar',
        value: 'adicionar',
        align: 'center',
      },
      {
        text: 'Selecionado',
        value: 'selecionado',
        align: 'center',
      },
      // this.itemGrade('Tamanho', 'nome_tamanho'),
      // this.itemGrade('Remove', 'remover'),
      // this.itemGrade('Estoque', 'estoque'),
      // this.itemGrade('Adiciona', 'adicionar'),
      // this.itemGrade('Selecionado', 'selecionado'),
    ],
  },

  methods: {
    itemGrade(label, valor, ordenavel = false) {
      return {
        text: label,
        align: 'center',
        sortable: ordenavel,
        value: valor,
      }
    },

    async buscarProduto() {
      try {
        this.loading = true
        const resposta = await api.get('api_administracao/produtos_logistica/etiquetas', {
          params: {
            id_produto: this.idProduto,
            id_colaborador: this.idColaborador,
          },
        })

        this.produto = resposta.data
        this.produto.grades = this.produto.grades.map((grade) => ({
          ...grade,
          selecionado: 0,
        }))
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar produto')
      } finally {
        this.loading = false
      }
    },

    remover(grade) {
      if (grade.selecionado > 0) {
        grade.selecionado--
      }
    },

    adicionar(grade) {
      if (grade.selecionado < item.estoque) {
        grade.selecionado++
      }
    },

    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        ativar: true,
        texto: texto,
        cor: cor,
      }
    },
  },

  filters: {},
  computed: {},
  watch: {},
  async mounted() {
    const urlParams = new URLSearchParams(window.location.search)

    this.idProduto = urlParams.get('id_produto')
    this.idColaborador = $('#cabecalhoVue input[name=userIDCliente]').val()

    await this.buscarProduto()
  },
})
