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

<v-app id="monitoraLetrasPontosMaisUsados">
    <template>
        <div>
            <h1 class="text-center">
                Iniciais dos Destinos Mais Utilizados
            </h1>
        </div>
        <div class="text-center">
            <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                    <v-chip v-bind="attrs" v-on="on">
                        <b>{{ listaDados.quantidadeTotal }}</b>
                    </v-chip>
                </template>
                <span>Quantidade Total</span>
            </v-tooltip>
            <br/><br/>
            <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                    <span v-bind="attrs" v-on="on">
                        <b>{{ data_de }}</b>
                    </span>
                </template>
                <span>Data Inicial</span>
            </v-tooltip>
            -
            <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                    <span v-bind="attrs" v-on="on">
                        <b>{{ data_ate }}</b>
                    </span>
                </template>
                <span>Data Final</span>
            </v-tooltip>
            <br/><br/>
            <small>
                Nessa tela é mostrado uma tabela informando o agrupamento dos
                <b>destinatários de entregas</b> com a mesma inicial a fim de verificar
                qual grupo possui maior quantidade de entregas, assim <b>facilitando a separação</b>
            </small>
        </div>
        <div>
            <br/>
            <v-data-table
                :footer-props="{ itemsPerPageOptions: [26] }"
                :headers="headerDados"
                :items="listaDados"
                :loading="isLoading"
            >
                <template v-slot:item.percentual="{ item }">
                    <span>
                        {{ item.percentual}}%
                    </span>
                </template>
                <template v-slot:item.letra="{ item }">
                    <v-btn text @click="abrirModal(item)">{{ item.letra }}</v-btn>
                </template>
            </v-data-table>

            <!-- Modal -->
            <v-dialog
                v-model="modal"
                :headers="headerModal"
                persistent
                transition="dialog-bottom-transition"
                width="auto"
            >
                <v-card>
                    <v-data-table
                        :headers="headerModal"
                        :items="dadosModal.clientes"
                        :loading="isLoading"
                    >
                        <template>
                            <span>
                                {{ dadosModal }}
                            </span>
                        </template>
                    </v-data-table>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="primary" text @click="modal = false">Fechar</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </template>

    <v-snackbar
        :color="snackbar.cor"
        :timeout="snackbar.tempo"
        v-model="snackbar.ativar"
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/letras-mais-usadas.js<?= $versao ?>"></script>
