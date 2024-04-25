import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

const monitoraLetrasPontosMaisUsados = new Vue({
    el: "#monitoraLetrasPontosMaisUsados",
    vuetify: new Vuetify({
        lang: {
            locales: {pt},
            current: "pt",
        },
    }),
    data() {
        return {
            isLoading: false,
            listaDados: [],
            dadosModal: {},
            headerDados: [
                this.itemGrades("Posição", "posicao"),
                this.itemGrades("Letra", "letra"),
                this.itemGrades("Quantidade", "quantidade"),
                this.itemGrades("Percentual", "percentual"),
            ],
            headerModal: [
                this.itemGrades("Nome", "nome"),
                this.itemGrades("Quantidade", "quantidade")
            ],
            snackbar: {
                ativar: false,
                texto: "",
                cor: "error",
                tempo: 5000,
            },
            data_de: "",
            data_ate: "",
            modal: false,
        };
    },
    methods: {
        itemGrades(
            campo,
            valor,
            ordernavel = false,
            classe = "text-light grey darken-3"
        ) {
            return {
                text: campo,
                value: valor,
                sortable: ordernavel,
                class: classe,
                align: "center",
            };
        },
        async buscaDadosLetrasMaisUsadas() {
            this.isLoading = true;
            try{
                const dataAte = new Date();
                const dataDe = new Date();
                dataDe.setMonth(dataDe.getMonth() - 1);

                const dataFormatada = (data) =>
                `${(data.getDate()).toString().padStart(2, '0')}/${(data.getMonth() + 1).toString().padStart(2, '0')}/${data.getFullYear()}`;

                this.data_de = dataFormatada(dataDe);
                this.data_ate = dataFormatada(dataAte);

                const resp = await MobileStockApi("api_administracao/entregas/busca_letras_pontos_mais_usados")
                const data = await resp.json();

                if (data?.message !== undefined) throw new Error(data.message);
                this.listaDados = data.resposta;
                this.listaDados.quantidadeTotal = data.total;
                this.listaDados.forEach((item, index) => {
                    item.posicao = index + 1;
                });
            }catch (error){
                this.enqueueSnackbar(error)
            }finally {
                this.isLoading = false;
            }
        },

        abrirModal(item) {
            this.modal = true;
            this.dadosModal = item
          },

        enqueueSnackbar(
            texto = "Erro, contate a equipe de T.I.",
            cor = "error",
            tempo = 5000
        ) {
            this.snackbar = {
                ativar: true,
                texto: texto,
                cor: cor,
                tempo: tempo,
            };
        },
    },
    mounted() {
        this.buscaDadosLetrasMaisUsadas();
    },
});
