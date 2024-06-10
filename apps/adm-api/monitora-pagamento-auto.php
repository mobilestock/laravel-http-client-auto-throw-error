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
    .sumario-cores {
        width: 9rem;
    }
    .box-cor {
        border-color: black;
        border-width: 0.01rem;
        border-style: solid;
        width: 0.9rem;
    }
</style>

<v-app id="monitoraPagamentoAutoVUE">
    <v-main>
        <div class="text-center">
            <h2>Monitora Fila de Pagamento Automático</h2>
            <v-chip v-show="!carregando">{{ formataValorEmReais(valorTotalSaques) }}</v-chip>
        </div>
        <br />
        <div class="p-4 pt-0 text-center">
            <div>
                <h4>Configurações:</h4>
                <v-btn
                    small
                    :color="ativado ? 'error' : 'success'"
                    :disabled="carregando"
                    :loading="carregando"
                    @click="alterarPagamentoAutomatico"
                >
                    {{ ativado ? 'Desativar Evento' : 'Ativar Evento' }}
                </v-btn>
                <v-btn
                    small
                    :dark="!carregando"
                    :disabled="carregando"
                    :loading="carregando"
                    @click="ativarModalRegras = true"
                >Informações Evento</v-btn>
                <v-btn
                    small
                    color="amber"
                    :dark="!carregando"
                    :disabled="carregando"
                    :loading="carregando"
                    @click="atualizarFilaTransferenciaAutomatico"
                >Atualizar Fila</v-btn>
            </div>
            <br />
            <v-data-table
                :headers="headersContemplados"
                :items="contemplados"
                :item-class="(linha) => corPorOrigem(linha.origem)"
                :items-per-page="-1"
                :loading="carregando"
            >
                <template v-slot:item.data_criacao="{ item }">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <span
                                v-bind="attrs"
                                v-on="on"
                            >{{ item.data_criacao }}</span>
                        </template>
                        <span>Pedido feito à {{ item.dias_diferenca }} dias</span>
                    </v-tooltip>
                </template>

                <template v-slot:item.reputacao="{ item }">
                    <template v-if="['CLIENTE', 'ENTREGADOR'].includes(item.origem)">
                        -
                    </template>
                    <template v-else>
                        {{ formataReputacao(item.reputacao) }}
                    </template>
                </template>

                <template v-slot:item.recebedor="{ item }">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <span
                                v-bind="attrs"
                                v-on="on"
                            >{{ item.recebedor.nome_conta_bancaria }}</span>
                        </template>
                        <span>ID conta bancária: {{ item.recebedor.id_conta_bancaria }}</span>
                    </v-tooltip>
                </template>

                <template v-slot:item.valor_pagamento="{ item }">
                    {{ formataValorEmReais(item.valor_pagamento) }}
                </template>
            </v-data-table>
        </div>
    </v-main>

    <!-- Modal de regras -->
    <v-dialog
        persistent
        transition="dialog-bottom-transition"
        v-model="ativarModalRegras"
    >
        <v-card>
            <v-toolbar dark>
                Parâmetros para ser pago automaticamente
                <v-spacer></v-spacer>
                <v-btn icon @click="ativarModalRegras = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-card-text>
                <div class="d-flex justify-content-around mt-4">
                    <div>
                        <h4>Prioridades do pagamento</h4>
                        <ol>
                            <li>Antecipações completando <b>{{ diasTransferenciaSeller.dias_pagamento_transferencia_antecipacao }}</b> dias úteis</li>
                            <li>Saques dos <b>Entregadores</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_ENTREGADOR }} dias úteis</li>
                            <li>Saques de não fornecedores completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_CLIENTE }} dias úteis</li>
                            <li>Saques dos <b>Melhores Fabricantes</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE }} dias úteis</li>
                            <li>Saques dos fornecedores <b>Excelentes</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_EXCELENTE }} dias úteis</li>
                            <li>Saques dos fornecedores <b>Regulares</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_REGULAR }} dias úteis</li>
                            <li>Saques dos fornecedores <b>Ruins</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_RUIM }} dias úteis</li>
                            <li>Saques dos fornecedores <b>Novatos</b> completando {{ diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_NOVATO }} dias úteis</li>
                        </ol>
                    </div>
                    <div>
                        <h4>Sumário reputações</h4>
                        <ul>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 deep-purple accent-1 rounded"></div>
                                    Entregador
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 brown lighten-5 rounded"></div>
                                    Antecipação
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 blue lighten-5 rounded"></div>
                                    Melhor Fabricante
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 green lighten-5 rounded"></div>
                                    Excelente
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 amber lighten-5 rounded"></div>
                                    Regular
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 red lighten-5 rounded"></div>
                                    Ruim
                                </div>
                            </li>
                            <li>
                                <div class="d-flex sumario-cores">
                                    <div class="box-cor mb-1 mr-2 rounded"></div>
                                    Sem Reputação
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </v-card-text>
        </v-card>
    </v-dialog>

    <!-- Alerta para usuário -->
    <v-snackbar
        :color="snackbar.cor"
        v-model="snackbar.ativar"
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/monitora-pagamento-auto.js"></script>
