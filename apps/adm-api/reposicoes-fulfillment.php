<?php

require_once 'cabecalho.php';

acessoUsuarioFornecedor();
?>
<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app class="container-fluid" id="reposicoesFulfillmentVue">
    <!-- Card de pesquisa -->
    <v-container class="p-0 text-center">
        <v-row>
            <v-col cols="12">
                <v-card
                    elevation="2"
                    :loading="loading"
                >
                    <v-card-title class="d-flex align-center justify-space-between text-center">
                        <div class="align-center">
                            <v-icon left>fa-shopping-basket</v-icon>
                            Reposições Fulfillment
                        </div>
                    </v-card-title>
                    <v-card-text class="pb-0">
                        <div class="d-flex">
                            <v-text-field
                                outlined
                                dense
                                small
                                label="Pesquisa por ID ou referência"
                                v-model="pesquisa"
                                :hint="mensagemAdministrador()"
                                @keydown.enter="buscarProdutos"
                            ></v-text-field>
                            <v-spacer></v-spacer>
                            <v-btn
                                color="primary"
                                @click="buscarProdutos"
                                :disabled="loading"
                            >
                                Buscar
                            </v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Lista Cards de produtos -->
        <v-row>
            <v-col v-for="produto in produtos" :key="produto.id_produto" cols="6" md="2" lg="2">
                <v-card>
                    <v-banner
                        class="banner"
                        color="secondary"
                        dark
                        single-line
                    >
                        ID: {{produto.id_produto }}
                    </v-banner>
                    <v-img
                        :src="produto.foto"
                        height="200px"
                    ></v-img>
                    <p style="margin: 0; font-size: 0.8rem;">{{ produto.descricao }}</p>
                    <div class="card-grades">
                        <div v-for="grade in produto.grades" :key="grade.id" class="grade">
                            <p
                                :class="grade.estoque - grade.reservado < 0 ? 'red--text' : 'black--text'"
                            >{{ grade.estoque - grade.reservado }}</p>
                            <p class="nome-tamanho">{{ grade.nome_tamanho }}</p>
                        </div>
                    </div>
                    <v-card-actions>
                        <v-btn
                            class="flex"
                            @click="reporProduto(produto.id_produto)"
                            color="success"
                            :disabled="loading"
                        >
                            Repor
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
        <v-btn
            v-if="$vuetify.breakpoint.smAndDown"
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
    .banner {
        @media (max-width: 768px) {
            font-size: calc(1rem - (768px - 100vw) / 100);
        }
    }
</style>

<script src="js/reposicoes-fulfillment.js<?= $versao ?>"></script>
