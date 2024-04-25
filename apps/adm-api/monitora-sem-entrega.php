<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="monitoraSemEntregaVUE">
    <v-main>
        <div class="text-center">
            <h2>Monitora Produtos Sem Entrega</h2>
            <small>
                Nessa tela é mostrado clientes com pedidos que serão enviados por
                <b>transportadora</b> ou serão <b>retirados na central</b> e que
                <b>todos</b> os produtos que <b>não</b> foram adicionados a uma entrega já
                foram <b>conferidos</b>.
            </small>
        </div>
        <div class="p-4">
            <v-data-table
                :headers="headerProdutos"
                :items="produtos"
                :search="pesquisa"
            >
                <template v-slot:top>
                    <div>
                        <v-text-field
                            filled
                            label="Pesquisar na tabela"
                            v-model="pesquisa"
                        ></v-text-field>
                    </div>
                </template>
            </v-data-table>
        </div>
    </v-main>

    <!-- Snackbar alertas -->
    <v-snackbar
        :color="snackbar.cor"
        :timeout="snackbar.tempo"
        v-model="snackbar.ativar"
        v-cloak
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style></style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/monitora-sem-entrega.js<?= $versao ?>"></script>
