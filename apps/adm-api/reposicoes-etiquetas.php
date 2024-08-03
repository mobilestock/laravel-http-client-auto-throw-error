<?php require_once 'cabecalho.php'; ?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app class="container-fluid" id="reposicoesEtiquetasVue">
    <!-- Card informações do produto -->
    <v-container class="p-0 text-center">
        <v-row>
            <v-col cols="12">
                <v-banner
                    class="banner"
                    color="secondary"
                    dark
                    single-line
                >
                    Fornecedor: {{ produto.fornecedor }}
                </v-banner>
                <div class="d-flex align-center justify-center">
                    <v-img
                        :src="produto.foto"
                        height="400"
                        width="400"
                        contain
                    ></v-img>
                </div>
                <p class="align-center text-center">
                    ({{ produto.id_produto }}) - {{ produto.descricao }}
                </p>
                <div>
                    <v-data-table
                        disable-pagination
                        disable-sort
                        dense
                        class="w-100"
                        hide-default-footer
                        mobile-breakpoint="0"
                        :headers="headersGrades"
                        :items="produto?.grades"
                        :loading="loading"
                        item-style="width: 10px;"
                    >
                        <template v-slot:item.remover="{ item }">
                            <v-btn dark color="red" @click="remover(item)">
                                <v-icon>mdi-minus</v-icon>
                            </v-btn>
                        </template>
                        <template v-slot:item.adicionar="{ item }">
                            <v-btn dark color="primary" @click="adicionar(item)">
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                </div>
            </v-col>
        </v-row>
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

</style>

<script src="js/reposicoes-etiquetas.js<?= $versao ?>"></script>
