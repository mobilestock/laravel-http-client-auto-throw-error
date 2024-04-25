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

<v-app id="conferenciaEstoqueProdutosVue">
    <v-main>
        <div style="margin-left: 1.3rem;">
            <h2>
                <b>Conferir estoque por referência</b>
            </h2>
        </div>
        <div id="corpo">
            <v-col>
                <h4>
                    <v-row>ID:&ensp; <b>{{ id_produto }}</b></v-row>
                    <v-row>Referência:&ensp; <b>{{ descricao }}</b></v-row>
                    <v-row>Localização:&ensp; <b>{{ local }}</b></v-row>
                </h4>
            </v-col>
            <br>
            <v-row>
                <v-text-field
                    dense
                    outlined
                    label="Código de barras"
                    prepend-inner-icon="mdi-barcode"
                    style="width: 13.75rem;"
                    v-model="codigoDigitado"
                    @keydown="salvaCodigo"
                ></v-text-field>
                <h3>
                    <b class="selecionar">Pares: &ensp; {{ codigosSelecionados.length }}</b>
                </h3>
                <v-spacer></v-spacer>
                <v-btn color="success" :disabled="isLoadingAnalise" :loading="isLoadingAnalise" @click="analisarCodigos">Analisar</v-btn>
            </v-row>
        </div>
        <div v-if="codigosSelecionados.length > 0">
            <v-data-table
                hide-default-footer
                :headers="headersListaCodigos"
                :items="codigosSelecionados"
                :items-per-page="codigosSelecionados.length"
                :loading="isLoadingAnalise"
            >
                <template v-slot:item.acao="{ item }">
                    <v-btn color="error" :loading="isLoadingAnalise" @click="removeCodigo(item)">
                        <v-icon dark>mdi-close</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.codigo="{ item }">
                    <b>{{ item.codigo }}</b>
                </template>
            </v-data-table>
        </div>
        <br>
        <div class="selecionar">
            <v-btn color="error" @click="retornar">Voltar</v-btn>
        </div>
    </v-main>

    <template>
        <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
            {{ snackbar.texto }}
        </v-snackbar>
    </template>
</v-app>

<style>
    #corpo {
        margin: 1.5rem 2rem 0 2rem;
    }

    .selecionar {
        margin-left: 2rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/estoque-conferencia-produtos.js<?= $versao ?>"></script>