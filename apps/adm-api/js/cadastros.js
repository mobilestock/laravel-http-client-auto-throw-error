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
      header_usuarios: [
        this.itemGrade('id', 'id_colaborador'),
        this.itemGrade('Usuário', 'nome'),
        this.itemGrade('Razão Social', 'razao_social'),
        this.itemGrade('Nível de Acesso', 'permissoes'),
        this.itemGrade('Data do Cadastro', 'data_cadastro'),
        this.itemGrade('Telefone', 'telefone'),
        this.itemGrade('Ações', 'acoes'),
      ],
      loading: false,
      usuarios: [],
      permissoes: [],
      snackbar: {
        open: false,
        color: 'error',
        message: '',
      },
      filtro: '',
      dialog_cadastro: false,
      checkboxCPF: false,
      checkboxCNPJ: false,
      regime: '',
      mostraSenha: false,
      mostraSenha2: false,
      nivelAcesso: 0,
    }
  },
  methods: {
    itemGrade(label, value, sortable) {
      return {
        text: label,
        align: 'start',
        sortable: sortable,
        value: value,
      }
    },
    onCatch(error) {
      this.snackbar.message = error?.response?.data?.message || error?.message || 'Ocorreu algum erro'
      this.snackbar.open = true
    },
    buscaColaboradores(e) {
      this.filtro = e.target[0].value
      this.loading = true
      const parametros = new URLSearchParams({
        filtro: this.filtro,
        nivel_acesso: this.nivelAcesso,
      })

      api
        .get(`api_administracao/cadastro/lista_filtrada_colaboradores?${parametros}`)
        .then((json) => {
          this.usuarios = json.data?.map((usuario) => ({
            ...usuario,
            telefone: formataTelefone(usuario.telefone || ''),
          }))
        })
        .catch((error) => {
          this.onCatch(error)
        })
        .finally(() => {
          this.loading = false
        })
    },
    buscaPermissoes() {
      MobileStockApi(`api_administracao/cadastro/busca_permissoes/0`)
        .then((resp) => resp.json())
        .then((json) => {
          if (!json.status || !json.data) {
            throw new Error(json.message)
          }
          json.data.todas_permissoes.forEach((element) => {
            this.permissoes.push({
              text: `${element.nome}`,
              value: `${element.nivel_value}`,
            })
          })
        })
        .catch((error) => {
          this.onCatch(error)
        })
    },
    validacaoFormulario(mensagem) {
      this.snackbar.message = mensagem
      this.snackbar.open = true
      this.loading = false
      this.dialog_cadastro = true
    },
    marcaCheckbox(checkbox) {
      if (checkbox == 1) {
        this.checkboxCPF = false
        this.checkboxCNPJ = true
        this.regime = '1'
      } else {
        this.checkboxCPF = true
        this.checkboxCNPJ = false
        this.regime = '2'
      }
    },
    defineNivelAcesso(dados) {
      this.nivelAcesso = dados.value
    },
  },
  async mounted() {
    this.buscaPermissoes()
  },
})
