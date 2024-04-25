<?php

require_once __DIR__ . '/cabecalho.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .page {
        padding: 0 1rem;
    }
</style>

<div class="page" id="menufraudes">
    <v-app>
        <div class="d-flex justify-content-between">
            <h1>Menu Fraudes</h1>
            <v-spacer></v-spacer>
            <v-switch style="padding-right: 1rem;" v-model="tela" true-value="suspeitos" false-value="fraudes" label="Suspeitos"></v-switch>
            <v-form v-if="tela === 'fraudes'" @submit.prevent="pagina = 1; buscarColaboradoresFraudulentos()" class="w-50">
                <v-text-field label="Pesquisa" v-model="pesquisa"></v-text-field>
            </v-form>
        </div>
        <div>
            <v-overlay :value="carregando">
                <v-progress-circular indeterminate size="64"></v-progress-circular>
            </v-overlay>

            <v-data-table
                must-sort
                class="elevation-1"
                :sort-by.sync="campoOrdenar.nome_campo"
                :sort-desc.sync="campoOrdenar.decrescente"
                :page.sync="pagina"
                :headers="cabecalho"
                :items="colaboradores"
                :server-items-length="qtdTotalItens"
                :items-per-page.sync="itensPorPagina"
                :footer-props="{ 'items-per-page-options': opcoesItensPorPag }"
                v-if="tela === 'fraudes'"
                @update:sort-desc="buscarColaboradoresFraudulentos"
                @update:page="buscarColaboradoresFraudulentos"
            >

                <template v-slot:item.colaborador.id="{ item }">
                    <a :href="'/extratos.php?id=' + item.colaborador.id">{{ item.colaborador.id }}</a>
                </template>
                <template v-slot:item.colaborador.nome="{ item }">
                    <span :style="item.caracteristicas ? 'color: red; text-transform: capitalize;' : 'text-transform: capitalize;'">
                        {{ item.colaborador.nome }}
                    </span>
                </template>
                <template v-slot:item.colaborador.telefone="{ item }">
                    <a @click.prevent="() => telefoneModal = {telefone: item.colaborador.telefone, nome: item.colaborador.nome}">
                        {{ item.colaborador.telefone.replace(/([0-9]{2})([0-9]{5})([0-9]{4})/, '($1) $2-$3') }}
                    </a>
                </template>
                <template v-slot:item.transacoes.valor_liquido="{ item }">
                    <span>
                        {{ item.transacoes.valor_liquido | dinheiro }}
                    </span>
                </template>
                <template v-slot:item.situacao_fraude="{ item }">
                    <v-select :ref="'select_' + item.colaborador.id" hide-details dense :value="item.situacao_fraude" @input="val => mudaSituacaoFraude(val, item)" :items="[{text: 'Pendente de fraude', value: 'PE'},{text: 'Fraude', value: 'FR'},{text: 'Liberado temporáriamente (Enquanto não compra novamente)', value: 'LT'},{text: 'Liberado permanentemente', value: 'LG'}]">
                        <template v-slot:selection="{ item }">
                            <v-icon v-if="item.value === 'PE'" color="warning">mdi-alert-circle</v-icon>
                            <v-icon v-else-if="item.value === 'FR'" color="error">mdi-cancel</v-icon>
                            <v-icon v-else-if="item.value === 'LT'">mdi-alert-circle-check</v-icon>
                            <v-icon v-else-if="item.value === 'LG'" color="success">mdi-check</v-icon>
                            <span class="ml-2">{{ item.text }}</span>
                        </template>
                        <template v-slot:item="{ item }">
                            <v-icon v-if="item.value === 'PE'" color="warning">mdi-alert-circle</v-icon>
                            <v-icon v-else-if="item.value === 'FR'" color="error">mdi-cancel</v-icon>
                            <v-icon v-else-if="item.value === 'LT'">mdi-alert-circle-check</v-icon>
                            <v-icon v-else-if="item.value === 'LG'" color="success">mdi-check</v-icon>
                            <span class="ml-2">{{ item.text }}</span>
                        </template>
                    </v-select>
                </template>
            </v-data-table>
            <v-data-table v-else :headers="cabecalhoSuspeitos" :items="colaboradoresSuspeitos" class="elevation-1" :loading="loading">
                <template v-slot:item.nome="{ item }">
                    <span style="text-transform: capitalize;">
                        {{ item.colaborador.nome }}
                    </span>
                </template>
                <template v-slot:item.colaborador.telefone="{ item }">
                    <a :href="item.link" target="_blank" rel="noopener noreferrer">
                        {{ item.colaborador.telefone.replace(/([0-9]{2})([0-9]{5})([0-9]{4})/, '($1) $2-$3') }}
                    </a>
                </template>
                <template v-slot:item.transacoes.valor_liquido="{ item }">
                    <span>
                        {{ item.transacoes.valor_liquido | dinheiro }}
                    </span>
                </template>
            </v-data-table>
        </div>

        <v-dialog :value="colaboradorAlterar !== null" @input="val => !val && (colaboradorAlterar = null)" max-width="500px" :persistent="carregando">
            <v-card :loading="carregando" :disabled="carregando">
                <v-card-title>Atenção</v-card-title>
                <v-card-text>
                    <div v-if="colaboradorAlterar?.situacao === 'FR'">
                        Ao afirmar que esse colaborador é fraude o sistema vai cancelar as seguintes transações:

                        <v-data-table hide-default-footer :items="colaboradorAlterar?.transacoes_remover" :headers="cabecalhoListaTransacoes">
                            <template v-slot:item.id="{ item }">
                                <a target="_blanc" :href="`transacao-detalhe.php?id=${item.id}`">{{ item.id }}</a>
                            </template>
                        </v-data-table>
                    </div>
                    <br>
                    Tem certeza que deseja alterar? <br>
                    <b>Essa ação não poderá ser desfeita</b>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn :disabled="carregando" @click="colaboradorAlterar = null" plain>Não</v-btn>
                    <v-btn :disabled="carregando" @click="confirmaMudancaSituacaoFraude" plain color="error">Sim</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog :value="telefoneModal !== null" @input="val => !val && (telefoneModal = null)" max-width="500px">
            <v-card>
                <v-card-title>Contactar cliente</v-card-title>
                <v-card-text>
                    <v-img :src="qrcodeCliente"></v-img>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-app>
</div>

<script src="js/whatsapp.js<?= $versao ?>"></script>
<script type="module" src="js/fraudes.js"></script>
