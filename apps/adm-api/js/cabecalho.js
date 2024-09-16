var cabecalhoVue = new Vue({
  el: '#cabecalhoVue',
  data: {
    user: {
      id: 0,
      nivelAcesso: 0,
    },
    listaPermissoes: [],
    paginaAtual: 'Produtos TOP',
    menuAtivo: 0,
    listaItemsMenu: [
      {
        header: 'Principais',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 0,
        nome: 'Home',
        link: 'menu-sistema.php',
        icone: 'fas fa-home',
        nivelNecessario: [30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 104,
        nome: 'Entregas/Transações',
        link: 'marketplace.php',
        icone: 'fas fa-comments-dollar',
        nivelNecessario: [55, 56, 57],
        notificacao: 'faturamentosCliente',
      },
      {
        id: 700,
        nome: 'Grupos de Entrega',
        link: 'grupos-de-entregas.php',
        icone: 'fas fa-boxes',
        nivelNecessario: [55, 56, 57],
      },
      {
        id: 42,
        nome: 'Extratos',
        link: 'administracao-seller.php',
        icone: 'fas fa-building',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39],
        notificacao: 'marketplace',
      },
      {
        id: 16,
        nome: 'Lista de Produtos',
        link: 'produtos-lista.php',
        icone: 'fas fa-tshirt',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 99,
        nome: 'Buscar produto',
        link: 'produtos-busca.php',
        icone: 'fas fa-search',
        nivelNecessario: [32, 52, 53, 54, 55, 56, 57],
      },
      {
        header: 'Atendimento',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 109,
        nome: 'Extratos',
        link: 'extratos.php',
        icone: 'fas fa-money-check',
        nivelNecessario: [55, 56, 57],
      },
      {
        id: 4,
        nome: 'Fila de Transferências',
        link: 'lista-sellers-transferencias.php',
        icone: 'fas fa-money-check-alt',
        nivelNecessario: [56, 57],
      },
      {
        id: 62,
        nome: 'Cadastros',
        link: 'cadastros.php',
        icone: 'fas fa-user-circle',
        nivelNecessario: [57],
      },
      {
        header: 'Estoque',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 19,
        nome: 'Conferência Externos',
        link: 'processos-seller-externo.php',
        icone: 'fas fa-eye',
        nivelNecessario: [32, 50, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 2,
        nome: 'Reposições',
        link: 'reposicoes-fulfillment.php',
        icone: 'fas fa-shopping-basket',
        nivelNecessario: [32, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 18,
        nome: 'Painéis de Impressão',
        link: 'paineis-impressao.php',
        icone: 'fas fa-print',
        nivelNecessario: [55, 56, 57],
      },
      {
        id: 20,
        nome: 'Personalizar Etiquetas',
        link: `${this.urlMobileStock}layout-etiquetas`,
        icone: 'fa fa-pencil',
        nivelNecessario: [32, 52, 53, 54, 55, 56, 57],
      },
      {
        header: 'Monitoramento',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 9,
        nome: 'Monitora Iniciantes',
        link: 'monitora-novo-seller.php',
        icone: 'fas fa-star',
        nivelNecessario: [32, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 15,
        nome: 'Correção de estoque',
        link: 'produtos-corrigir-estoque.php',
        icone: 'fas fa-cogs',
        nivelNecessario: [32, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 86,
        nome: 'Monitora +Vendidos',
        link: 'monitora-mais-vendidos.php',
        icone: 'fas fa-arrow-circle-up',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 333,
        nome: 'Monitoramento Pontos',
        link: 'monitora-pontos.php',
        icone: 'fab fa-watchman-monitoring',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 374,
        nome: 'Monitoramento Entregadores',
        link: 'monitora-entregadores.php',
        icone: 'fas fa-truck-moving',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 52,
        nome: 'Monitoramento Seller',
        link: 'monitora-seller.php',
        icone: 'fas fa-store-alt',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 37,
        nome: 'Monitoramento Vendas',
        link: 'monitora-vendas.php',
        icone: 'fas fa-dollar',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 297,
        nome: 'Entregas Atrasadas',
        link: 'monitora-produtos-atrasados.php',
        icone: 'fas fa-dolly',
        nivelNecessario: [57],
      },
      {
        id: 764,
        nome: 'Monitora Sem Entrega',
        link: 'monitora-sem-entrega.php',
        icone: 'fa fa-dropbox',
        nivelNecessario: [57],
      },
      {
        id: 437,
        nome: 'Monit. Previsões',
        link: 'monitora-previsoes.php',
        icone: 'fas fa-eye',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 765,
        nome: 'Monit. Letras + Usadas',
        link: 'letras-mais-usadas.php',
        icone: 'fa fa-etsy',
        nivelNecessario: [57],
      },
      {
        id: 767,
        nome: 'Dados Bancários',
        link: 'gerencia-contas-bancarias.php',
        icone: 'fa fa-credit-card',
        nivelNecessario: [57],
      },
      {
        header: 'Administrativo',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 46,
        nome: 'Informações Gerenciais',
        link: 'produtos-mais-vendidos.php',
        icone: 'far fa-question-circle',
        nivelNecessario: [57],
      },
      {
        id: 47,
        nome: 'Alterar Datas para Troca',
        link: 'datas-entrega.php',
        icone: 'far fa-calendar',
        nivelNecessario: [57],
      },
      {
        id: 34,
        nome: 'Fila Aprovação',
        link: 'fila-tipo-frete.php',
        icone: 'fas fa-users-cog',
        nivelNecessario: [57],
      },
      {
        id: 35,
        nome: 'Gerenciar Pontos',
        link: 'gerenciar-pontos.php',
        icone: 'fas fa-gear',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 36,
        nome: 'Logs Internos',
        link: 'logs-internos.php',
        icone: 'fas fa-clipboard-list',
        nivelNecessario: [57],
      },
      {
        header: 'Outros menus',
        nivelNecessario: [53, 54, 55, 56, 57],
      },
      {
        id: 560,
        nome: 'Trocas',
        link: 'ponto-trocas.php',
        icone: 'fas fa-exchange-alt',
        nivelNecessario: [52, 57],
      },
      {
        id: 559,
        nome: 'Analise Promoções',
        link: 'analise-promocoes.php',
        icone: 'fas fa-user-secret',
        nivelNecessario: [57],
      },
      {
        id: 555,
        nome: 'Fraudes?',
        link: 'fraudes.php',
        icone: 'fas fa-user-secret',
        nivelNecessario: [52, 57],
      },
      {
        id: 554,
        nome: 'Fraude Devoluções',
        link: 'fraudes-devolucoes.php',
        icone: 'fas fa-user-secret',
        nivelNecessario: [52, 57],
      },
      {
        id: 556,
        nome: 'Pontuação Produtos',
        link: 'pontuacao-produtos.php',
        icone: 'fas fa-cubes',
        nivelNecessario: [30, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 54,
        nome: 'Estoque interno',
        link: 'fornecedor-estoque-interno-controle-estoque.php',
        icone: 'fas fa-dolly-flatbed',
        nivelNecessario: [30, 31, 33, 34, 55, 36, 37, 38, 39],
      },
      {
        id: 53,
        nome: 'Fulfillment',
        link: 'reposicoes-fulfillment.php',
        icone: 'fas fa-shopping-basket',
        nivelNecessario: [30, 31, 33, 34, 55, 36, 37, 38, 39],
      },
      {
        id: 53,
        nome: 'Separação',
        link: 'acesso.php?separacao=true',
        icone: 'fas fa-truck',
        notificacao: 'qtd_pra_separar',
        nivelNecessario: [30, 31, 33, 34, 55, 36, 37, 38, 39],
      },
      {
        id: 486,
        nome: 'Desempenho Sellers',
        link: 'desempenho-sellers.php',
        icone: 'fas fa-bar-chart',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 546,
        nome: 'Análise de Defeitos',
        link: 'solicitacoes-troca.php',
        icone: 'fas fa-exchange-alt',
        nivelNecessario: [30, 50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 150,
        nome: 'Look Pay',
        link: 'acesso.php?mobilepay=true',
        icone: 'fas fa-credit-card',
        nivelNecessario: [10, 30, 31, 33, 34, 35, 36, 37, 38, 39, 50, 51, 52, 53, 54, 55, 56, 57],
      },
      {
        id: 25,
        nome: 'Produtos',
        link: 'fornecedores-produtos.php',
        icone: 'fas fa-boxes',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39, 57],
      },
      {
        id: 26,
        nome: 'Dúvidas de Produtos',
        link: 'duvidas-produtos.php',
        icone: 'fas fa-question',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39],
        notificacao: 'duvidas_produtos',
      },
      {
        id: 27,
        nome: 'Sugestões de Clientes',
        link: 'sugestao-produtos.php',
        icone: 'fab fa-hotjar',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39, 50, 51, 52, 53, 54, 55, 56, 57],
        notificacao: 'duvidas_produtos',
      },
      {
        id: 321,
        nome: 'Categorias',
        link: 'categorias.php',
        icone: 'fab fa-searchengin',
        nivelNecessario: [57],
      },
      {
        id: 324,
        nome: 'Catalogos e Filtros',
        link: 'catalogos-personalizados.php',
        icone: 'fas fa-filter',
        nivelNecessario: [57],
      },
      {
        id: 26,
        nome: 'Promoções',
        link: 'promocoes.php',
        icone: 'fas fa-percent',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39, 57],
      },
      {
        id: 668,
        nome: 'Log Pesquisa',
        link: 'log-pesquisa-meulook.php',
        icone: 'fas fa-search',
        nivelNecessario: [50, 55, 56, 57],
      },
      {
        id: 70,
        nome: 'Retirada de Produtos',
        link: 'retirada-produtos.php',
        icone: 'fas fa-cubes',
        nivelNecessario: [30, 31, 33, 34, 35, 36, 37, 38, 39],
      },
      {
        id: 130,
        nome: 'Transação',
        link: 'transacao.php',
        icone: 'fas fa-dollar-sign',
        nivelNecessario: [56, 57],
      },
      {
        id: 38,
        nome: 'Nota Fiscal',
        link: 'fiscal-gerenciar.php',
        icone: 'fas fa-balance-scale',
        nivelNecessario: [56, 57],
      },
      {
        id: 7,
        nome: 'Fazer Login',
        link: 'cliente-login.php',
        icone: 'fa fa-user',
        nivelNecessario: [0],
      },
      {
        id: 8,
        nome: 'Cadastrar',
        link: 'cliente-cadastro.php',
        icone: 'fa fa-user',
        nivelNecessario: [0],
      },
      {
        id: 26,
        nome: 'Dashboard',
        link: 'dashboard-fornecedores.php',
        icone: 'fas fa-chart-line',
        nivelNecessario: [57],
      },
      {
        id: 183,
        nome: 'Lista de Pedidos',
        link: 'pedido-painel.php',
        icone: 'fas fa-shopping-cart',
        nivelNecessario: [50, 51, 52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 54,
        nome: 'Produtos cancelados',
        link: 'pares-corrigidos.php',
        icone: 'fas fa-crosshairs',
        nivelNecessario: [52, 53, 54, 55, 56, 57, 58, 59],
      },
      {
        id: 11,
        nome: 'Configurações',
        link: 'configuracoes-sistema.php',
        icone: 'fas fa-tools',
        nivelNecessario: [57],
      },
      {
        id: 130,
        nome: 'FAQ',
        link: 'central-helper.php',
        icone: 'fas fa-question-circle',
        nivelNecessario: [52, 53, 54, 55, 56, 57, 58, 59],
        notificacao: 'faq',
      },
      {
        id: 44,
        nome: 'Campanha Cliente',
        link: 'campanhas.php',
        icone: 'fas fa-ad',
        nivelNecessario: [51, 55, 56, 57],
      },
      {
        id: 48,
        nome: 'Central de notificações',
        link: 'central-de-notificacoes.php',
        icone: 'fas fa-check-double',
        nivelNecessario: [57],
      },
    ],
    listaFaturados: [],
    listaProdutosDisponiveis: [],
    listaProdutosReservados: [],
    listaZoopSMS: [],
    listaItemsCorrigidos: [],
    listaFaturadosLidos: [],
    listaReservadosLidos: [],
    listaDisponiveisLidos: [],
    listaItemsCorrigidosLidos: [],
    notificacoesMenuLateral: {},
    show: true,
    bancoCarrinhoDigital: null,
    versaoBanco: 100,
    token: '',
    urlMobileStock: '',
  },
  filters: {
    upperCase(value) {
      value = value.toString()
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
  },
  async mounted() {
    this.user.token = $('#cabecalhoVue input[name=userToken]').val()
    this.user.id = parseInt($('#cabecalhoVue input[name=userID]').val()) || null
    this.user.nome = $('#cabecalhoVue input[name=nomeUsuarioLogado]').val()
    this.user.idColaborador = parseInt($('#cabecalhoVue input[name=userIDCliente]').val()) || null
    api.defaults.headers.common.token = this.user.token

    this.url_gerador_qrcode = $('#cabecalhoVue input[name=url-gerador-qrcode]').val()
    this.urlMobileStock = $('#cabecalhoVue input[name=url-mobile-stock]').val()

    this.user.nivelAcesso = $('#cabecalhoVue input[name=nivelAcesso]').val()
    this.listaFaturadosLidos = window.localStorage.getItem('listaFaturadosLidos')
      ? JSON.parse(window.localStorage.getItem('listaFaturadosLidos'))
      : []
    this.listaDisponiveisLidos = window.localStorage.getItem('listaDisponiveisLidos')
      ? JSON.parse(window.localStorage.getItem('listaDisponiveisLidos'))
      : []
    this.listaReservadosLidos = window.localStorage.getItem('listaReservadosLidos')
      ? JSON.parse(window.localStorage.getItem('listaReservadosLidos'))
      : []
    this.listaItemsCorrigidosLidos = window.localStorage.getItem('listaItemsCorrigidosLidos')
      ? JSON.parse(window.localStorage.getItem('listaItemsCorrigidosLidos'))
      : []
    this.menuAtivo = window.localStorage.getItem('menuAtivo')
      ? window.localStorage.getItem('menuAtivo')
      : this.user.nivelAcesso >= 50 && this.user.nivelAcesso <= 59
        ? 0
        : 2
    this.$nextTick(this.buscaPermissoes)
  },
  async created() {
    document.querySelector('#cabecalhoVue').classList.remove('d-none')
  },
  computed: {
    quantidadeTotalNotificacoes: function () {
      total =
        this.listaProdutosDisponiveis.length +
        this.listaProdutosReservados.length +
        this.listaZoopSMS.length +
        this.listaFaturados.length +
        this.listaItemsCorrigidos.length -
        (this.listaFaturadosLidos.length +
          this.listaReservadosLidos.length +
          this.listaDisponiveisLidos.length +
          this.listaItemsCorrigidosLidos.length)

      return total >= 0 ? total : 0
    },
  },
  methods: {
    limpaVariaveis() {
      this.listaFaturados = []
      this.listaProdutosDisponiveis = []
      this.listaProdutosReservados = []
      this.listaZoopSMS = []
      this.listaFaturadosLidos = []
      this.listaReservadosLidos = []
      this.listaDisponiveisLidos = []
      window.localStorage.removeItem('listaFaturadosLidos')
      window.localStorage.removeItem('listaReservadosLidos')
      window.localStorage.removeItem('listaDisponiveisLidos')
      window.localStorage.removeItem('listaFaturadosLidos')
    },
    async buscaPermissoes() {
      const resposta = await api.get('api_administracao/cadastro/busca/colaboradores')
      const idColaborador = resposta.data.id_colaborador

      const retorno = await MobileStockApi(`api_administracao/cadastro/busca_permissoes/${idColaborador}`).then(
        (resp) => resp.json(),
      )

      this.listaPermissoes = retorno.data.todas_permissoes.filter(
        (permissao) =>
          resposta.data?.permissao.includes(parseInt(permissao.nivel_value)) &&
          !(permissao.nivel_value >= 10 && permissao.nivel_value <= 19),
      )

      this.buscaQuantidadeSeparacao()
    },
    async mudaAcessoPrincipal(idPermissao) {
      await api.patch('api_administracao/cadastro/acesso_principal', {
        id_usuario: this.user.id,
        acesso: idPermissao,
      })
      this.user.nivelAcesso = idPermissao
      const parametros = new URLSearchParams({
        token: this.user.token,
      })
      window.location.href = `/acesso.php?${parametros}`
    },
    exibeMenu(nivelNecessario) {
      if (this.user.id != 0 && nivelNecessario == 0) {
        return false
      }

      if (nivelNecessario.includes(parseInt(this.user.nivelAcesso))) {
        return true
      }

      return false
    },
    ativaMenu(idLinha) {
      // Caso seja selecionado o mobilepay, não fazemos highlight.
      if (idLinha == 15) return
      else {
        window.localStorage.setItem('menuAtivo', idLinha)
        this.menuAtivo = idLinha
      }
    },
    classesListaFaturado(item, index) {
      for (const key in this.listaFaturadosLidos) {
        if (this.listaFaturadosLidos[key] == index) {
          return ''
        }
      }
      switch (item.status_atual) {
        case 'separado':
          return 'alert-primary'
        case 'conferido':
          return 'alert-info'
        case 'expedido':
          return 'alert-warning'
        case 'entregue':
          return 'alert-success'
        case 'aguardando_pagamento':
          return 'alert-danger'
        case 'faturado':
          return 'alert-success'

        default:
          return 'alert-primary'
      }
    },
    marcaFaturadoLido(index, event) {
      if (event) {
        //previne que feche o menu de notificações
        event.stopPropagation()
      }
      if (this.listaFaturadosLidos.indexOf(index) != -1) {
        return false
      }

      this.listaFaturadosLidos.push(index)

      window.localStorage.setItem('listaFaturadosLidos', JSON.stringify(this.listaFaturadosLidos))
    },
    marcaDisponivelLido(index, event = '') {
      if (event) {
        //previne que feche o menu de notificações
        event.stopPropagation()
      }

      if (this.listaDisponiveisLidos.indexOf(index) != -1) {
        return
      }

      this.listaDisponiveisLidos.push(index)

      window.localStorage.setItem('listaDisponiveisLidos', JSON.stringify(this.listaDisponiveisLidos))
    },
    verificaDisponivelLido(index) {
      if (this.listaDisponiveisLidos.indexOf(index) != -1) {
        return false
      }

      return true
    },
    marcaReservadoLido(index, event = '') {
      if (event) {
        //previne que feche o menu de notificações
        event.stopPropagation()
      }

      if (this.listaReservadosLidos.indexOf(index) != -1) {
        return
      }

      this.listaReservadosLidos.push(index)

      window.localStorage.setItem('listaReservadosLidos', JSON.stringify(this.listaReservadosLidos))
    },
    verificaReservadoLido(index) {
      if (this.listaReservadosLidos.indexOf(index) != -1) {
        return false
      }
      return true
    },
    marcaCorrigidoLido(index, event = '') {
      if (event) {
        //previne que feche o menu de notificações
        event.stopPropagation()
      }

      if (this.listaItemsCorrigidosLidos.indexOf(index) != -1) {
        return
      }

      this.listaItemsCorrigidosLidos.push(index)

      window.localStorage.setItem('listaItemsCorrigidosLidos', JSON.stringify(this.listaItemsCorrigidosLidos))
    },
    verificaCorrigidoLido(index) {
      if (this.listaItemsCorrigidosLidos.indexOf(index) != -1) {
        return false
      }
      return true
    },
    tipoNotificaoMenuLateral(tipoNotificacao) {
      switch (tipoNotificacao) {
        case 'qtdReembolso':
          return this.notificacoesMenuLateral.qtdReembolso
        case 'pedidosAbertos':
          return this.notificacoesMenuLateral.pedidosAbertos
        case 'qtdParesTroca':
          return this.notificacoesMenuLateral.qtdParesTroca
        case 'faturamentosCliente':
          return this.notificacoesMenuLateral.faturamentosCliente
        case 'avaliacaoProdutos':
          return this.notificacoesMenuLateral.avaliacaoProdutos
        case 'marketplace':
          return this.notificacoesMenuLateral.marketplace
        case 'atendimentos':
          return this.notificacoesMenuLateral.atendimentos
        case 'qtdAtendimentos':
          return this.notificacoesMenuLateral.qtdAtendimentos
        case 'qtdSeparacao':
          return this.notificacoesMenuLateral.qtdSeparacao
        case 'marketplace_fiscal':
          return this.notificacoesMenuLateral.marketplace_fiscal
        case 'faq':
          return this.notificacoesMenuLateral.faq
        case 'duvidas_produtos':
          return this.notificacoesMenuLateral.duvidas_produtos
        case 'qtd_pra_separar':
          return this.notificacoesMenuLateral.qtd_pra_separar
        default:
          return ''
      }
    },
    verNotificacoes() {
      window.location.href = '/central-de-notificacoes.php'
    },
    async buscaQuantidadeSeparacao() {
      if (
        this.listaPermissoes.some((permissao) => {
          const nivelValue = parseInt(permissao.nivel_value)
          return nivelValue >= 30 && nivelValue <= 39
        })
      ) {
        const resposta = await api.get('/api_estoque/separacao/quantidade_demandando_separacao')
        this.$set(this.notificacoesMenuLateral, 'qtd_pra_separar', resposta.data)
      }
    },
  },
})

jQuery(document).ready(function () {})

function getCookie(campo) {
  const valor = document.cookie
    .split('; ')
    .find((row) => row.startsWith(campo))
    .split('=')[1]
  return valor
}
