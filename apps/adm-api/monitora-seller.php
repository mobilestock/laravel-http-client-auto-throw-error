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
    <h1 class="text-center">Monitoramento de Seller Externo</h1>
    <div class="container-fluid" id="monitoraSellerVUE">
        <v-app>
            <!-- Menu inicial -->
            <v-main>
                <v-card class="mb-2 pb-2">
                    <v-card-title>
                        <v-text-field dense filled hide-details single-line append-icon="mdi-magnify" label="Filtrar" :loading="isLoading" v-model="pesquisaSeller" />
                    </v-card-title>
                    <v-data-table
                        hide-default-footer
                        loading-text="Carregando Sellers"
                        no-data-text="Nenhum seller encontrado"
                        sort-desc
                        :headers="headersSeller"
                        :items="listaDeSellers"
                        :items-per-page="-1"
                        :loading="isLoading"
                        :search="pesquisaSeller"
                        :sort-by="['esta_atrasado']"
                    >
                        <template v-slot:item.razao_social="{ item }">
                            {{ item.razao_social }}
                            <v-chip
                                x-small
                                class="ml-2"
                                color="success"
                                v-if="item.eh_novato"
                            >
                                <v-icon left x-small>mdi-star-four-points</v-icon>
                                new
                            </v-chip>
                        </template>

                        <template v-slot:item.acoes="{ item }">
                            <v-btn :color="item.esta_atrasado == true ? 'error' : ''" elevation="1" :loading="isLoadingDetalhes" @click="buscaDetalhesSeller(item)">
                                <v-icon>fas fa-info-circle</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                    <br />
                    <div class="text-center">
                        <v-btn
                            dark
                            :loading="isLoading"
                            v-if="maisPaginas"
                            @click="pagina++"
                        >
                            <v-icon>mdi-chevron-down</v-icon>
                            Ver mais
                            <v-icon>mdi-chevron-down</v-icon>
                        </v-btn>
                        <div v-else-if="!maisPaginas && !isLoading">
                            Total de sellers:
                            &ensp;
                            <v-chip>{{ listaDeSellers.length }}</v-chip>
                        </div>
                    </div>
                </v-card>
            </v-main>

            <!-- Modal para Detalhes -->
            <v-dialog style="z-index: 2000" transition="dialog-bottom-transition" :persistent="isLoadingCancelar" v-model="modalDetalhes">
                <template v-if="listaDetalhesSeller">
                    <v-card>
                        <v-toolbar dark color="error">
                            <h5 @click="abrirModalQrCode(true)">
                                Seller:&ensp;<b>{{ sellerSelecionado.razao_social }}</b>
                            </h5>
                            <v-spacer></v-spacer>
                            <h5>
                                Reputação:&ensp;<b>{{ formataReputacaoSeller(sellerSelecionado) }}</b>
                            </h5>
                            <v-spacer></v-spacer>
                            <v-tooltip top>
                                <template v-slot:activator="{ on, attrs }">
                                    <h5 v-bind="attrs" v-on="on">
                                        Vendas canceladas:&ensp;<b>{{ sellerSelecionado.vendas_canceladas_recentes }}</b>
                                    </h5>
                                </template>
                                <span>Nos últimos 7 dias</span>
                            </v-tooltip>
                        </v-toolbar>
                        <template>
                            <v-data-table no-data-text="Nenhum pedido encontrado" :headers="headersDetalhes" :items="listaDetalhesSeller" :sort-by="['data_criacao']" sort-asc>
                                <template v-slot:item.valor_total="{ item }">
                                    {{ formataValorEmReais(item.valor_total) }}
                                </template>

                                <template v-slot:item.abrir_produtos="{ item }">
                                    <v-btn color="info" :dark="isLoadingProdutos === false && isLoadingCancelar === false" :disabled="isLoadingProdutos || isLoadingCancelar" :loading="isLoadingProdutos" @click="buscaProdutos(item)">
                                        <v-icon>fas fa-info-circle</v-icon>
                                    </v-btn>
                                </template>
                            </v-data-table>
                        </template>
                    </v-card>
                </template>
            </v-dialog>

            <!-- Modal de Produtos -->
            <v-dialog fullscreen hide-overlay scrollable transition="dialog-bottom-transition" v-model="modalProdutos">
                <template v-if="listaDeProdutos && transacaoSelecionada">
                    <v-card>
                        <v-toolbar dark dense color="red darken-2">
                            <v-toolbar-title>Produtos da transação:&ensp;
                                <b>{{ transacaoSelecionada?.transacoes?.join(',') }}</b>
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-btn icon dark @click="modalProdutos = false">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-card flat height="100%">
                            <v-data-table hide-default-footer :headers="headersProdutos" :items="listaDeProdutos" :items-per-page="listaDeProdutos.length">
                                <template v-slot:item.foto="{ item }">
                                    <v-img height="4rem" width="5rem" :aspect-ratio="16/9" :src="item.foto" />
                                </template>
                                <template v-slot:item.razao_social="{ item }">
                                    <v-btn :disabled="!item.qr_code" dense @click="abrirModalQrCodeCliente(item)">
                                        {{ item.razao_social }}
                                    </v-btn>
                                </template>
                            </v-data-table>
                            <v-divider></v-divider>
                        </v-card>
                    </v-card>
                </template>
            </v-dialog>

            <!-- Modal para QrCode -->
            <v-dialog hide-overlay width="unset" v-model="modalQrCode">
                <template v-if="listaDetalhesSeller">
                    <v-expand-x-transition>
                        <v-card class="mx-auto" height="250" width="250" v-show="expande">
                            <v-img :src="sellerSelecionado.qrCodeTelefone" />
                        </v-card>
                    </v-expand-x-transition>
                </template>
            </v-dialog>

            <!-- Modal para QrCode cliente -->
            <v-dialog hide-overlay width="unset" v-model="modalQrCodeCliente">
                <template v-if="listaDeProdutos && qrCodeCliente">
                    <v-expand-x-transition>
                        <v-card class="mx-auto" height="250" width="250" v-show="expandeQRCliente">
                            <v-img :src="qrCodeCliente" />
                        </v-card>
                    </v-expand-x-transition>
                </template>
            </v-dialog>

            <!-- Snackbar para respostas visuais do sistema: -->
            <template>
                <v-snackbar v-model="snackbar" :color="colorSnackbar">
                    {{ textoSnackbar }}
                </v-snackbar>
            </template>
        </v-app>
    </div>
</body>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/monitora-seller.js<?= $versao ?>"></script>
