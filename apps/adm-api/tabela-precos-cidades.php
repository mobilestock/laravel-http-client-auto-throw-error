<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador(); ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    hr {
        margin: 0;
    }
    .topo {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    .input_pesquisa {
        margin-top: 1rem !important;
        margin-left: 1rem !important;
        max-width: 25%;
        width: 50rem;
        margin-right: 2rem;
    }
    .medida {
        font-size: 1.15rem;
        margin-bottom: 0.5rem;
    }
    .inputValorCidades {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .itensEsquerda {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
    }
    .containerCidadesModal {
        display: flex;
        margin: 2rem;
        gap: 2rem;
    }
    .containerCidadesModal > .v-autocomplete {
        margin-top: 0.8rem;
    }
    .v-text-field__details {
        display: none;
    }
</style>

<div class="container-fluid" id="app">
    <v-app>
        <br />
        <div class="topo">
            <h4>&emsp;Valor Adicional por Cidade</h4>
            <div class="itensEsquerda">
                <v-btn
                    elevation="2"
                    color="primary"
                    style="margin-top: 0.8rem;"
                    @click="modalNovaCidade.open = true"
                >
                    Adicionar Cidade
                </v-btn>
                <v-text-field
                    v-model="filtro"
                    label="Pesquisar"
                    class="input_pesquisa"
                    append-icon="mdi-magnify"
                    solo
                ></v-text-field>
            </div>
        </div>
        <br />
        <v-card>
            <v-data-table
                :items-per-page="100"
                :loading="loading"
                :headers="headersListaDeCidades"
                :items="listaDeCidades"
                :search="filtro"
            >
                <template v-slot:item.valor_comissao_bonus="{ item }">
                    <div class="inputValorCidades">
                        <span class="medida">R$</span>
                        <v-text-field
                            label="Solo"
                            @change="alteraPrecoCidade({
                                id_cidade: item.id,
                                preco: item.valor_comissao_bonus
                            })"
                            v-model="item.valor_comissao_bonus"
                            solo
                        ></v-text-field>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <v-dialog
            v-model="modalNovaCidade.open"
            max-width="600px">
            <v-card :disabled="loading">
                <v-container class="px-4" fluid>
                    <p>Adicionar Cidade</p>
                    <div class="containerCidadesModal">
                        <v-autocomplete
                            dense
                            label="Cidade"
                            v-model="modalNovaCidade.id"
                            :search-input.sync="inputModalCidade"
                            :items="modalNovaCidade.items"
                            :disabled="loading"
                            :loading="loading"
                        >
                        </v-autocomplete>
                        <div class="inputValorCidades" style="width: 25%;">
                            <span class="medida">R$</span>
                            <v-text-field
                                label="Solo"
                                v-model="modalNovaCidade.preco"
                                solo
                            ></v-text-field>
                        </div>
                    </div>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="secondary"
                            text
                            @click="modalNovaCidade.open = false"
                        >
                            Fechar
                        </v-btn>
                        <v-btn
                            color="primary"
                            text
                            :disabled="!Boolean(modalNovaCidade.id)"
                            @click="alteraPrecoModal"
                        >
                            Adicionar
                        </v-btn>
                    </v-card-actions>
                </v-container>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.mostrar" :color="snackbar.cor">
            {{ snackbar.texto }}
            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="snackbar.mostrar = false">
                    Fechar
                </v-btn>
            </template>
        </v-snackbar>

    </v-app>
</div>

<script type="module" src="js/tabela-precos-cidades.js<?= $versao ?>"></script>
