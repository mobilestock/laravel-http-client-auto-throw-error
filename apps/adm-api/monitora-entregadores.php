<?php

use MobileStock\helper\Globals;

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

<v-app id="monitoraEntregadoresVue">
    <v-main>
        <div>
            <h2 class="text-center">Monitoramento de Entregadores</h2>
        </div>
        <div class="p-4">
            <v-data-table
                hide-default-footer
                :headers="headersInformacoes"
                :items="listaInformacoes"
                :items-per-page="-1"
                :loading="carregando"
                :search="filtros.pesquisa"
            >
                <template v-slot:top>
                    <v-form
                        class="d-flex"
                        @submit.prevent="buscaListaEntregadoresComProdutos()"
                    >
                        <v-text-field
                            dense
                            outlined
                            class="mr-2"
                            label="Pesquisar"
                            :loading="carregando"
                            v-model="filtros.pesquisa"
                        ></v-text-field>
                        <v-btn
                            class="ml-2"
                            color="primary"
                            type="submit"
                        >
                            <v-icon>mdi-magnify</v-icon>
                        </v-btn>
                    </v-form>
                </template>

                <template v-slot:item.id_entrega_transacao="{ item }">
                    <a :href="`detalhes-entrega.php?id=${item.id_entrega}`">
                        {{ item.id_entrega }}
                    </a>
                    <b>/</b>
                    <a :href="`transacao-detalhe.php?id=${item.id_transacao}`">
                        {{ item.id_transacao }}
                    </a>
                </template>

                <template v-slot:item.id_produto_tamanho="{ item }">
                    <a :href="`fornecedores-produtos.php?id=${item.id_produto}`">
                        {{ item.id_produto }}
                    </a>
                    <b> - {{ item.nome_tamanho }}</b>
                </template>

                <template v-slot:item.foto_produto="{ item }">
                    <v-img
                        class="mx-auto"
                        height="5rem"
                        width="5rem"
                        :aspect-ratio="16/9"
                        :src="item.foto_produto"
                    ></v-img>
                </template>

                <template v-slot:item.cliente.nome="{ item }">
                    <v-btn
                        block
                        @click="gerirModal(true, false, item)"
                    >
                        {{ cortaNome(item.cliente.nome) }}
                        &ensp;
                        <v-chip
                            dark
                            x-small
                            v-show="item.cliente.tem_devolucao"
                        >Tem Devolução</v-chip>
                    </v-btn>
                </template>

                <template v-slot:item.entregador.nome="{ item }">
                    <v-btn
                        block
                        @click="gerirModal(true, true, item)"
                    >
                        {{ cortaNome(item.entregador.nome) }}
                        &ensp;
                        <v-chip
                            dark
                            x-small
                            v-show="item.entregador.desativado"
                        >Desativado</v-chip>
                    </v-btn>
                </template>

                <template v-slot:item.data_coleta="{ item }">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <span
                                v-bind="attrs"
                                v-on="on"
                            >{{ item.data_coleta }}</span>
                        </template>
                        <span v-if="item.dias_coleta < 1">O produto foi coletado hoje!</span>
                        <span v-else-if="item.dias_coleta === 1">O produto foi coletado ontem!</span>
                        <span v-else>Já fazem {{ item.dias_coleta }} dias que o produto foi coletado!</span>
                    </v-tooltip>
                </template>
            </v-data-table>
        </div>
        <br />
        <div class="text-center mb-4">
            <v-btn
                height="3rem"
                :dark="maisPags"
                :disabled="!maisPags"
                :loading="carregando"
                @click="filtros.pagina++"
            >
                <div class="d-flex flex-column">
                    Mostrar mais produtos
                    <v-icon>mdi-chevron-down</v-icon>
                </div>
            </v-btn>
        </div>
    </v-main>

    <!-- Modal QRCode WhatsApp -->
    <v-dialog
        persistent
        transition="dialog-bottom-transition"
        width="unset"
        v-if="modalQrCode.ativar"
        v-model="modalQrCode.ativar"
    >
        <v-card
            class="mx-auto"
            min-width="31.25rem"
            heigth="250"
            v-show="modalQrCode.ativar"
        >
            <v-toolbar dark>
                <v-toolbar-title>
                    {{ modalQrCode.nome }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="gerirModal()">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-img :src="'<?=Globals::geraQRCODE("")?>' + modalQrCode.qrCode"></v-img>
        </v-card>
    </v-dialog>

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
<script src="js/whatsapp.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script type="module" src="js/monitora-entregadores.js<?= $versao ?>"></script>
