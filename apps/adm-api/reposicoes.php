<?php

require_once 'cabecalho.php';

acessoUsuarioFornecedor();
?>
<style>
    #image-container {
        max-width: 100vw;
    }

    #image-container>img {
        width: 100%;
    }
    .entregue {
        background-color: lightgray;
    }

    .em_aberto {
        background-color: lightgreen;
    }

    .parcialmente_entregue {
        background-color: orange;
    }
</style>
<input type="hidden" id="gera_mensagem" value="<?php  ?>">

<v-app class="container-fluid" id="comprasVue">
    <v-card color="light-blue darken-2" dark class="pa-4 mb-4">
        <!--filtros -->
        <v-row>
            <v-col cols="12" class="hidden-sm-and-down">
                <v-card-title class="py-6">
                    <h1 class="font-weight-bold display-2">{{fornecedor ? 'Lista de Pedidos' : 'Reposições'}}</h1>
                </v-card-title>
                <v-card-subtitle class="ml-4">{{ new Date().toLocaleString('pt-br',{dateStyle: 'full'}) }}</v-card-subtitle>
            </v-col>
            <v-col cols="6" sm="2">
                <v-text-field v-model="filtros.id" label="Número" outlined dense></v-text-field>
            </v-col>
            <v-col cols="6" sm="4" v-show="!fornecedor">
                <v-autocomplete v-model="filtros.fornecedor" :items="selectFornecedor" :search-input.sync="buscaFornecedor" cache-items outlined dense label="Nome do Fornecedor" item-text="nome" item-value="id" clearable></v-autocomplete>
            </v-col>
            <v-col cols="6" sm="3">
                <v-menu ref="menu" v-model="menu" :close-on-content-click="false" :return-value.sync="datesEmissao" transition="scale-transition" offset-y min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field outlined dense hide-details v-model="dateRangeText" label="Data Emissão:" prepend-inner-icon="event" readonly v-on="on" append-icon="mdi-close" @click:append.stop.prevent="filtros.data_inicial_emissao = filtros.data_fim_emissao = ''; datesEmissao = []"></v-text-field>
                    </template>
                    <v-date-picker v-model="datesEmissao" color="primary" no-title scrollable range :first-day-of-week="1" locale="pt-br">
                        <v-spacer></v-spacer>
                        <v-btn text color="primary" @click="menu = false">Cancel</v-btn>
                        <v-btn text color="primary" @click="$refs.menu.save(datesEmissao)">OK</v-btn>
                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="6" sm="3">
                <v-menu ref="menu2" v-model="menu2" :close-on-content-click="false" :return-value.sync="datesPrevisao" transition="scale-transition" offset-y min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field outlined dense hide-details v-model="dateRangeTextPrevisao" label="Data Previsão:" prepend-inner-icon="event" readonly v-on="on" append-icon="mdi-close" @click:append.stop.prevent="filtros.data_inicial_previsao = filtros.data_fim_previsao = ''; datesPrevisao = []"></v-text-field>
                    </template>
                    <v-date-picker v-model="datesPrevisao" color="primary" no-title scrollable range :first-day-of-week="1" locale="pt-br">
                        <v-spacer></v-spacer>
                        <v-btn text color="primary" @click="menu2 = false">Cancel</v-btn>
                        <v-btn text color="primary" @click="$refs.menu2.save(datesPrevisao)">OK</v-btn>
                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="6" sm="2">
                <v-text-field v-model="filtros.tamanho" label="Tamanho" outlined dense></v-text-field>
            </v-col>
            <v-col cols="6" sm="6" md="4">
                <v-text-field outlined dense label="Referência" v-model="filtros.referencia"></v-text-field>
            </v-col>
            <v-col cols="6" sm="6" md="3">
                <v-select :items="listaSituacoes" label="Situação" item-text="situacao" item-value="id" v-model="filtros.situacao" outlined dense></v-select>
            </v-col>
            <v-col cols="6" sm="3" class="text-right">
                <v-btn color="orange" @click="buscaListaReposicoes(true)">Pesquisar <v-icon right>mdi-magnify</v-icon>
                </v-btn>
            </v-col>
        </v-row>
    </v-card>
    <v-card>
        <v-row>
            <v-col cols="12">
                <v-card-title primary-title>
                    <v-btn color="error" :href=" fornecedor ? 'dashboard-fornecedores.php' : 'menu-sistema.php'">Voltar</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn dark color="green" href="cadastrar-reposicao.php">Cadastrar</v-btn>
                </v-card-title>
                <v-card-text>
                    <v-data-table
                        :headers="headers"
                        :items="listaReposicoes"
                        :options.sync="options"
                        :server-items-length="itemsPorPagina"
                        :loading="loading"
                        class="elevation-1"
                        no-data-text="Nenhum registro"
                        no-results-text="Nenhum dado encontrado"
                        loading-text="Buscando dados"
                    >
                        <template v-slot:item="{ item }">
                            <tr>
                                <td class="text-start">{{item.id}}</td>
                                <td class="text-center">{{item.fornecedor}}</td>
                                <td class="text-center" :class="{'entregue': item.situacao === 'ENTREGUE', 'em_aberto': item.situacao === 'EM_ABERTO', 'parcialmente_entregue': item.situacao === 'PARCIALMENTE_ENTREGUE'}">{{item.situacao.replace('_', ' ')}}</td>
                                <td class="text-center">{{item.preco_total}}</td>
                                <td class="text-center">{{converteData(item.data_criacao)}}</td>
                                <td class="text-center">{{converteData(item.data_previsao)}}</td>
                                <td class="text-center">

                                    <v-btn dark small :color="item.situacao == 'ENTREGUE' ? 'green' : 'warning'" @click="editarCompra(item.id)">
                                        <v-icon>{{item.situacao == 'ENTREGUE' ?'fas fa-eye' : 'mdi-pencil'  }}</v-icon>
                                    </v-btn>

                                </td>
                            </tr>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-col>
        </v-row>
    </v-card>

    <v-overlay :value="overlay" style="z-index: 3000;">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
    </v-overlay>

    <v-snackbar v-model="snackbar.open" timeout="3000" :color="snackbar.color" style="z-index: 2200">
        {{ snackbar.text }}

        <template v-slot:action="{ attrs }">
            <v-btn color="white" icon v-bind="attrs" @click="snackbar.open = false" tile>
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </template>
    </v-snackbar>
</v-app>

<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script src="js/reposicoes.js<?= $versao ?>"></script>
