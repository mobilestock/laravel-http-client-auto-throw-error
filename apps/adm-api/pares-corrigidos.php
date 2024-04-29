<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<body>
    <div class="container-fluid" id="paresCorrigidosVUE">
        <v-app>
            <v-main>
                <v-card>
                    <v-card-title>
                        Produtos Cancelados
                    <v-spacer></v-spacer>
                    <v-text-field
                        v-model="pesquisa"
                        append-icon="mdi-magnify"
                        label="Pesquisar"
                        single-line
                        hide-details
                    ></v-text-field>
                    </v-card-title>
                    <v-data-table
                        :headers="headerProdutos"
                        :items="produtos"
                        :item-class="(produto) => corPorReputacao(produto.reputacao)"
                        :search="pesquisa"
                        :items-per-page="50"
                        no-data-text="Sem dados"
                        :loading="loading">
                        <template v-slot:item.reputacao="{ item }">
                            <div class="align-items-center d-flex justify-content-center">
                                <v-chip
                                    dark
                                    x-small
                                    :color="corPorReputacao(item.reputacao)?.replace(/lighten/g, 'darken') || 'grey darken-4'"
                                >
                                    {{ formataTexto(item.reputacao) || 'NOVATO' }}
                                </v-chip>
                            </div>
                        </template>

                        <template v-slot:item.id_transacao="{ item }">
                            <a target="_blanc" :href="`transacao-detalhe.php?id=${item.id_transacao}`">
                                {{ item.id_transacao }}
                            </a>
                        </template>

                        <template v-slot:item.porque_afetou_reputacao="{ item }">
                            <v-chip dark>{{ formataTexto(item.porque_afetou_reputacao) }}</v-chip>
                        </template>
                    </v-data-table>
                </v-card>
            </v-main>

            <v-snackbar v-model="snackbar" timeout="2000" :color="snackColor" dark>
                {{mensagem}}

                <template v-slot:action="{ attrs }">
                    <v-btn icon v-bind="attrs" @click="removeAlerta">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </template>
            </v-snackbar>

        </v-app>
    </div>
</body>



<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/pares-corrigidos.js<?= $versao ?>"></script>
