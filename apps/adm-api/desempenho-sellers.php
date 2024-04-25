<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<v-app id="desempenho-sellers">
    <div class="container">
        <h4 class="text-center">Desempenho Sellers</h4>
        <v-data-table :headers="headers" :items="sellers" :loading="carregando" :search="busca" :items-per-page="15">
            <template v-slot:top>
                <v-text-field
                    v-model="busca"
                    label="Buscar"
                    class="mx-4"
                ></v-text-field>
            </template>
            <template v-slot:item.nome="{ item }">
                <div>
                    <span class="text-capitalize">{{ item.nome.toLowerCase() }}</span>
                    (<a :href="urlMeulook + item.usuario_meulook" target="_blank" rel="noopener noreferrer">{{ item.usuario_meulook }}</a>)
                </div>
            </template>
            <template v-slot:item.taxa_cancelamento="{ item }">
                <span>{{ item.taxa_cancelamento }}%</span>
            </template>
            <template v-slot:item.valor_vendido="{ item }">
                <span>{{ item.valor_vendido | formatarDinheiro }}</span>
            </template>
        </v-data-table>
    </div>
</v-app>

<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/desempenho-sellers.js"></script>