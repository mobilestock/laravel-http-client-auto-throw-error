Vue.component("trocas-confirmadas", {
  template: "#trocas-confirmadas",
  data() {
    return {
      id_cliente: document.querySelector("#idCliente").value,
      filtroTrocasPendentesConfirmadas: "",
      loadingTrocaPendente: false,
      trocasPendentesConfirmadas: [],
      cabecalhoTrocasPendentesConfirmadas: [
        { text: "ID", value: "id_produto" },
        { text: "Produto", value: "produto" },
        { text: "Tamanho", value: "nome_tamanho" },
        { text: "Data Compra", value: "data_compra" },
        { text: "Data Devolução", value: "data_hora" },
        { text: "Preço", value: "preco" },
        { text: "taxa", value: "taxa" },
        { text: "Usuário", value: "nome" },
        { text: "Ponto", value: "nome_entregador" },
        { text: "Defeito", value: "descricao_defeito" },
        { text: "Ações", value: "acoes" },
        { text: "cor", value: "cores" },
      ],
      expanded: false,
      itemExpand: [],
    };
  },
  methods: {
    async buscaTrocasPendentesConfirmadas() {
      this.overlay = true;
      await MobileStockApi(
        `api_administracao/troca/trocas_pendente_confirmadas/${this.id_cliente}`
      )
        .then((r) => r.json())
        .then((resp) => {
          if (resp.status) {
            this.trocasPendentesConfirmadas = resp.data;
          }
          this.overlay = false;
        });
    },
    // async removerItemTrocaPendenteConfirmada(item) {
    //   this.overlay = true;
    //
    //   let form = new FormData();
    //   form.append("uuid", item.uuid);
    //
    //   let json = await fetch("src/controller/Troca/TrocaPendenteRemover.php", {
    //     method: "post",
    //     body: form,
    //   }).then((r) => r.json());
    //
    //   if (json.status === true) {
    //     this.trocasPendentesConfirmadas =
    //       this.trocasPendentesConfirmadas.filter((produto) => produto !== item);
    //   } else {
    //     $.alert({
    //       title: "Erro!",
    //       icon: "fa fa-warning",
    //       type: "red",
    //       content: json.mensagem,
    //     });
    //   }
    //   this.overlay = false;
    // },
    expandePainel() {
      alert("eqwewq");
    },
    calculaCorItemTabela(item) {
      return item.uuid === document.querySelector("#uuid-identificacao").value
        ? "bg-danger"
        : "";
    },
    gerarEtiquetasTrocaConfirmata() {
      let grupo = this.trocasPendentesConfirmadas
        .filter((el) => el.marcado_etiqueta)
        .map((item) => {
          console.log(item);
          json = {
            referencia: `${item.id_produto} - ${item.produto} - ${item.cores}`,
            tamanho: item.nome_tamanho,
            cod_barras: item.cod_barras,
            localizacao: item.localizacao,
          };
          return json;
        });
      var filename = "grupo_etiqutas";
      json = JSON.stringify(grupo);
      var blob = new Blob([json], { type: "json" });
      saveAs(blob, filename + ".json");
    },
  },
  filters: {
    formataData: function (value) {
      var data = new Date(value);
      (dia = data.getDate().toString()),
        (diaF = dia.length == 1 ? "0" + dia : dia),
        (mes = (data.getMonth() + 1).toString()), //+1 pois no getMonth Janeiro começa com zero.
        (mesF = mes.length == 1 ? "0" + mes : mes),
        (anoF = data.getFullYear());
      return `${diaF}/${mesF}/${anoF} `;
    },
  },
  mounted() {
    this.buscaTrocasPendentesConfirmadas();
  },
});

Vue.component("confirmar-troca", {
  template: "#trocas-agendadas",
  data() {
    return {
      loadingTrocaPendente: false,
      toggleHistorico: false,
      fornecedores: [],
      historicos: [],
      paginaHistorico: 1,
      fornecedorFiltro: '',
      categoriaFiltro: [],
      corFiltro: '',
      faturamentoFiltro: '',
      linhaFiltro: '',
      listaProdutosAdicionados: [],
      overlay: false,
      abrirPaineis: false,
      linhas: [''],
      categorias: [''],
      numeracao: [''],
      tamanhoFiltro: '',
      filtroProdutosAdicionados: '',
      id_cliente: document.querySelector('#idCliente').value,
      cabecalhos: [
        {
          text: 'Produto',
          value: 'descricao',
          align: 'start',
        },
        {
          text: 'Tamanho',
          value: 'nome_tamanho',
        },
        {
          text: 'Data compra',
          value: 'data_hora',
        },
        {
          text: 'Preco',
          value: 'preco',
        },
        {
          text: 'Taxas',
          value: 'taxa',
        },
        {
          text: 'Usuário',
          value: 'usuario',
        },
        {
          text: 'Defeito',
          value: 'defeito',
        },
        {
          text: 'Descrição defeito:',
          value: 'descricao_defeito',
        },
        {
          text: 'Remover',
          value: 'acoes',
        },
        {
          text: 'cor',
          value: 'cores',
        },
      ],
      inputError: '',
      filtroCodigoBarras: '',

      listaTrocasAgendadas: [],
      listaProdutosAdicionados: [],
      codBarrasPesquisa: '',
      idCliente: document.querySelector('#idCliente').value,
      paginaTrocasAgendadas: 1,
      filtroTrocasConferir: '',
      categoriaAutocomplete: '',
    }
  },
  methods: {
    removerItemListaProdutosAdicionados(item) {
      this.listaProdutosAdicionados = this.listaProdutosAdicionados.filter((produto) => produto !== item)
    },
    fecharPainel: function (index) {
      var painel = document.querySelector('#painel_' + index)
      if (painel.style.display != 'none') {
        painel.style.display = 'none'
      } else {
        painel.style.display = 'block'
      }
    },
    adicionarItem: function (item) {
      this.listaTrocasAgendadas.push({
        id_produto: item.id_produto,
        produto: item.descricao,
        caminho: item.fotoProduto,
        uuid: item.uuid,
        preco: item.preco,
        taxa: item.taxa,
        nome_tamanho: item.nome_tamanho,
        correto: false,
        incorreto: false,
        defeito: false,
        alerta_defeito: false,
        descricao_defeito: '',
        taxa_real: item.taxa,
        pacIndevido: false,
        cod_barras: item.cod_barras,
        cor: item.cores,
      })

      // this.adicionaItem(
      //   this.listaTrocasAgendadas[this.listaTrocasAgendadas.length - 1]
      // );
    },
    async confirmaTroca() {
      this.loadingTrocaPendente = true
      self = this
      $.confirm({
        title: 'Alerta',
        content: `
            <h5>Deseja confirmar ${this.listaProdutosAdicionados.length} pares na troca?</h5>
            <small>Ao confirmar serão gerados créditos para o cliente e débitos para o fornecedor</small>
        `,
        type: 'red',
        buttons: {
          Sim: {
            btnClass: 'btn btn-danger text-white',
            async action() {
              let passar = true

              self.listaProdutosAdicionados.forEach((el) => {
                if (el.defeito) {
                  if (el.descricao_defeito === undefined || el.descricao_defeito == '') {
                    passar = false
                  } else {
                    passar = true
                  }
                }
              })

              if (passar === false) {
                $.alert({
                  title: 'Erro!',
                  icon: 'fa fa-warning',
                  type: 'red',
                  content: 'Preencha o campo defeito',
                })
                self.loadingTrocaPendente = false
                return
              }

              if (self.listaProdutosAdicionados.length) {
                try {
                  const retorno = await MobileStockApi('api_administracao/troca/confirmar_troca', {
                    method: 'POST',
                    body: JSON.stringify(
                      self.listaProdutosAdicionados.map((el) => {
                        return {
                          uuid: el.uuid,
                          defeito: el.defeito || false,
                          descricao_defeito: el.descricao_defeito || '',
                          pacIndevido: el.pacIndevido || false,
                          cliente_enviou_errado: !el.correto,
                          agendada: el.agendada || false,
                        }
                      }),
                    ),
                  })

                  if (retorno.status !== 200) throw new Error((await retorno.json()).message)

                  $.dialog({
                    title: 'Sucesso!',
                    content: 'Troca confirmada com sucesso!',
                    type: 'blue',
                  })
                  self.listaProdutosAdicionados = []

                  self.buscaTrocasAgendadas()
                } catch (error) {
                  $.dialog({
                    title: 'Alerta',
                    type: 'red',
                    content: error.message || 'Não foi possível confirmar a troca!',
                    icon: 'fa fa-warning',
                  })
                } finally {
                  self.loadingTrocaPendente = false
                }
              }
            },
          },
          Não() {
            self.loadingTrocaPendente = false
          },
        },
      })
    },
    async filtrar() {
      this.overlay = true

      await MobileStockApi('api_administracao/troca/busca_itens_comprados_parametros', {
        method: 'POST',
        body: JSON.stringify({
          id_cliente: this.id_cliente,
          id_fornecedor: this.fornecedorFiltro,
          id_faturamento: this.faturamentoFiltro,
          id_categorias: this.categoriaFiltro,
          id_linha: this.linhaFiltro,
          nome_tamanho: this.tamanhoFiltro,
          descricao: this.corFiltro,
          cod_barras: this.filtroCodigoBarras.split('_')[0],
          pagina: this.paginaHistorico,
        }),
      })
        .then((resp) => resp.json())
        .then((resp) => {
          if (resp.status) {
            this.historicos = resp.data
            for (let i in Object.keys(this.historicos)) {
              const index = Object.keys(this.historicos)[i]
              const faturamento = this.historicos[index]

              for (let indice in faturamento) {
                item = faturamento[indice]
              }
            }
            this.overlay = false
            if (this.toggleHistorico === false) this.toggleHistorico = true
          }
        })
    },
    reset() {
      this.overlay = true
      this.fornecedorFiltro = ''
      this.categoriaFiltro = []
      this.corFiltro = ''
      this.faturamentoFiltro = ''
      this.linhaFiltro = ''
      this.tamanhoFiltro = ''
      this.historicos = []
      this.overlay = false
      this.paginaHistorico = 1
    },
    async buscaFornecedores() {
      await $.ajax({
        url: 'src/controller/Troca/TrocaPendenteItens.php',
        method: 'POST',
        datatype: 'json',
        data: {
          action: 'buscaFornecedores',
        },
      }).done((resultado) => {
        json = JSON.parse(resultado)
        arr = Object.entries(json.data)
        arr.forEach((element) => {
          this.fornecedores.push(element[1])
        })
      })
    },
    async buscaCategorias() {
      let json = await MobileStockApi('api_administracao/categorias').then((r) => r.json())
      let json2 = await MobileStockApi('api_administracao/linhas').then((r) => r.json())

      this.linhas = json2.data.linhas
      this.categorias = json.data.categorias
    },
    async buscaTrocasAgendadas() {
      await MobileStockApi(`api_administracao/troca/busca_trocas_agendadas/${this.id_cliente}`)
        .then((resp) => resp.json())
        .then((resp) => {
          this.listaTrocasAgendadas = resp.data
          this.listaTrocasAgendadas.forEach((troca) => {
            if (troca.defeito) {
              this.adicionaItem(troca)
            }
          })
        })
    },

    adicionaItem(item) {
      if (this.listaProdutosAdicionados.find((el) => el.uuid === item.uuid)) {
        item.taxa = item.taxa_real
        this.listaProdutosAdicionados = this.listaProdutosAdicionados.filter((el) => el.uuid !== item.uuid)
      } else {
        this.listaProdutosAdicionados.push(item)
      }
    },

    bipaProdutoCodBarras() {
      const pesquisa = this.codBarrasPesquisa.split('_')[0]
      let troca = this.listaTrocasAgendadas.filter(
        (el) =>
          el.cod_barras === pesquisa &&
          !this.listaProdutosAdicionados.find((itemAdicionado) => itemAdicionado.uuid === el.uuid),
      )[0]

      if (!troca) {
        $.dialog({
          title: 'Alerta',
          type: 'red',
          content: 'Nenhum produto encontrado!',
          icon: 'fa fa-warning',
        })
        return
      }
      troca.defeito = false
      troca.correto = true
      this.adicionaItem(troca)
      this.codBarrasPesquisa = ''
    },
  },
  filters: {
    filtraIdFaturamento: function (value) {
      return 'Número do pedido: ' + value
    },
    formataData: function (value) {
      var data = new Date(value)
      ;(dia = data.getDate().toString()),
        (diaF = dia.length == 1 ? '0' + dia : dia),
        (mes = (data.getMonth() + 1).toString()), //+1 pois no getMonth Janeiro começa com zero.
        (mesF = mes.length == 1 ? '0' + mes : mes),
        (anoF = data.getFullYear())
      return `${diaF}/${mesF}/${anoF} `
      // let string = `${data.toLocaleDateString("pt-BR", {
      //     timeZone: "UTC",
      //     weekday: "long",
      //     year: "numeric",
      //     month: "long",
      //     day: "numeric",
      // })} as ${data.getHours()}:${data.getMinutes()}`;
      // return string.charAt(0).toUpperCase() + string.slice(1);
    },
    dinheiro(value) {
      return value.toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
    dinheiro(value) {
      return value.toLocaleString('pt-br', {
        style: 'currency',
        currency: 'BRL',
      })
    },
  },
  computed: {
    ordernarLista() {
      // var lista = [];
      // Object.keys(this.historicos)
      //   .sort((a, b) => b - a)
      //   .forEach((historico) => {
      //     itemAtual = this.historicos[historico];
      //     lista.push(itemAtual);
      //   });

      return this.historicos
    },
    calculaValorTaxas() {
      return this.listaProdutosAdicionados
        .reduce((valorTotalTaxas, item) => (valorTotalTaxas += item.taxa), 0)
        .toFixed(2)
    },
    calculaValorCredito() {
      return this.listaProdutosAdicionados
        .reduce((valorTotalCredito, item) => (valorTotalCredito += parseFloat(item.preco)), 0)
        .toFixed(2)
    },
    valorTotalTrocasAgendadas() {
      return this.listaProdutosAdicionados.reduce((total, item) => (total += item.preco), 0)
    },

    valorTotalTaxaTrocasAgendadas() {
      return this.listaProdutosAdicionados.reduce((total, item) => (total += item.taxa), 0)
    },

    dataIncercaoUltimaTroca() {
      return this.listaTrocasAgendadas.length ? this.listaTrocasAgendadas[0].data_hora : ''
    },
  },
  mounted() {
    for (let i = 13; i <= 50; i++) {
      this.numeracao.push({ name: i, value: i })
    }
    this.buscaTrocasAgendadas()
    this.toggleHistorico = true
  },
  watch: {
    toggleHistorico(val) {
      if (val === false) this.reset()
      if (val === true && this.categorias.length === 1) {
        this.buscaFornecedores()
        this.buscaCategorias()
      }
    },
    listaProdutosAdicionados(NEWs, OLDs) {
      NEWs.forEach((NEW) => {
        if (NEW.pacIndevido === true) NEW.taxa = NEW.taxa_real + 10.0
        else if (NEW.defeito === true) NEW.taxa = NEW.agendada === true ? 0.0 : 5.0
        else if (NEW.incorreto === true) NEW.taxa = NEW.taxa_real + 2.0
        else if (NEW.defeito === false) NEW.descricao_defeito = ''
        else NEW.taxa = NEW.taxa_real
      })
    },
  },
})

var trocaPendenteVue = new Vue({
  el: '#app',
  vuetify: new Vuetify(),
  data() {
    return {
      tabs: [
        {
          nome: 'Confirmar trocas',
          icone: 'fa fa-refresh',
        },
        {
          nome: 'Trocas confirmadas',
          icone: 'far fa-calendar-check',
        },
      ],
      tabAtual: 0,
      pendentes: [],
      redirect: document.querySelector('#redirect').value == 1,
    }
  },
  mounted() {
    this.tabAtual = this.redirect === true ? 1 : 0
  },
  methods: {
    voltar() {
      history.back()
    },
    mudaTab(e) {
      this.tabAtual = 1
      this.pendentes = e
    },
  },
})

Object.filter = (obj, predicate) =>
  Object.keys(obj)
    .filter((key) => predicate(obj[key]))
    .reduce((res, key) => ((res[key] = obj[key]), res), {})

Object.size = function (obj) {
  var size = 0,
    key
  for (key in obj) {
    if (obj.hasOwnProperty(key)) size++
  }
  return size
}
