<?php

use MobileStock\helper\Globals;

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioConferenteInternoOuAdm();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="monitoraNovoSellerVUE">
    <v-main>
        <div>
            <h2 class="text-center">Monitora Novos Seller</h2>
            <h4 class="text-center">{{ tituloAreaAtual }}</h4>
        </div>
        <div class="p-4">
            <div class="d-flex justify-content-around">
                <v-btn
                    :dark="areaAtual === 'ESTOQUE'"
                    :loading="carregando"
                    @click="verArea('ESTOQUE')"
                >
                    Adicionou Estoque
                </v-btn>
                <v-btn
                    :dark="areaAtual === 'VENDA'"
                    :loading="carregando"
                    @click="verArea('VENDA')"
                >
                    Primeira Venda
                </v-btn>
            </div>
            <br />

            <div v-if="areaAtual === 'ESTOQUE'">
                <v-data-table
                    hide-default-footer
                    :headers="ESTOQUE_header_fornecedores"
                    :items="ESTOQUE_lista_fornecedor"
                    :items-per-page="-1"
                    :search="ESTOQUE_pesquisa"
                >
                    <template v-slot:top>
                        <div>
                            <v-text-field
                                filled
                                label="Pesquisar na tabela"
                                v-model="ESTOQUE_pesquisa"
                            ></v-text-field>
                        </div>
                    </template>

                    <template v-slot:item.telefone="{ item }">
                        <v-btn
                            @click="gerirModalQrCode(true, item.telefone, item.razao_social, 'ESTOQUE')"
                        >
                            {{ item.telefone }}
                            &ensp;
                            <v-icon>mdi-whatsapp</v-icon>
                        </v-btn>
                    </template>

                    <template v-slot:item.bloqueado_repor="{ item }">
                        <v-btn
                            color="success"
                            :disabled="carregando"
                            v-if="item.bloqueado_repor"
                            @click="reposicao('DESBLOQUEAR', item)"
                        >
                            Desbloquear
                        </v-btn>

                        <v-tooltip top v-else>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    color="error"
                                    :disabled="carregando"
                                    v-bind="attrs"
                                    v-on="on"
                                    @click="reposicao('BLOQUEAR', item)"
                                >
                                    Bloquear
                                </v-btn>
                            </template>
                            <span>Ao bloquear um seller, você irá zerar todo seu estoque externo</span>
                        </v-tooltip>
                    </template>

                    <template v-slot:item.estou_ciente="{ item }">
                        <v-btn
                            icon
                            color="primary"
                            :loading="carregando"
                            @click="estouCiente(item, 'ESTOQUE')"
                        >
                            <v-icon>mdi-check</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </div>

            <div v-if="areaAtual === 'VENDA'">
                <v-data-table
                    hide-default-footer
                    :headers="VENDA_header_fornecedores"
                    :items="VENDA_lista_fornecedor"
                    :items-per-page="-1"
                    :search="VENDA_pesquisa"
                >
                    <template v-slot:top>
                        <div>
                            <v-text-field
                                filled
                                label="Pesquisar na tabela"
                                v-model="VENDA_pesquisa"
                            ></v-text-field>
                        </div>
                    </template>

                    <template v-slot:item.telefone="{ item }">
                        <v-btn
                            @click="gerirModalQrCode(true, item.telefone, item.razao_social, 'VENDA')"
                        >
                            {{ item.telefone }}
                            &ensp;
                            <v-icon>mdi-whatsapp</v-icon>
                        </v-btn>
                    </template>

                    <template v-slot:item.estou_ciente="{ item }">
                        <v-btn
                            icon
                            color="primary"
                            :loading="carregando"
                            @click="estouCiente(item, 'VENDA')"
                        >
                            <v-icon>mdi-check</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </div>

            <br />

            <div class="text-center">
                <v-btn
                    height="3rem"
                    :dark="ESTOQUE_mais_pags || VENDA_mais_pags"
                    :disabled="(areaAtual === 'ESTOQUE' && !ESTOQUE_mais_pags) || (areaAtual === 'VENDA' && !VENDA_mais_pags)"
                    :loading="carregando"
                    @click="proximaPag"
                >
                    <div class="d-flex flex-column">
                        Mostrar mais fornecedores
                        <v-icon>mdi-chevron-down</v-icon>
                    </div>
                </v-btn>
            </div>
        </div>
    </v-main>

    <!-- Modal QRCode WhatsApp -->
    <v-dialog
        transition="dialog-bottom-transition"
        width="unset"
        v-if="abrirModal"
        v-model="abrirModal"
    >
        <v-card
            class="mx-auto"
            min-width="31.25rem"
            heigth="250"
            v-show="abrirModal"
        >
            <v-toolbar dark>
                <v-toolbar-title>
                    {{ dadosModal.nome }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="gerirModalQrCode()">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-img :src="'<?= Globals::geraQRCODE('') ?>' + dadosModal.qrCode"></v-img>
        </v-card>
    </v-dialog>

    <!-- Snackbar alertas -->
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
<script type="module" src="js/monitora-novo-seller.js<?= $versao ?>"></script>
