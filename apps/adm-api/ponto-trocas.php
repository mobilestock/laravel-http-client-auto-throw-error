<?php require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador(); ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<h1 class="text-center">Meu Look Devoluções</h1>
<div class="container-fluid" id="app">

    <v-app>
        <v-card class="elevation-0">
            <v-card-title>
                <v-text-field v-model="search" append-icon="mdi-magnify" label="Pesquisa" single-line hide-details></v-text-field>
            </v-card-title>
            <v-data-table
                :headers="headers"
                :items="listaDePontos"
                :item-class="informaDesabilitado"
                :search="search"
            >
                <template v-slot:item.detalhes="{ item }">
                    <v-btn 
                        elevation="2" 
                        small 
                        :disabled="item.devolucoes_a_enviar === 0 && item.devolucoes_em_transito === 0" 
                        @click="abreModalDeDetalhesDoPonto(item)" 
                        :color="item.alerta_devolucoes ? 'error' : '' " 
                        :loading="item.detalhes  === 1"
                    >
                        <v-icon small>fas fa-edit</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.data_ultimo_envio_ts="{ item }">
                        {{ item.data_ultimo_envio }}
                    </template>
                <template v-slot:item.gerar_pac_reverso="{ item }">
                    <v-btn elevation="2" :disabled="(item.devolucoes_a_enviar === 0 && item.devolucoes_em_transito === 0)" small :loading="item.gerar_pac_reverso === 1" @click="clickPACReverso(item)">
                        Gerar
                    </v-btn>
                </template>
                <template v-slot:item.avisar_recolhimento="{ item }">
                    <v-btn elevation="2" :loading="item.avisar_recolhimento  === 1" :disabled="item.devolucoes_a_enviar === 0" @click="avisaPonto(item)" small>
                        Avisar
                    </v-btn>
                </template>
            </v-data-table>
        </v-card>
    </v-app>
    <v-snackbar 
        v-model="snackbar" 
        style="z-index: 10000;"
    >
        {{ text }}

        <template v-slot:action="{ attrs }">
            <v-btn color="red" text v-bind="attrs" @click="snackbar = false">
                Fechar
            </v-btn>
        </template>
    </v-snackbar>
    <v-dialog v-model="dialog" fullscreen style="z-index: 4000" hide-overlay transition="dialog-bottom-transition">
        <v-card>
            <v-toolbar dark :color="detalhesDoPontoSelecionado?.categoria === 'PE' ? 'grey darken-2' : 'var(--cor-fundo-vermelho)'">
                <v-toolbar-title>Detalhes</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn dark text @click="dialog = false">
                        OK
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <v-card style="width: 100%;" class="my-1">
                <v-card-title>
                    Devoluções
                    <v-spacer></v-spacer>
                </v-card-title>
                <v-data-table 
                    :headers="headersModalProdutos" 
                    :item-class="backgroundLinhaTabela" 
                    :items="detalhesDoPontoSelecionado?.produtos?.a_enviar?.items || [] " 
                    :items-per-page="5" 
                    class="elevation-0"
                >
                    <template v-slot:item.foto="{ item }">
                        <v-img lazy-src="https://upload.wikimedia.org/wikipedia/commons/b/b9/Youtube_loading_symbol_1_(wobbly).gif" max-height="100" max-width="100" :src="item.foto"></v-img>
                    </template>
                    <template v-slot:item.valor="{ item }">
                        {{ formataValores(item.valor) }}
                    </template>
                    <template v-slot:item.acoes="{ item }">
                        <div class="mx-auto">
                            <v-btn
                                dark
                                width="12.25rem"
                                color="green darken-1"
                                @click="notificarDescontoDePonto(item)"
                            >
                                <v-icon/>
                                    mdi-whatsapp
                                </v-icon>
                                <v-spacer></v-spacer>
                                Notificar ponto
                            </v-btn>
                            <v-btn
                                dark
                                width="12.25rem"
                                :color="item.situacao === 'VE' ? 'var(--cor-secundaria-meulook)' : 'deep-orange'"
                                @click="abreModalDescontar(item,'a_enviar')"
                                v-if="item.acoes.podeDescontar"
                            >
                                {{ item.situacao === 'VE' ? 'Descontar Venda' : 'Descontar do Ponto' }}
                            </v-btn>
                        </div>
                    </template>
                    <template v-slot:item.data_criacao_ts="{ item }">
                        {{ item.data_criacao }}
                    </template>
                    <template v-slot:body.append>
                        <tr>
                            <td>Total</td>
                            <td colspan="3"></td>
                            <td>
                                {{ formataValores(detalhesDoPontoSelecionado?.produtos?.a_enviar?.total) }}
                            </td>
                        </tr>
                    </template>
                </v-data-table>
            </v-card>
            <br>
            <br>

            <!-- <v-card style="width: 100%;" class="my-1">
                <v-card-title>
                    Em Transito
                    <v-spacer></v-spacer>
                </v-card-title>
                <v-data-table :headers="headersModalProdutos" :item-class="backgroundLinhaTabela"  :items="detalhesDoPontoSelecionado?.produtos?.em_transito?.items || [] " :items-per-page="5" class="elevation-0">
                    <template v-slot:item.foto="{ item }">
                        <v-tooltip right>
                            <template v-slot:activator="{ on, attrs }">
                                <v-img v-bind="attrs"
                                    v-on="on" lazy-src="https://upload.wikimedia.org/wikipedia/commons/b/b9/Youtube_loading_symbol_1_(wobbly).gif" max-height="100" max-width="100" :src="item.foto"></v-img>
                            </template>
                            <span>{{item.uuid}}</span>
                        </v-tooltip>
                    </template>
                    <template v-slot:item.valor="{ item }">
                        {{ formataValores(item.valor) }}
                    </template>
                    <template v-slot:item.acoes="{ item }">
                        <v-btn
                            color="green darken-1"
                            @click="notificarDescontoDePonto(item)"
                            dark
                        >
                            <v-icon/>
                                mdi-whatsapp
                            </v-icon>
                            <p  style=" margin:0; padding:0;">

                                Notificar ponto
                            </p>
                        </v-btn>
                        <v-btn
                            v-if="item.acoes.podeDescontar"
                            color="deep-orange"
                            @click="abreModalDescontar(item,'em_transito')"
                        >
                        <p style="color: var(--cor-text); margin:0; padding:0;">

                            Descontar do ponto
                        </p>
                        </v-btn>
                    </template>
                    <template v-slot:item.data_criacao_ts="{ item }">
                        {{ item.data_criacao }}
                    </template>
                    <template v-slot:body.append>
                        <tr>
                            <td>Total</td>
                            <td colspan="3"></td>
                            <td>
                                {{ formataValores(detalhesDoPontoSelecionado?.produtos?.em_transito?.total) }}
                            </td>
                        </tr>
                    </template>
                </v-data-table>
            </v-card> -->
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogPacReverso" persistent max-width="400px">
        <v-card>
            <v-card-title>
            <span class="text-h6">Gerar PAC Reverso</span>
            </v-card-title>
            <div
                class="mx-6"
                fluid
                >
                <div class="mt-2">
                    <v-btn 
                        text
                        outlined
                        color="primary"
                        :disabled="loading"
                        @click="clickPACReverso(this.item, true, false)"
                        >
                        PAC a enviar
                    </v-btn>
                    <v-btn 
                        text
                        outlined
                        color="primary"
                        :disabled="loading"
                        @click="clickPACReverso(this.item, true, true)"
                        >
                        PAC em transito
                    </v-btn>
                </div>
            </div>
            <br />
            <v-card-actions>
                <v-btn
                    color="error"
                    text
                    :disabled="loading"
                    @click="dialogPacReverso = false"
                >
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog
      v-model="modalDescontar"
      persistent
      max-width="290"
    >
      <v-card>
        <v-card-title class="text-h5">
          Alerta
        </v-card-title>
        <v-card-text>
            <p>Deseja realmente descontar este produto do ponto</p>
            <v-text-field
                v-model="password"
                :append-icon="show1 ? 'mdi-eye' : 'mdi-eye-off'"
                :type="show1 ? 'text' : 'password'"
                name="input-10-1"
                label="Digite sua senha"
                @click:append="show1 = !show1"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="danger"
            @click="modalDescontar = false"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="green"
            dark
            :loading="loadingDescontar"
            :disable="loadingDescontar"
            @click="confirmaDescontoDePonto"
          >
            Confirmar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

</div>

<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/whatsapp.js<?= $versao ?>"></script>
<script type="module" src="js/ponto-trocas.js<?= $versao?>"></script>