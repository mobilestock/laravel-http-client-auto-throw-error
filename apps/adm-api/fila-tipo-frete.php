<?php
use MobileStock\helper\Globals;

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="//unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet"/>
    <script src="//unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="//unpkg.com/vue2-leaflet"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<style>
    .div-tipos {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        margin-left: 0.5rem;
        align-items: flex-start;
        max-width: 40rem;
    }
    .botao-gerenciar-pontos {
        margin-top: 0.8rem;
    }
    .medida-tarifa-entrega {
        font-size: 1.15rem;
    }
    .mapa-raio {
        width: 100%;
        height: 42rem;
    }
    .tarifa-div {
        margin-top: 1.8rem;
    }
    .botoes-aprovacao {
        display: flex;
        gap: 0.2rem;
    }

    .footer-fechar {
        display: flex;
        justify-content: flex-end;
    }

    .painel-mapa-raio {
        position: absolute;
        display: flex;
        top: 0;
        left: 0;
        z-index: 500;
        width: 4rem;
        margin-top: 1rem;
        padding: 1rem;
        gap: 1rem;
        flex-direction: column;
    }

    .leaflet-div-icon {
        background: white;
        border-radius: 50%;
        padding: 0.3rem !important;
        filter: invert(1);
    }
</style>

<div class="container-fluid" id="app">
    <v-app>
        <h3 v-cloak>Fila de Pontos</h3>
        <div class="div-tipos">
            <v-select
                v-model="tipoFrete"
                :items="['PONTOS','ENTREGADORES']"
                label="Tipo Frete"
            ></v-select>
            <v-select
                v-model="visualizacao"
                :items="['ELEGIVEIS','PENDENTES']"
                label="Situação"
            ></v-select>
            <v-btn
                class="botao-gerenciar-pontos"
                color="primary"
                href="gerenciar-pontos.php"
            >Gerenciar Pontos Aprovados</v-btn>
        </div>
        <v-card>
            <v-data-table
                :loading="loading"
                :headers="tipoFrete === 'PONTOS' ? headersPontos : headersEntregadores"
                :items="listaDePontos"
                :items-per-page="100"
                :footer-props="{
                    itemsPerPageOptions: [100, 200, 400, 600, 800, -1],
                }"
                :search="pesquisa"
                sort-desc
                multi-sort
            >
                <template v-slot:top>
                    <div class="mx-2">
                        <v-text-field
                            dense
                            outlined
                            append-icon="mdi-magnify"
                            label="Pesquisar"
                            :loading="loading"
                            v-model="pesquisa"
                        ></v-text-field>
                    </div>
                </template>

                <template v-slot:item.id="{ item }">
                    {{ item.id }}
                </template>
                <template v-slot:item.razao_social="{ item }">
                    &ensp;{{ item.razao_social }}
                </template>
                <template v-slot:item.telefone="{ item }">
                    <v-btn
                        depressed
                        color="transparent"
                        @click="gerirModalContato(item)"
                    >
                        <v-icon color="green">mdi-whatsapp</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.cidade="{ item }">
                    {{ item.cidade }}
                </template>
                <template v-slot:item.endereco="{ item }">
                    <small>{{ item.endereco }}</small>
                </template>
                <template v-slot:item.raios="{ item }">
                    <v-btn
                        depressed
                        color="secondary"
                        @click="buscarMapaCidade(item)"
                    >
                        <v-icon>mdi-map-marker</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.cidades="{ item }">
                    <v-btn
                        depressed
                        color="secondary"
                        :loading="loading"
                        @click="gerirModalCidadesEntregadores(item)"
                    >
                        <v-icon>mdi-map-marker</v-icon>
                        &ensp;Cidades atendidas
                    </v-btn>
                </template>
                <template v-slot:item.street_view="{ item }">
                    <v-btn
                        depressed
                        color="secondary"
                        :loading="loading"
                        @click="streetView(item)"
                    >
                        <v-icon>mdi-map-search</v-icon>
                        &ensp;Street View
                    </v-btn>
                </template>
                <template v-slot:item.documentos="{ item }">
                    <v-btn
                        depressed
                        color="primary"
                        @click="buscarDocumentos(item)"
                        :loading="loadingDocumentos"
                    >
                        <v-icon>mdi-file-document</v-icon>
                        &ensp;Documentos
                    </v-btn>
                </template>
                <template v-slot:item.gestao="{ item }">
                    <div class="botoes-aprovacao">
                        <v-btn
                            depressed
                            color="error"
                            :disabled="loading"
                            v-if="visualizacao === 'ELEGIVEIS'"
                            @click="atualizarSituacao(item, 'REJEITADO')"
                        >
                            <v-icon>mdi-close</v-icon>
                            &ensp;Rejeitar
                        </v-btn>
                        <v-btn
                            depressed
                            color="success"
                            :disabled="loading"
                            @click="atualizarSituacao(item, 'APROVADO')"
                        >
                            <v-icon>mdi-check</v-icon>
                            &ensp;Aprovar
                        </v-btn>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Modal central para mensagem WhatsApp -->
        <v-dialog
            width="38rem"
            v-cloak
            v-model="modalContato.mostrar"
        >
            <v-card>
                <v-card-title class="justify-content-center">
                    <h5 v-cloak>Entrar em contato</h5>
                </v-card-title>
                <v-card-text>
                    <div class="text-center">
                        <v-img
                            class="mx-auto"
                            height="16rem"
                            width="16rem"
                            :alt="modalContato.link"
                            :src="'<?= Globals::geraQRCODE('') ?>' + modalContato.qrCode"
                        ></v-img>
                        <a target="_blank" :href="modalContato.link">
                            Enviar mensagem para o ponto
                        </a>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Modal de documentos ENTREGADORES -->
        <v-dialog
            persistent
            width="50rem"
            v-model="modalDocumentos.mostrar"
        >
            <v-card>
                <v-card-title class="justify-content-center">
                    <h6 v-cloak>Documentos</h6>
                </v-card-title>
                <v-card-text>
                    <v-img
                        contain
                        class="imagem-documentos"
                        max-height="20rem"
                        v-for="documento in modalDocumentos.documentos"
                        :src="documento"
                    />
                </v-card-text>
                <v-card-actions class="justify-content-center">
                    <v-btn
                        text
                        color="error"
                        v-cloak
                        @click="() => modalDocumentos = { mostrar: false }"
                    >Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal configura raio ENTREGADORES -->
        <v-dialog
            persistent
            max-width="90%"
            v-cloak
            v-model="modalMapa.mostrar && modalMapa.dados !== null && modalMapa.selecionado !== null"
        >
            <v-card>
                <v-card-title class="justify-content-center">
                    <h6 v-cloak v-if="modalMapa.selecionado !== null">Área de cobertura de <b>{{ modalMapa.selecionado.cidade }}</b></h6>
                </v-card-title>
                <v-card-text>
                    <l-map
                        class="mapa-raio"
                        :options="modalMapa.options"
                        :zoom="modalMapa.zoom"
                        :center="modalMapa.center"
                    >
                        <l-tile-layer :url="modalMapa.url"></l-tile-layer>
                        <l-marker
                            v-for="marker in modalMapa.dados"
                            :lat-lng="[marker.latitude, marker.longitude]"
                        >
                            <l-tooltip
                                :content="marker.nome_tipo_frete"
                            ></l-tooltip>
                            <l-icon
                                :icon-size="[44,44]"
                                :icon-anchor="dynamicAnchor"
                                :icon-url="marker.tipo_ponto === 'PP' ? 'images/icons/icon_ponto.png' : 'images/icons/icon_entregador.png'"
                                class-name="leaflet-div-icon"
                            />
                        </l-marker>
                        <l-circle
                            v-for="marker in modalMapa.dados"
                            :color="retornaCorRaio(marker)"
                            :fill-color="retornaCorRaio(marker)"
                            :lat-lng="[marker.latitude, marker.longitude]"
                            :radius="marker.raio"
                        ></l-circle>
                        <l-polygon
                            color="green"
                            v-if="modalMapa.limites !== null"
                            :lat-lngs="modalMapa.limites"
                        ></l-polygon>
                    </l-map>
                </v-card-text>
                <v-card-actions class="justify-content-end">
                    <v-btn
                        depressed
                        color="error"
                        :disabled="loading"
                        @click="() => modalMapa = {
                            ...modalMapa,
                            limites: null,
                            mostrar: false,
                            dados: null,
                            selecionado: null
                        }"
                    >&emsp;Fechar&emsp;</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal configuração cidades ENTREGADORES -->
        <v-dialog
            persistent
            width="60rem"
            v-model="modalCidades.mostrar && modalCidades.dados !== null"
        >
            <v-card>
                <v-card-title class="justify-content-center">
                    <h6 v-cloak>Cidades do Entregador</h6>
                </v-card-title>
                <v-card-text>
                    <v-data-table
                        disable-pagination
                        hide-default-footer
                        :loading="loadingCidades"
                        v-if="modalCidades.dados?.cidades"
                        :headers="headersCidades"
                        :items="modalCidades.dados?.cidades"
                    >

                        <template v-slot:item.raio="{ item }">
                            <v-btn
                                depressed
                                color="primary"
                                :loading="loading"
                                @click="buscarMapaCidade(modalCidades.dados, item)"
                            >
                                <v-icon>mdi-map-marker</v-icon>
                                &ensp;Cobertura
                            </v-btn>
                        </template>

                        <template v-slot:item.valor_entrega="{ item }">
                            <div class="tarifa-div">
                                <v-text-field
                                    solo
                                    type="number"
                                    placeholder="0.00"
                                    :disabled="loadingCidades"
                                    v-model="item.valor_entrega"
                                    @input="debounceSalvarNovosDados(item)"
                                >
                                    <template v-slot:prepend-inner>
                                        <span class="mr-1 medida-tarifa-entrega">R$</span>
                                    </template>
                                </v-text-field>
                            </div>
                        </template>

                        <template v-slot:item.prazo_forcar_entrega="{ item }">
                            <div class="tarifa-div">
                                <v-text-field
                                    solo
                                    step="1"
                                    type="number"
                                    width="100%"
                                    v-model="item.prazo_forcar_entrega"
                                    @input="debounceSalvarNovosDados(item)"
                                ></v-text-field>
                            </div>
                        </template>

                        <template v-slot:item.esta_ativo="{ item }">
                            <div class="d-flex justify-content-center">
                                <v-switch
                                    :disabled="loadingCidades"
                                    @change="atualizarStatusCidade(item)"
                                    v-model="item.esta_ativo"
                                ></v-switch>
                            </div>
                        </template>

                        <template v-slot:item.eh_elegivel="{ item }">
                            <div class="d-flex justify-content-center">
                                <v-icon v-if="item.eh_elegivel" color="success">mdi-check</v-icon>
                                <v-icon v-else color="error">mdi-close</v-icon>
                            </div>
                        </template>
                    </v-data-table>
                </v-card-text>
                <v-card-actions class="footer-fechar">
                    <v-btn
                        text
                        v-cloak
                        color="error"
                        @click="() => modalCidades = { mostrar: false, dados: null }"
                    >Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar alertas -->
        <v-snackbar v-model="snackbar.mostrar" timeout="2000" :color="snackbar.cor" dark>
            {{ snackbar.texto }}
            <template v-slot:action="{ attrs }">
                <v-btn dark text v-bind="attrs" @click="snackbar.mostrar = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </template>
        </v-snackbar>
    </v-app>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/whatsapp.js"></script>
<script type="module" src="js/fila-tipo-frete.js<?= $versao ?>"></script>
