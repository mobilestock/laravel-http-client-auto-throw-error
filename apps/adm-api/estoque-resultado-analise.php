<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioVendedor();

?>

<head>
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="estoqueResultadoAnaliseVue">
    <template>
        <div>
            <v-row id="titulo">
                <h2>
                    <b>Resultado da análise</b>
                </h2>
            </v-row>
        </div>
        <div id="infos-card">
            <v-card flat color="grey lighten-4">
                <v-row class="infos-do-card">
                    <h3>Localização: &ensp;</h3>
                    <h4>{{informacoesGerais.localizacao}}</h4>
                </v-row>
                <v-row class="infos-do-card">
                    <h3>Pares Bipados: &ensp;</h3>
                    <h4>{{informacoesGerais.pares}}&ensp;</h4>
                    <h4 v-if="informacoesGerais.pares > 1">pares</h4>
                    <h4 v-else-if="informacoesGerais.pares == 1">par</h4>
                </v-row>
            </v-card>
        </div>
        <div id="gerenciar">
            <v-row>
                <v-btn
                    color="success"
                    id="nova-conferencia"
                    @click="novaConferencia"
                >
                    Nova Conferência
                </v-btn>
                <v-btn class="espaco-gerenciar" color="warning" @click="gerarEtiquetas()">Imprimir Todas Etiquetas</v-btn>
                <v-btn
                    class="espaco-gerenciar"
                    :dark="listaEtiquetas.length !== 0"
                    :disabled="listaEtiquetas.length === 0"
                    @click="gerarEtiquetas(listaEtiquetas)"
                >
                    Imprimir Etiquetas
                </v-btn>
            </v-row>
        </div>
        <div id="produtos">
            <v-data-table
                hide-default-footer
                :headers="headersProdutos"
                :items="listaProdutos"
                :items-per-page="listaProdutos.length"
                :loading="isLoading"
            >
                <template v-slot:item.estoque="{ item }">
                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <span v-bind="attrs" v-on="on">{{ item.estoque }}</span>
                        </template>
                        <span v-if="item.estoque > 0">Estoque que já existe no sistema</span>
                        <span v-else>Não é possível remover estoque inexistente</span>
                    </v-tooltip>
                </template>
                <template v-slot:item.baixar_etiqueta="{ item }">
                    <v-checkbox color="grey darken-3" @click="selecionarProduto(item.index)"></v-checkbox>
                </template>
                <template v-slot:item.adicionar="{ item }">
                    <v-btn
                        color="green"
                        :dark="item.tipo === 'PS'"
                        :disabled="item.tipo !== 'PS'"
                        :loading="isLoadingMovimentacao"
                        @click="movimentaPar(item, 'A')"
                    >
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.remover="{ item }">
                    <v-btn
                        color="red"
                        :dark="item.tipo !== 'PS' && item.estoque > 0"
                        :disabled="item.tipo === 'PS' || item.estoque < 1"
                        :loading="isLoadingMovimentacao"
                        @click="movimentaPar(item, 'R')"
                    >
                        <v-icon>mdi-delete</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
        </div>
    </template>

    <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style>
    #titulo {
        margin: 0 0 1rem 1.5rem;
    }

    #infos-card {
        margin: 0 1rem;
    }

    #gerenciar {
        margin: 1.8rem;
    }
    #nova-conferencia:hover {
        text-decoration: none;
    }

    .espaco-gerenciar {
        margin-top: 0;
        margin-left: 1rem;
    }

    .infos-do-card {
        margin-left: inherit;
        padding-left: 0.5rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/estoque-resultado-analise.js<?= $versao ?>"></script>