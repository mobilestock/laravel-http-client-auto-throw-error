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

<style>
    #linha-filtros {
        display: grid;
        grid-template-columns: 1fr 0.1fr 1fr;
    }
</style>

<v-app id="monitoraVendasProdutosExternosVUE">
    <template>
        <div>
            <v-row class="justify-content-center mt-1">
                <h2>
                    Monitoramento Últimos Produtos Externos Vendidos
                </h2>
            </v-row>
        </div>
        <br />
        <div class="mx-2">
            <div id="linha-filtros">
                <v-text-field
                    dense
                    outlined
                    label="Pesquisa na tabela"
                    :loading="isLoading"
                    v-model="pesquisa"
                ></v-text-field>
                <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            dense
                            icon
                            color="orange"
                            style="justify-self: center;"
                            :loading="isLoading"
                            v-bind="attrs"
                            v-on="on"
                            @click="filtros = {
                                menuData: false,
                                pagina: 1,
                                data: '',
                            };
                            pesquisa = '';"
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
                            :loading="isLoading"
                            v-bind="attrs"
                            v-model="filtros.menuData"
                            v-on="on"
                            @click:append.stop.prevent="filtros.data = '';"
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
                        v-model="filtros.data"
                        @input="filtros.menuData = false"
                    ></v-date-picker>
                </v-menu>
            </div>
            <div class="text-center">
                <v-tooltip top>
                    <template v-slot:activator="{ on, attrs }">
                        <v-chip v-bind="attrs" v-on="on">
                            <b>{{ qtdProdutos }}</b>
                        </v-chip>
                    </template>
                    <span v-if="filtros.data !== ''">Produtos vendidos no dia: {{ converteData(filtros.data) }}</span>
                    <span v-else>Total de produtos vendidos</span>
                </v-tooltip>
            </div>
            <br />
            <v-data-table
                hide-default-footer
                :headers="headerProdutos"
                :items="produtos"
                :items-per-page="-1"
                :loading="isLoading"
                :search="pesquisa"
            >
                <template v-slot:item.id_produto="{ item }">
                    <a :href="`fornecedores-produtos.php?id=${item.id_produto}`">
                        {{ item.id_produto }}
                    </a> - {{ item.nome_tamanho }}
                </template>

                <template v-slot:item.foto_produto="{ item }">
                    <v-img
                        class="mx-auto"
                        height="5rem"
                        width="5rem"
                        :aspect-ratio="16/9"
                        :src="item.foto_produto"
                    />
                </template>

                <template v-slot:item.preco="{ item }">
                    <span>{{ converteValorEmReais(item.preco) }}</span>
                </template>

                <template v-slot:item.cliente.nome="{ item }">
                    <v-btn block @click="ativarModalQrCode(item.cliente)">{{ item.cliente.nome }}</v-btn>
                </template>

                <template v-slot:item.fornecedor.nome="{ item }">
                    <v-btn
                        block
                        dark
                        :color="corPorReputacao(item.fornecedor.reputacao_atual)"
                        @click="ativarModalQrCode(item.fornecedor)"
                    >
                        {{ item.fornecedor.nome }}
                    </v-btn>
                </template>

                <template v-slot:item.data_validade="{ item }">
                    <b>{{ item.data_validade }}</b>
                </template>
            </v-data-table>
            <br />
            <div class="d-flex justify-content-around pb-4">
                <v-btn
                    dense
                    :dark="filtros.pagina > 1"
                    :disabled="filtros.pagina <= 1"
                    :loading="isLoading"
                    @click="filtros.pagina--"
                >
                    <v-icon>mdi-chevron-left</v-icon>
                    Produtos anteriores
                </v-btn>
                <v-chip dark>{{ filtros.pagina }}</v-chip>
                <v-btn
                    dense
                    :dark="produtos.length >= 150 && filtros.data === ''"
                    :disabled="produtos.length < 150 || filtros.data !== ''"
                    :loading="isLoading"
                    @click="filtros.pagina++"
                >
                    Proximos produtos
                    <v-icon>mdi-chevron-right</v-icon>
                </v-btn>
            </div>
        </div>
    </template>

    <!-- Modal QrCode -->
    <v-dialog
        width="unset"
        v-model="modalQr.ativar"
    >
        <template v-if="modalQr.ativar">
            <v-expand-x-transition>
                <v-card
                    class="mx-auto"
                    v-show="modalQr.ativar"
                >
                    <v-toolbar dark>
                        <v-toolbar-title>
                            {{ modalQr.nome }}
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <span v-if="modalQr.reputacao !== ''">
                            Reputação: {{ modalQr.reputacao }}
                        </span>
                    </v-toolbar>
                    <br />
                    <v-img :src="modalQr.codigo"/>
                </v-card>
            </v-expand-x-transition>
        </template>
    </v-dialog>

    <!-- Alerta -->
    <v-snackbar
        v-model="snackbar.ativar"
        :color="snackbar.cor"
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/monitora-vendas.js<?= $versao ?>"></script>
