<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://esm.sh/jsondiffpatch@0.6.0/lib/formatters/styles/annotated.css" type="text/css" />
    <link rel="stylesheet" href="https://esm.sh/jsondiffpatch@0.6.0/lib/formatters/styles/html.css" type="text/css" />
</head>

<v-app id="logs-manuais" class="container">
    <h1>Logs Internos</h1>
    <v-form ref="formularioReferencia">
        <v-col>
            <v-combobox
                label="SELECT"
                v-model="select"
                :items="autocomplete.select"
                :rules="formularioInputsRegras"
                persistent-hint
                hint="A coluna deve ser um JSON"
                required
            ></v-combobox>
            <v-combobox label="FROM" v-model="from" :items="autocomplete.from" :rules="formularioInputsRegras" required></v-combobox>
            <v-combobox label="WHERE" v-model="where" :items="autocomplete.where" :rules="formularioInputsRegras" required></v-combobox>
            <br>
            <v-btn :disabled="carregando" :loading="carregando" @click="consultar">Consultar</v-btn>
        </v-col>
    </v-form>
    <br />
    <v-data-table :headers="cabecalhos" :items="itens" :loading="carregando" :disabled="carregando">
        <template v-slot:item.dados="{ item }">
            <div id="campo-json" @click="copiarDados(JSON.stringify(item.dados))" class="py-2" style="cursor: copy;">
                <v-icon>
                    mdi-content-copy
                </v-icon>
                <span>{{ item.dados }}</span>
            </div>
        </template>
        <template v-slot:item.acao="{ item, index }">
            <v-btn @click="abrirDialogCompararJsons(index)">
                Visualizar
            </v-btn>
        </template>
    </v-data-table>
    <v-dialog :eager="true" width="500" :value="logSelecionado != null" @click:outside="fecharDialog" @keydown.esc="fecharDialog">
        <v-card class="p-3">
            <h3 class="text-center">{{ logSelecionado?.data_criacao }}</h3>
            <div id="visual"></div>
            <div class="mt-3 d-flex justify-content-between">
                <v-btn @click="alternarLogSelecionado(-1)" :disabled="botaoAnteriorDesabilitado">
                    <v-icon>mdi-chevron-left</v-icon>
                </v-btn>
                <v-btn @click="alternarLogSelecionado()" :disabled="botaoProximoDesabilitado">
                    <v-icon>mdi-chevron-right</v-icon>
                </v-btn>
            </div>
        </v-card>
    </v-dialog>
    <v-snackbar v-model="snack.mostrar">{{ snack.mensagem }}</v-snackbar>
</v-app>

<textarea id="input-copiar" style="height: 1px;"></textarea>

<script type="module" src="js/logs-internos.js"></script>
