import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

var geraEtiquetaAvulsaVUE = new Vue({
  el: "#geraEtiquetaAvulsaVUE",
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
    },
  }),
  data: {
      pesquisa: "",
      carregando: false,
      produto: null,
      snackbar: false,
      idFaturamento: null,
      textoSnackbar: "",
      colorSnackbar: "error",

  },
  methods: {
    async buscaProduto() {
      try {
        
        this.carregando = true

        const resposta = await MobileStockApi(`api_administracao/produtos/busca_etiquetas_avulsa/${this.pesquisa}`)
        .then((resp) => resp.json())



        const lista = resposta.data?.lista.map(item =>{
          item.quantidade = 0
          return item;
        })
        this.produto = {
        cores: resposta.data.cores,
          descricao: resposta.data.descricao,
          id: resposta.data.id,
          foto: resposta.data.foto,
          nome: resposta.data.nome,
          lista
        }
      } catch (error) {
        enqueueSnackbar(true,'error',error?.response?.data?.message ||"falha ao buscar o produto")
      }finally{
        this.carregando = false
      }

    },
    imprimeEtiquetas(){
      if(!this.produto?.lista?.length){
        enqueueSnackbar(true,'error',"Escolha um produto")
      }

      const referencia = `${this.produto.id} ${this.produto.descricao} ${this.produto.cores}`
      const nomeDoArquivo = `etiqueta_unitaria_${this.produto.id}.json`

      const etiquetasFormatadas = []
      this.produto?.lista.filter(item =>{
        if(item.quantidade){
          const etiquetas = Array(parseInt(item.quantidade)).fill().map(arr =>{
            return {
              referencia,
              tamanho: item.tamanho,
              cod_barras: item.cod_barras
            }
          })
          etiquetasFormatadas.push(...etiquetas)
        }
      })
      const blob = new Blob([JSON.stringify(etiquetasFormatadas)],{ type: "json"})
      saveAs(blob,nomeDoArquivo)


    },
    enqueueSnackbar(ativar = false, cor = "error", texto = "Erro") {
      this.snackbar = ativar;
      this.colorSnackbar = cor;
      this.textoSnackbar = texto;
    },
  }
});
