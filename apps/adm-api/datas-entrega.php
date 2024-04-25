<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<v-app id="datas-entrega">
    <div class="p-2">
        <h2>Alterar Datas para Trocas</h2>
        <br />
        <v-data-table
            :headers="cabecalho"
            :items="itensDaPagina"
            hide-default-footer
            class="elevation-1"
            :items-per-page="itensPorPagina"
        >
            <template v-slot:top>
                <v-text-field
                    v-model="busca"
                    label="Pesquisa"
                    class="mx-4"
                    :loading="carregando"
                ></v-text-field>
            </template>
            <template v-slot:item.cliente="{ item }">
                <div class="text-center">
                    <span class="text-capitalize">{{ item.cliente }}</span>
                    <br />
                    <a :href="item.cliente_whatsapp" target="_blank">{{ item.cliente_telefone }}</a>
                </div>
            </template>
            <template v-slot:item.ponto="{ item }">
                <div class="text-center">
                    <span class="text-capitalize">{{ item.ponto }}</span>
                    <br />
                    <a :href="item.ponto_whatsapp" target="_blank">{{ item.ponto_telefone }}</a>
                </div>
            </template>
            <template v-slot:item.produto="{ item }">
                <span class="text-capitalize">{{ item.produto }}</span>
            </template>
            <template v-slot:item.data_base_troca="{ item }">
                <v-menu
                    :ref="item.menu"
                    v-model="item.menu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                >
                    <template v-slot:activator="{ on, attrs }">
                        <v-text-field
                            v-model="item.data_base_troca"
                            prepend-icon="mdi-calendar"
                            readonly
                            v-bind="attrs"
                            v-on="on"
                            :disabled="carregando"
                            :loading="carregando"
                        ></v-text-field>
                    </template>
                    <v-date-picker
                        v-model="item.data_base_troca"
                        no-title
                        scrollable
                    ></v-date-picker>
                    <v-spacer></v-spacer>
                    <v-btn
                        text
                        color="primary"
                        @click="item.menu = false"
                    >Cancelar</v-btn>
                    <v-btn
                        text
                        color="primary"
                        @click="item.menu = false; alterarDataEntregaItem(item.uuid_produto, item.data_base_troca)"
                    >Alterar</v-btn>
                </v-menu>
            </template>
        </v-data-table>
        <div class="pt-2">
            <v-pagination
                v-model="pagina"
                :length="ultimaPagina"
            ></v-pagination>
        </div>
    </div>
    <v-snackbar v-model="snackBar.show" timeout="2000">{{ snackBar.message }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/datas-entrega.js"></script>
