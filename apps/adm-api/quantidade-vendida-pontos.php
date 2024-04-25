<?php 

require_once __DIR__ . '/cabecalho.php';

?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .pagina {
        padding: 0 1rem;
    }
</style>

<div id="quantidadeEntregasPontos" class="pagina">
    <v-app>
        <h1 style="text-align: center; margin-top: 3rem;">Vendas Pontos</h1>
        <v-data-table 
            :headers="cabecalho" 
            :items="entregasPontos" 
            :loading="loading" 
            class="elevation-3" 
            style="margin-top: 3rem;" 
            :footer-props="{ 'items-per-page-options': [15,30,50,100,200,300] }"
        >
            <template v-slot:item.nome="{ item }">
                {{ item.nome }} ({{ item.razao_social }})
            </template>
            <template v-slot:item.telefone="{ item }">
                <v-btn :disabled="item.telefone === null" @click="mensagemWhatsapp(item.telefone)">
                    <v-icon>mdi-whatsapp</v-icon>
                </v-btn>
            </template>
        </v-data-table>
        <v-snackbar v-model="snackbar.open" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </v-app>
</div>

<script src="js/whatsapp.js" <?= $versao ?>></script>
<script type="module" src="js/quantidade-vendida-pontos.js"></script>