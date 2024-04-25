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
    <h1 class="text-center">Monitoramento de Pontos</h1>
    <div class="container-fluid" id="monitoraPontosVUE">
        <v-app>
            <!-- Menu inicial -->
            <v-main>
                <template>
                    <v-card :loading="loading">
                        <v-card-title>
                            <v-text-field :loading="loading" dense filled v-model="pesquisaPontosRetirada" append-icon="mdi-magnify" label="Filtrar" single-line hide-details />
                        </v-card-title>
                        <v-data-table :items="listaPontosRetirada" :item-class="corPontoDesativado" :loading="loading" loading-text="Carregando pontos" :headers="headers" :search="pesquisaPontosRetirada" :sort-by="['atrasado']" sort-desc />
                        <template v-slot:item.nome="{ item }">
                            <span>{{ item.nome }}</span> ({{ item.razao_social }})
                        </template>
                        <template v-slot:item.acoes="{ item }">
                            <template v-if="item.atrasado">
                                <v-btn elevation="1" color="error" :loading="loading" @click="openModal(item)">
                                    <v-icon color="white">fas fa-info-circle</v-icon>
                                </v-btn>
                            </template>
                            <template v-else>
                                <v-btn elevation="1" :loading="loading" @click="openModal(item)">
                                    <v-icon color="#616161">fas fa-info-circle</v-icon>
                                </v-btn>
                            </template>
                        </template>
                        </v-data-table>
                    </v-card>
                </template>
            </v-main>

            <!-- Modal de informações -->
            <v-dialog hide-overlay transition="dialog-bottom-transition" :items="produtosEPonto" v-model="modal" style="z-index: 2000">
                <template v-if="produtosEPonto.ponto && produtosEPonto.produtos">
                    <v-card :loading="loading" color="#dc3545">
                        <v-container grid-list-md text-xs-center>
                            <v-row no-gutters>
                                <v-col>
                                    <v-card-title class="white--text" @click="openModalQrCode">Ponto:
                                        <b>{{produtosEPonto.ponto.nome}}</b>
                                    </v-card-title>
                                </v-col>
                                <v-col>
                                    <v-card-title class="white--text">Último envio:
                                        <b>{{produtosEPonto.ponto.ultimo_envio}}</b>
                                    </v-card-title>
                                </v-col>
                            </v-row>
                        </v-container>
                        <template>
                            <v-container grid-list-md text-xs-center>
                                <v-row no-gutters>
                                    <v-col>
                                        <template v-if="tempoChegando === false">
                                            <v-btn elevation="1" :loading="loading" @click="mudaSetor('chegando')">Para Chegar</v-btn>
                                        </template>
                                        <template v-else>
                                            <v-btn elevation="1" color="error" :loading="loading" @click="mudaSetor('chegando')">Para Chegar</v-btn>
                                        </template>
                                    </v-col>
                                    <!-- <v-col>
                                        <template v-if="tempoConferindo === false">
                                            <v-btn elevation="1" :loading="loading" @click="mudaSetor('conferindo')">Para Conferir</v-btn>
                                        </template>
                                        <template v-else>
                                            <v-btn elevation="1" color="error" :loading="loading" @click="mudaSetor('conferindo')">Para Conferir</v-btn>
                                        </template>
                                    </v-col> -->
                                    <v-col>
                                        <template v-if="tempoRetirando === false">
                                            <v-btn elevation="1" :loading="loading" @click="mudaSetor('retirando')">Para Retirar</v-btn>
                                        </template>
                                        <template v-else>
                                            <v-btn elevation="1" color="error" :loading="loading" @click="mudaSetor('retirando')">Para Retirar</v-btn>
                                        </template>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </template>
                        <template v-if="qualTabela === 'chegando'">
                            <v-data-table :items="produtosEPonto.produtos.chegando" :loading="loading" loading-text="Carregando produtos" :headers="headersProdutos" :sort-by="['atrasado']" sort-desc>
                                <template v-slot:item.nome_tamanho="{ item }">
                                    <v-btn @click="downloadEtiqueta(item)">{{ item.nome_tamanho }}</v-btn>
                                </template>
                                <template v-slot:item.foto="{ item }">
                                    <v-img :aspect-ratio="16/9" :src="item.foto" />
                                </template>
                                <template v-slot:item.atrasado="{ item }">
                                    <v-simple-checkbox color="error" v-model="item.atrasado" />
                                </template>
                            </v-data-table>
                        </template>
                        <!-- <template v-else-if="qualTabela === 'conferindo'">
                            <v-data-table :items="produtosEPonto.produtos.conferindo" :loading="loading" loading-text="Carregando produtos" :headers="headersProdutos" :sort-by="['atrasado']" sort-desc>
                                <template v-slot:item.nome_tamanho="{ item }">
                                    <v-btn @click="downloadEtiqueta(item)">{{ item.nome_tamanho }}</v-btn>
                                </template>
                                <template v-slot:item.foto="{ item }">
                                    <v-img :aspect-ratio="16/9" :src="item.foto" />
                                </template>
                                <template v-slot:item.atrasado="{ item }">
                                    <v-simple-checkbox color="error" v-model="item.atrasado" />
                                </template>
                            </v-data-table>
                        </template> -->
                        <template v-else-if="qualTabela === 'retirando'">
                            <v-data-table :items="produtosEPonto.produtos.retirando" :loading="loading" loading-text="Carregando produtos" :headers="headersProdutos" :sort-by="['atrasado']" sort-desc>
                                <template v-slot:item.nome_tamanho="{ item }">
                                    <v-btn @click="downloadEtiqueta(item)">{{ item.nome_tamanho }}</v-btn>
                                </template>
                                <template v-slot:item.foto="{ item }">
                                    <v-img :aspect-ratio="16/9" :src="item.foto" />
                                </template>
                                <template v-slot:item.atrasado="{ item }">
                                    <v-simple-checkbox color="error" v-model="item.atrasado" />
                                </template>
                            </v-data-table>
                        </template>
                    </v-card>
                </template>
            </v-dialog>

            <!-- Modal para QrCode -->
            <v-dialog hide-overlay :items="produtosEPonto.ponto" v-model="modalQrCode" width="unset">
                <template v-if="produtosEPonto.ponto">
                    <v-expand-x-transition>
                        <v-card v-show="expande" height="250" width="250" class="mx-auto">
                            <v-img :src="produtosEPonto.ponto.qrCodeTelefone" />
                        </v-card>
                    </v-expand-x-transition>
                </template>
            </v-dialog>

            <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
                {{ snackbar.texto }}
            </v-snackbar>
        </v-app>
    </div>
</body>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/monitora-pontos.js<?= $versao ?>"></script>
<script src="js/tools/formataTelefone.js"></script>
