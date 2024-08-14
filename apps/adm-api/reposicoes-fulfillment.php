<?php require_once 'cabecalho.php'; ?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app class="container-fluid" id="reposicoesFulfillmentVue">
    <!-- Card de pesquisa -->
    <v-container class="p-0 text-center">
        <v-card
            elevation="2"
            :loading="loading"
            style="padding: 0; margin: 0;"
        >
            <v-card-title class="d-flex align-center justify-space-between text-center">
                <div class="align-center">
                    <v-icon left>fa-shopping-basket</v-icon>
                    Reposições Fulfillment
                </div>
            </v-card-title>
            <v-card-text class="pb-0">
                <div ref="blocoPesquisa">
                    <v-text-field
                        outlined
                        dense
                        small
                        label="Pesquisa por ID ou referência"
                        v-model="pesquisa"
                        style="padding: 0; margin: 0;"
                        :disabled="loading"
                    ></v-text-field>
                </div>
            </v-card-text>
            <a @click="modalTermosCondicoes = true" style="padding: 0; margin: 0;">Termos e condições</a>
        </v-card>

        <!-- Lista Cards de produtos -->
        <v-row class="produto-row">
            <v-col v-for="produto in produtos" :key="produto.id_produto" cols="6" md="2" lg="2" class="produto-col">
                <v-card v-if="produto.id_produto">
                    <v-banner
                        class="banner"
                        color="secondary"
                        dark
                        single-line
                    >
                        ID: {{produto.id_produto }}
                    </v-banner>
                    <div class="d-flex align-center justify-center">
                        <v-img
                            :src="produto.foto"
                            height="200"
                            width="200"
                            contain
                        ></v-img>
                    </div>
                    <p class="produto-descricao">{{ produto.descricao }}</p>
                    <div class="card-grades">
                        <div v-for="grade in produto.grades" :key="grade.nome_tamanho" class="grade">
                            <p
                                :class="grade.estoque - grade.reservado < 0 ? 'red--text' : 'black--text'"
                            >{{ grade.estoque - grade.reservado }}</p>
                            <p class="nome-tamanho">{{ grade.nome_tamanho }}</p>
                        </div>
                    </div>
                    <v-card-actions>
                        <v-btn
                            class="flex"
                            @click="reporProduto(produto)"
                            color="primary"
                            :disabled="loading"
                        >
                            Repor
                        </v-btn>
                    </v-card-actions>
                </v-card>
                <v-card
                    v-else
                    class="cadastro-card"
                    href="fornecedores-produtos.php"
                >
                    <v-banner
                        class="banner"
                        color="success"
                        dark
                        single-line
                    >
                    CADASTRAR
                    </v-banner>
                    <div class="cadastro-content d-flex align-center justify-center">
                        <v-icon size="200px">mdi-plus</v-icon>
                        <p class="cadastro-texto">Cadastre um novo produto</p>
                    </div>
                </v-card>
            </v-col>
            <div ref="finalPagina"></div>
        </v-row>
        <v-btn
            v-show="ehPossivelVoltarAoTopo"
            fab
            fixed
            bottom
            right
            @click="voltarAoTopo"
            color="primary"
        >
            <v-icon>mdi-arrow-up</v-icon>
        </v-btn>
    </v-container>

    <!-- Modal de impressão de etiquetas -->
    <v-dialog
        v-model="modalImpressaoEtiquetas"
        fullscreen
        hide-overlay
        transition="dialog-bottom-transition"
    >
        <v-card class="white p-0 text-center" v-if="produtoSelecionado !== null">
            <v-toolbar
                color="secondary"
                dark
            >
                Fornecedor: {{ produtoSelecionado.fornecedor }}
                <v-spacer></v-spacer>
                <v-icon right @click="fecharModalImpressaoEtiquetas">mdi-close</v-icon>
            </v-toolbar>
            <div class="d-flex align-center justify-center">
                <v-img
                    :src="produtoSelecionado.foto"
                    height="200"
                    width="200"
                    contain
                ></v-img>
            </div>
            <p class="align-center text-center" style="font-size: 0.8rem; margin: 0;">
                ({{ produtoSelecionado.id_produto }}) - {{ produtoSelecionado.descricao }}
            </p>
            <div class="d-flex justify-center">
                <v-data-table
                    disable-pagination
                    disable-sort
                    dense
                    hide-default-footer
                    mobile-breakpoint="0"
                    :headers="headersGrades"
                    :items="gradesComMultiplicador"
                    :loading="loading"
                >
                    <template v-slot:item.nome_tamanho="{ item }">
                        <v-chip
                            small
                            label
                            dark
                            color="black"
                            style="font-size: 0.8rem; margin: 0;"
                        >
                            {{ item.nome_tamanho }}
                        </v-chip>
                    </template>
                    <template v-slot:item.remover="{ item }">
                        <v-btn
                            small
                            dark
                            color="red"
                            @click="remover(item)"
                        >
                            <v-icon>mdi-minus</v-icon>
                        </v-btn>
                    </template>
                    <template v-slot:item.estoque="{ item }">
                        <p>
                            {{ item.estoque - item.reservado }}
                        </p>
                    </template>
                    <template v-slot:item.adicionar="{ item }">
                        <v-btn
                            small
                            dark
                            color="primary"
                            @click="adicionar(item)"
                        >
                            <v-icon>mdi-plus</v-icon>
                        </v-btn>
                    </template>
                    <template v-slot:item.quantidade_impressao="{ item }">
                        <p :class="item.quantidade_impressao > 0 ? 'text-green' : 'text-grey'">
                            {{ item.quantidade_impressao }}
                        </p>
                    </template>
                </v-data-table>
            </div>
            <div class="caixas">
                <v-btn class="caixas-btn" @click="decrementarMultiplicador()">
                    <v-icon>mdi-minus</v-icon>
                </v-btn>
                <v-text-field
                    label="Caixas"
                    class="caixas-text-field"
                    outlined
                    dense
                    type="number"
                    v-model="multiplicador"
                ></v-text-field>
                <v-btn class="caixas-btn" @click="incrementarMultiplicador()">
                    <v-icon>mdi-plus</v-icon>
                </v-btn>
            </div>
            <v-btn
                class="flex align-center justify-center"
                width="90%"
                color="success"
                :disabled="loading || produtoSelecionado.grades.reduce((acc, grade) => acc + grade.quantidade_impressao, 0) === 0"
                @click="imprimirEtiquetas"
            >
                IMPRIMIR
            </v-btn>
        </v-card>
    </v-dialog>

    <!-- Modal de termos e condições -->
     <v-dialog
        v-model="modalTermosCondicoes"
        fullscreen
        hide-overlay
        transition="dialog-bottom-transition"
    >
        <v-card>
            <v-toolbar
                color="secondary"
                dark
            >
                Termos e Condições
                <v-spacer></v-spacer>
                <v-icon right @click="modalTermosCondicoes = false">mdi-close</v-icon>
            </v-toolbar>
                Vantagens de colocar produtos no Full:
            <v-card-text>
                <p>
                * Produtos ganham maior relevância nas plataformas aumentando chances de venda.
                </p>
                <p>
                    * O processo de separação e embalagem é por nossa conta.
                </p>
                <p>
                    * Produtos expostos na plataforma Mobile Stock, onde o crédito das vendas cai no momento em que o cliente faz o pagamento.
                </p>
                <p>
                    * Acesso a antecipação de valores.
                </p>
            </v-card-text>
                Regras e Condições:
            <v-card-text>
                <p>
                    1- A empresa Mobile Stock se responsabiliza pela perda e danos ao produto acontecidos no ato do armazenamento.
                </p>
                <p>
                    2- Não nos responsabilizamos pelas embalagens amassadas ou danificadas com tempo.
                </p>
                <p>
                    3- Não nos responsabilizamos por produtos que não estejam conforme a legislação.
                </p>
                <p>
                    4- O proprietário dos produtos pode solicitar a qualquer momento os produtos de volta, a empresa Mobile Stock tem 5 dias úteis para recolher os produtos. Será cobrado a taxa de 2,00 reais por produto, para cobrir os custos de mão de obra.
                </p>
                <p>
                    5- Todo mês será verificado produtos que venderam menos de 10% da quantidade armazenada, esses em 120 dias terão seus valores diminuídos automaticamente pelo em 10%.
                </p>
            </v-card-text>
        </v-card>
    </v-dialog>

    <!-- Modal de snackbar -->
    <v-snackbar
        :color="snackbar.cor"
        v-model="snackbar.ativar"
        v-cloak
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style>
    .pa-0 {
        padding: 0 !important;
    }
    .ma-0 {
        margin: 0 !important;
    }
    .v-row {
        margin-bottom: 0 !important;
    }
    .v-card {
        margin: 0 auto;
    }
    .card-grades {
        margin-top: 0.5rem;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }
    .grade {
        margin: 0.02rem;
        border: 1px solid #ccc;
        border-radius: 0.2rem;
    }
    .grade p {
        margin: 0;
        padding: 0 0.4rem;
        font-size: 0.8rem;
    }
    .grade p.nome-tamanho {
        background-color: black;
        color: white;
        padding: 0.04rem;
        border-radius: 0.2rem;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .produto-row {
        margin: 0.5rem 0;
    }
    .produto-col {
        padding: 0.2rem;
    }
    .produto-descricao {
        margin: 0;
        font-size: 0.8rem;
    }
    .cadastro-card {
        height: 100%;
    }
    .cadastro-card:hover {
        background-color: #EEE;
        text-decoration: none;
    }
    .cadastro-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .cadastro-texto {
        margin: 0;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .caixas {
        display: flex;
        justify-content: center;
        margin: 0;
        padding: 0;
        flex: 1;
    }
    .caixas-btn {
        min-width: 30px;
        min-height: 36px;
        padding: 0;
        margin: 0 10px;
    }
    .caixas-text-field {
        max-width: 65px;
        padding: 0;
    }
    .v-dialog__content--active {
        z-index: 2000 !important;
    }
    .v-application--wrap {
        overflow: hidden;
    }
</style>

<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/reposicoes-fulfillment.js<?= $versao ?>"></script>
