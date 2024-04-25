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

<v-app id="paineisImpressaoVUE">
    <v-main>
        <h1 class="text-center">Painéis de Impressão</h1>
        <div class="mx-auto w-75">
            <span v-if="editando">
                <v-textarea label="Painéis" solo auto-grow v-model="input"></v-textarea>
                <v-btn color="success" @click="salvaPaineis" :loading="carregando">Salvar</v-btn>
                <v-btn color="error" @click="editando = false">Cancelar</v-btn>
            </span>
            <span v-else>
                <v-card elevation="3" :loading="carregando">
                    <v-card-title>{{paineis}}</v-card-title>
                </v-card>
                <v-btn class="mt-2" color="info" @click="iniciaEdicao">Editar</v-btn>
            </span>
        </div>
    </v-main>

    <v-snackbar v-model="snackbar.mostrar" :color="snackbar.cor">{{ snackbar.texto }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/paineis-impressao.js<?= $versao ?>"></script>
