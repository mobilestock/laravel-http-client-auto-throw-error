import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

new Vue({
    el: "#monitoraMaisVendidosVUE",
    vuetify: new Vuetify({
        lang: {
            locales: { pt },
            current: "pt",
        },
    }),
    data() {
        return {
            carregando: false,
            statusModalQR: false,
            statusModalPermissao: false,
            maisPags: false,
            pesquisa: "",
            totalProdutos: 0,
            listaProdutos: [],
            headerProdutos: [
                this.itemGrades("ID produto", "id_produto", true),
                this.itemGrades("Produto", "foto_produto"),
                this.itemGrades("Quantidade Vendas", "qtd_vendas", true),
                this.itemGrades("Fornecedor", "razao_social"),
                this.itemGrades("Telefone Fornecedor", "telefone"),
                this.itemGrades("Fulfillment", "possui_permissao"),
            ],
            filtros: {
                pagina: 1,
                dataInicial: "",
                menuData: false,
            },
            snackbar: {
                ativar: false,
                cor: "",
                tempo: 5000,
                texto: "",
            },
            dadosModalQR: {
                nome: "",
                qrCode: "",
                reputacao: "",
            },
            dadosModalPermissao: {},
        };
    },
    methods: {
        async buscaProdutos() {
            try {
                this.carregando = true;

                const parametros = new URLSearchParams({
                    pagina: this.filtros.pagina || 1,
                    data_inicial: this.filtros.dataInicial || "",
                });

                await MobileStockApi(
                    `api_administracao/produtos/busca/produtos_mais_vendidos?${parametros}`
                )
                    .then((resp) => resp.json())
                    .then((resp) => {
                        if (!resp.status) throw new Error(resp.message);

                        const consulta = resp.data;
                        if (this.filtros.pagina <= 1) {
                            this.listaProdutos = consulta.produtos;
                        } else {
                            this.listaProdutos = this.listaProdutos.concat(consulta.produtos);
                        }
                        this.maisPags = consulta.mais_pags;
                        this.totalProdutos = consulta.total;
                    });
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregando = false;
            }
        },
        async atualizaPermissaoFulfillment() {
            try {
                this.carregando = true;

                await MobileStockApi(
                    "api_administracao/produtos/permissao_repor_fulfillment",
                    {
                        method: "PATCH",
                        body: JSON.stringify({
                            id_produto: this.dadosModalPermissao.id_produto,
                            autorizado: !this.dadosModalPermissao.possui_permissao,
                        }),
                    }
                )
                    .then((resp) => resp.json())
                    .then((resp) => {
                        if (!resp.status) throw new Error(resp.message);

                        const index = this.listaProdutos.findIndex(
                            (produto) =>
                                produto.id_produto === this.dadosModalPermissao.id_produto
                        );
                        if (index === false) {
                            this.enqueueSnackbar(resp.message, "success");
                            document.location.reload();

                            return;
                        }

                        this.listaProdutos[index].possui_permissao =
                            !this.listaProdutos[index].possui_permissao;
                        this.gerirModalPermissao();
                        this.enqueueSnackbar(resp.message, "success");
                    });
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregando = false;
            }
        },
        converteData(data) {
            if (!data) return "";

            const dataFormatada = data
                .toString()
                .substring(0, 10)
                .split("-")
                .reverse()
                .join("/");

            return dataFormatada;
        },
        corPorReputacao(reputacao = "NOVATO") {
            switch (reputacao) {
                case "RUIM":
                    return "error";
                case "REGULAR":
                    return "amber";
                case "EXCELENTE":
                    return "success";
                case "MELHOR_FABRICANTE":
                    return "primary";
                default:
                    return;
            }
        },
        itemGrades(
            campo,
            valor,
            ordernavel = false,
            estilizacao = "text-light grey darken-3"
        ) {
            return {
                text: campo,
                value: valor,
                sortable: ordernavel,
                class: estilizacao,
                align: "center",
            };
        },
        gerirFiltros(pagina = 1, dataInicial = "", menuData = false) {
            this.filtros = {
                menuData: menuData,
                pagina: pagina,
                dataInicial: dataInicial,
            };
        },
        gerirModalQrCode(
            status = false,
            telefone = "",
            nomeColaborador = "",
            reputacao = "",
            produto = {
                id: 0,
                nome: '',
            },
        ) {
            let qrCode = '';
            if (status) {
                const texto = `Olá, notamos que o produto *(${produto.id}) ${produto.nome}* está com um ótimo número de vendas na plataforma.\nGostaria de fazer uma reposição dele em nosso estoque Fulfillment?`;
                telefone = telefone.replace(/[^0-9]+/gi, "");
                qrCode = encodeURIComponent(new MensagensWhatsApp({
                    mensagem: texto,
                    telefone: telefone
                }).resultado);
            }

            this.dadosModalQR = {
                nome: nomeColaborador,
                qrCode: qrCode,
                reputacao: reputacao,
            };
            this.statusModalQR = status;
        },
        gerirModalPermissao(status = false, produto = {}) {
            this.dadosModalPermissao = produto;
            this.statusModalPermissao = status;
        },
        enqueueSnackbar(
            texto = "Erro, contate a equipe de T.I.",
            cor = "error",
            tempo = 5000
        ) {
            this.snackbar = {
                ativar: true,
                cor: cor,
                tempo: tempo,
                texto: texto,
            };
        },
    },
    computed: {
        mostraReputacaoAtual() {
            const reputacao = this.dadosModalQR.reputacao.replace(/_/i, " ");

            return reputacao;
        },
        textoFiltroData() {
            const texto = this.filtros.dataInicial
                ? `Filtrando data: ${this.converteData(this.filtros.dataInicial)}`
                : "Selecionar data inicial";

            return texto;
        },
    },
    mounted() {
        this.buscaProdutos();
    },
    watch: {
        "filtros.pagina"(valor) {
            this.gerirFiltros(valor, this.filtros.dataInicial);

            this.buscaProdutos();
        },
        "filtros.dataInicial"(valor) {
            if (valor === this.filtros.dataInicial) {
                this.gerirFiltros(this.filtros.pagina, valor);
            } else {
                this.gerirFiltros(0, valor);
            }

            this.buscaProdutos();
        },
    },
});
