<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioVendedor();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="produtoCorrigirEstoqueDetalhesVue">
    <v-main class="responsiva">
        <template>
            <div>
                <v-row id="titulo">
                    <h2>
                        <b>Conferir Estoque</b>
                    </h2>
                </v-row>
            </div>
        </template>
        <div id="corpo" v-if="produto.length !== 0">
            <v-row justify="space-around">
                <div id="infos-produto">
                    <v-card
                        flat
                        class="mx-auto my-12"
                        max-width="25.4rem"
                        :loading="isLoading || isLoadingMovimentar"
                    >
                        <template slot="progress">
                            <v-progress-linear
                                dark
                                stream
                                height="3"
                            ></v-progress-linear>
                        </template>

                        <v-img
                            height="25.4rem"
                            id="imagem"
                            width="100%"
                            :alt="produto.id"
                            :src="produto.foto"
                        ></v-img>

                        <v-card
                            flat
                            color="grey lighten-4"
                        >
                            <v-card-title class="justify-center">
                                <h4>{{ produto.descricao }}</h4>
                            </v-card-title>
                            <v-card-title class="justify-center" style="background-color: #EEDD82;">
                                    <h6>Localização: {{ produto.localizacao }}</h6>
                            </v-card-title>
                        </v-card>
                    </v-card>
                </div>
                <div id="grades">
                    <v-data-table
                        disable-filtering
                        disable-pagination
                        disable-sort
                        hide-default-footer
                        :disabled="isLoadingMovimentar"
                        :headers="headersGradeTabela"
                        :items="gradesProduto"
                    >
                        <template v-slot:item.nome_tamanho="{ item }">
                            <v-chip dark>{{ item.nome_tamanho }}</v-chip>
                        </template>

                        <template v-slot:item.quantidade="{ item }">
                            <div class="d-flex w-100">
                                <v-text-field
                                    dense
                                    hide-details
                                    outlined
                                    single-line
                                    label="Quantidade"
                                    type="number"
                                    :disabled="isLoadingMovimentar"
                                    @input="input => calculaTotalEstoque(item, input)"
                                    v-model="item.quantidade"
                                ></v-text-field>
                            </div>
                        </template>
                    </v-data-table>
                </div>
            </div>
        </v-row>
        <br>
        <div id="botoes">
            <v-btn
                block
                color="error"
                :disabled="isLoadingMovimentar"
                :loading="isLoadingMovimentar"
                @click="movimentarEstoque"
            >
                Remover Estoque
            </v-btn>
            <br>
            <v-btn dark color="amber" :disabled="isLoadingMovimentar" @click="voltar">Voltar</v-btn>
        </div>
    </v-main>

    <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style>
    @media screen and (max-width: 576px) {
        .responsiva {
            width: 100%;
        }
    }
    @media screen and (min-width: 576px) {
        .responsiva {
            width: 540px;
        }
    }
    @media screen and (min-width: 768px) {
        .responsiva {
            width: 720px;
        }
    }
    @media screen and (min-width: 992px) {
        .responsiva {
            width: 960px;
        }
    }
    @media screen and (min-width: 1200px) {
        .responsiva {
            width: 1140px;
        }
    }
    .responsiva {
        margin: 0 auto;
    }
    #titulo {
        margin: 0 0 1rem 1.5rem;
    }
    #corpo {
        margin: 0 1rem;
    }
    #infos-produto {
        margin-bottom: -3rem;
    }
    #imagem {
        border-radius: 1.5rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/produto-corrigir-estoque-detalhes.js<?= $versao ?>"></script>
