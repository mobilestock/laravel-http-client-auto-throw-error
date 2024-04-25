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

<style>
    #linha-filtros {
        display: grid;
        grid-template-columns: 1fr 0.1fr 1fr;
    }
</style>

<v-app id="monitoraMaisVendidosVUE">
    <v-main>
        <div>
            <h2 class="text-center">Monitora Produtos Mais Vendidos</h2>
            <h4 class="text-center" v-show="totalProdutos">
                Quantidade de produtos:
                <v-chip>{{ totalProdutos }}</v-chip>
            </h4>
        </div>
        <div class="p-4">
            <v-data-table
                hide-default-footer
                :headers="headerProdutos"
                :items="listaProdutos"
                :items-per-page="-1"
                :loading="carregando"
                :search="pesquisa"
            >
                <template v-slot:top>
                    <div id="linha-filtros">
                        <v-text-field
                            dense
                            outlined
                            label="Pesquisar na tabela"
                            :loading="carregando"
                            v-model="pesquisa"
                        ></v-text-field>
                        <v-tooltip bottom>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    dense
                                    icon
                                    color="orange"
                                    style="justify-self: center;"
                                    :loading="carregando"
                                    v-bind="attrs"
                                    v-on="on"
                                    @click="gerirFiltros(); pesquisa = ''"
                                >
                                    <v-icon>mdi-refresh</v-icon>
                                </v-btn>
                            </template>
                            <span>Limpar os filtros</span>
                        </v-tooltip>
                        <v-menu
                            offset-y
                            min-width="18.125rem"
                            transition="scale-transition"
                            :close-on-content-click="false"
                            :nudge-right="40"
                            v-model="filtros.menuData"
                        >
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    block
                                    dark
                                    :loading="carregando"
                                    v-bind="attrs"
                                    v-model="filtros.menuData"
                                    v-on="on"
                                    @click:append.stop.prevent="filtros.dataInicial = ''"
                                >
                                    <v-icon>mdi-calendar</v-icon>
                                    &ensp;
                                    {{ textoFiltroData }}
                                    &ensp;
                                    <v-icon v-if="filtros.menuData">mdi-chevron-up</v-icon>
                                    <v-icon v-else>mdi-chevron-down</v-icon>
                                </v-btn>
                            </template>
                            <v-date-picker
                                color="grey darken-3"
                                elevation="15"
                                locale="pt-BR"
                                v-model="filtros.dataInicial"
                                @input="filtros.menuData = false"
                            ></v-date-picker>
                        </v-menu>
                    </div>
                </template>

                <template v-slot:item.id_produto="{ item }">
                    <a :href="`fornecedores-produtos.php?id=${item.id_produto}`">
                        {{ item.id_produto }}
                    </a>
                </template>

                <template v-slot:item.foto_produto="{ item }">
                    <v-img
                        height="5rem"
                        width="5rem"
                        :aspect-ratio="16/9"
                        :src="item.foto_produto"
                    />
                </template>

                <template v-slot:item.telefone="{ item }">
                    <v-btn
                        block
                        dark
                        :color="corPorReputacao(item.reputacao)"
                        @click="gerirModalQrCode(
                            true,
                            item.telefone,
                            item.razao_social,
                            item.reputacao,
                            {
                                id: item.id_produto,
                                nome: item.nome_produto,
                            },
                        )"
                    >
                        {{ item.telefone }}
                        <v-icon>mdi-whatsapp</v-icon>
                    </v-btn>
                </template>

                <template v-slot:item.possui_permissao="{ item }">
                    <v-btn
                        block
                        :dark="!carregando"
                        :color="item.possui_permissao ? 'var(--cor-secundaria-meulook)' : 'var(--cor-permitir-fulfillment)'"
                        :disabled="carregando"
                        @click="gerirModalPermissao(true, item)"
                    >
                        <template v-if="item.possui_permissao">
                            <v-icon>mdi-close-circle</v-icon> &ensp; Proibir reposição
                        </template>
                        <template v-else>
                            <v-icon>mdi-checkbox-marked-circle</v-icon> &ensp; Permitir reposição
                        </template>
                    </v-btn>
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
        transition="dialog-bottom-transition"
        width="unset"
        v-if="statusModalQR"
        v-model="statusModalQR"
    >
        <v-card
            class="mx-auto"
            min-width="31.25rem"
            heigth="250"
            v-show="statusModalQR"
        >
            <v-toolbar dark>
                <v-toolbar-title>
                    {{ dadosModalQR.nome }} - <b>{{ mostraReputacaoAtual }}</b>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="gerirModalQrCode()">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-img :src="'<?=Globals::geraQRCODE("")?>' + dadosModalQR.qrCode"></v-img>
        </v-card>
    </v-dialog>

    <!-- Modal confirmar autorização -->
    <v-dialog
        persistent
        transition="dialog-bottom-transition"
        width="300"
        v-if="statusModalPermissao"
        v-model="statusModalPermissao"
    >
        <v-card v-show="statusModalPermissao">
            <v-toolbar
                dark
                dense
                :color="dadosModalPermissao.possui_permissao ? 'var(--cor-secundaria-meulook)' : 'var(--cor-permitir-fulfillment)'"
            >
                <v-toolbar-title>
                    Atenção!
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="gerirModalPermissao()">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <h6>
                <v-card-text>
                    Você tem certeza de que deseja
                    <b
                        style="color: var(--cor-secundaria-meulook);"
                        v-if="dadosModalPermissao.possui_permissao"
                    > proibir </b>
                    <b
                        style="color: var(--cor-permitir-fulfillment);"
                        v-else
                    > permitir </b>
                    a reposição
                    <b> Fulfillment </b>
                    do produto
                    <b> {{ dadosModalPermissao.id_produto }} </b>
                </v-card-text>
            </h6>
            <v-card-actions>
                <v-btn @click="atualizaPermissaoFulfillment()">Confirmar</v-btn>
                <v-btn dark @click="gerirModalPermissao()">Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Snackbar aviso -->
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
<script type="module" src="js/monitora-mais-vendidos.js<?= $versao ?>"></script>