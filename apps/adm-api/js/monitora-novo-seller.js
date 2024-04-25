import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js";

new Vue({
    el: "#monitoraNovoSellerVUE",
    vuetify: new Vuetify({
        lang: {
            locales: {pt},
            current: "pt",
        },
    }),
    data() {
        return {
            areaAtual: "ESTOQUE",
            carregando: false,
            abrirModal: false,
            dadosModal: {
                nome: "",
                qrCode: "",
            },
            snackbar: {
                ativar: false,
                cor: "",
                tempo: 5000,
                texto: "",
            },

            ESTOQUE_pagina: 1,
            ESTOQUE_mais_pags: false,
            ESTOQUE_pesquisa: "",
            ESTOQUE_lista_fornecedor: [],
            ESTOQUE_header_fornecedores: [
                this.itemGrades("Fornecedor", "razao_social"),
                this.itemGrades("Telefone", "telefone"),
                this.itemGrades("Produtos Cadastrados", "qtd_produtos", true),
                this.itemGrades("Reposição Externa", "bloqueado_repor"),
                this.itemGrades("Estou Ciente", "estou_ciente"),
            ],

            VENDA_pagina: 1,
            VENDA_mais_pags: false,
            VENDA_pesquisa: "",
            VENDA_lista_fornecedor: [],
            VENDA_header_fornecedores: [
                this.itemGrades("Fornecedor", "razao_social"),
                this.itemGrades("Telefone", "telefone"),
                this.itemGrades("Quantidade Vendas", "qtd_vendas", true),
                this.itemGrades("Estou Ciente", "estou_ciente"),
            ],
        };
    },
    methods: {
        async buscaListaFornecedores(inicial = true, pagina = 1) {
            try {
                this.carregando = true;
                let idVisualizados = '';

                switch (this.areaAtual) {
                    case "ESTOQUE":
                        if (
                            this.ESTOQUE_lista_fornecedor.length > 0 &&
                            pagina === this.ESTOQUE_pagina
                        ) {
                            return;
                        }

                        if (
                            this.ESTOQUE_lista_fornecedor.length > 0 &&
                            this.ESTOQUE_lista_fornecedor.length < 50
                        ) {
                            this.enqueueSnackbar(
                                "Não possui mais resultados pra mostrar",
                                "amber"
                            );
                            this.carregando = false;
                            return;
                        }

                        idVisualizados = localStorage.getItem('ESTOQUE_id_visualizado') || '';
                        this.ESTOQUE_pagina = pagina;
                        break;
                    case "VENDA":
                        if (
                            this.VENDA_lista_fornecedor.length > 0 &&
                            pagina === this.VENDA_pagina
                        ) {
                            return;
                        }

                        if (
                            this.VENDA_lista_fornecedor.length > 0 &&
                            this.VENDA_lista_fornecedor.length < 50
                        ) {
                            this.enqueueSnackbar(
                                "Não possui mais resultados pra mostrar",
                                "amber"
                            );
                            this.carregando = false;
                            return;
                        }

                        idVisualizados = localStorage.getItem('VENDA_id_visualizado') || '';
                        this.VENDA_pagina = pagina;
                        break;
                }

                const parametros = new URLSearchParams({
                    area: this.areaAtual,
                    pagina: pagina,
                    visualizados: idVisualizados,
                });

                await MobileStockApi(
                    `api_administracao/cadastro/busca_novos_fornecedores?${parametros}`
                )
                    .then((resp) => resp.json())
                    .then((resp) => {
                        if (!resp.status) throw new Error(resp.message);

                        const consulta = resp.data;
                        switch (this.areaAtual) {
                            case "ESTOQUE":
                                if (inicial) {
                                    this.ESTOQUE_lista_fornecedor = consulta.fornecedores;
                                } else {
                                    this.ESTOQUE_lista_fornecedor.push(consulta.fornecedores);
                                }
                                this.ESTOQUE_mais_pags = consulta.mais_pags;
                                break;
                            case "VENDA":
                                if (inicial) {
                                    this.VENDA_lista_fornecedor = consulta.fornecedores;
                                } else {
                                    this.VENDA_lista_fornecedor.push(consulta.fornecedores);
                                }
                                this.VENDA_mais_pags = consulta.mais_pags;
                                break;
                        }
                    });
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregando = false;
            }
        },
        async reposicao(acao, item) {
            try {
                this.carregando = true;

                if (acao === "BLOQUEAR") {
                    await MobileStockApi(
                        `api_administracao/fornecedor/bloqueia_seller/${item.id}`,
                        { method: "POST" }
                    )
                        .then((resp) => resp.json())
                        .then((resp) => {
                            if (!resp.status) throw new Error(resp.message);

                            item.bloqueado_repor = !item.bloqueado_repor;
                        });
                }

                if (acao === "DESBLOQUEAR") {
                    await MobileStockApi(
                        `api_administracao/fornecedor/desbloqueia_seller/${item.id}`,
                        { method: "POST" }
                    )
                        .then((resp) => resp.json())
                        .then((resp) => {
                            if (!resp.status) throw new Error(resp.message);

                            item.bloqueado_repor = !item.bloqueado_repor;
                        });
                }
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregando = false;
            }
        },
        estouCiente(item, area) {
            try {
                this.carregando = true;
                let idVisualizados = '';
                let guardar = '';
                let fornecedores = [];

                switch (area) {
                    case 'ESTOQUE':
                        fornecedores = this.ESTOQUE_lista_fornecedor;
                        break;
                    case 'VENDA':
                        fornecedores = this.VENDA_lista_fornecedor;
                        break;
                }

                const index = fornecedores.findIndex((fornecedor) => fornecedor.id === item.id);
                if (index === false) throw new Error("Fornecedor não encontrado");

                const fornecedorVisualizado = fornecedores[index];

                switch (area) {
                    case 'ESTOQUE':
                        idVisualizados = localStorage.getItem('ESTOQUE_id_visualizado') || '';

                        guardar = !!idVisualizados ? [idVisualizados, fornecedorVisualizado.id].join(',') : fornecedorVisualizado.id;
                        localStorage.setItem('ESTOQUE_id_visualizado', guardar);

                        this.ESTOQUE_lista_fornecedor.splice(index, 1);
                        break;
                    case 'VENDA':
                        idVisualizados = localStorage.getItem('VENDA_id_visualizado') || '';

                        guardar = !!idVisualizados ? [idVisualizados, fornecedorVisualizado.id].join(',') : fornecedorVisualizado.id;
                        localStorage.setItem('VENDA_id_visualizado', guardar);

                        this.VENDA_lista_fornecedor.splice(index, 1);
                        break;
                }
            } catch (error) {
                this.enqueueSnackbar(error);
            } finally {
                this.carregando = false;
            }
        },
        verArea(novaArea) {
            this.areaAtual = novaArea;
            this.buscaListaFornecedores();
        },
        proximaPag() {
            let pagina = 1;
            switch (this.areaAtual) {
                case "ESTOQUE":
                    pagina = this.ESTOQUE_pagina;
                    break;
                case "VENDA":
                    pagina = this.VENDA_pagina;
                    break;
            }
            pagina++;

            this.buscaListaFornecedores(false, pagina);
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
        gerirModalQrCode(
            status = false,
            telefone = "",
            nomeColaborador = "",
            area = ""
        ) {
            let qrCode = "";
            if (area !== "") {
                let texto = "";
                switch (area) {
                    case "ESTOQUE":
                        texto = "Olá, bem vindo ao Meulook.\nMe chamo Cleber e sou o responsável pelo recebimento de mercadorias e suporte ao seller aqui na plataforma.\nEm caso de dúvidas estou a disposição para lhe ajudar.";
                        break;
                    case "VENDA":
                        texto = "Olá, constatamos que houve sua primeira venda em nossa plataforma.\nO próximo passo é separar o item vendido e nos entregar na Central, situada na Rua Pará de Minas, 150, Fartura.";
                        break;
                }

                telefone = telefone.replace(/[^0-9]+/gi, "");
                qrCode = encodeURIComponent(new MensagensWhatsApp({ mensagem: texto, telefone: telefone }).resultado);
            }

            this.dadosModal = {
                nome: nomeColaborador,
                qrCode: qrCode,
            };
            this.abrirModal = status;
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
        tituloAreaAtual() {
            const t = this.areaAtual.charAt(0).toUpperCase();
            const itulo = this.areaAtual.slice(1).toLowerCase();

            return `${t}${itulo}`;
        },
    },
    mounted() {
        this.buscaListaFornecedores();
    },
});
