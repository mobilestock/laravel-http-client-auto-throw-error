<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<style>
    .v-application p {
        margin-bottom: 2px;
    }
</style>
<div class="container-fluid" id="app">
    <v-app>
        <br />
        <h2 class="d-flex justify-content-between align-items-center">
            Produtos mais vendidos
        </h2>
        <br />
        <div style="display: flex;">
            <div style="margin-right: 0.8rem;">
                <p>Ano</p>
                <v-select
                :items="anos_select"
                v-model="ano_default"
                @change="buscaDados()"
                label="Ano"
                solo
                ></v-select>
            </div>
            <div>
                <p>Mês</p>
                <v-select
                :items="meses_select"
                v-model="mes_default"
                @change="buscaDados()"
                label="Mês"
                solo
                ></v-select>
            </div>
            <div style="display: flex; justify-content: center; flex-direction: column; margin-left: 2rem;">
                <p>Vendas:&ensp;<b>{{formatarNumero(quantidade_vendas)}}</b></p>
                <p>Totalizando:&ensp;<b>R$ {{formatarNumero(valor_vendas)}}</b></p>
            </div>
        </div>
        <v-card>
            <v-data-table :loading="loading" :headers="lista_mais_vendidos_headers" :items="lista_mais_vendidos" :footer-props="{'items-per-page-options': [50, 100, 200, -1]}" :items-per-page="50">
                <template v-slot:item.id="{ item }">
                    <a target="_blank" :href="'fornecedores-produtos.php?id=' + item.id">
                        {{ item.id }} <v-icon v-if="item.promocao != '0'" style="font-size: 1rem; color: indianred;">mdi-percent</v-icon>
                    </a>
                </template>
                <template v-slot:item.quantidade="{ item }">
                    {{ item.quantidade }}
                </template>
                <template v-slot:item.valor="{ item }">
                    R$ {{formatarNumero(item.valor)}}
                </template>
                <template v-slot:item.preco_medio="{ item }">
                    R$ {{formatarNumero(item.preco_medio)}}
                </template>
                <template v-slot:item.custo="{ item }">
                    R$ {{parseFloat(item.custo).toFixed(2)}}
                </template>
                <template v-slot:item.custo_total="{ item }">
                    R$ {{formatarNumero(item.custo_total)}}
                </template>
            </v-data-table>
        </v-card>

        <v-snackbar v-model="snackbar.mostra">
            {{ snackbar.texto }}
            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="snackbar.mostra = false">
                    Fechar
                </v-btn>
            </template>
        </v-snackbar>
    </v-app>
</div>

<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/produtos-mais-vendidos.js"></script>