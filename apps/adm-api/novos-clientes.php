<?php
require_once __DIR__ . '/cabecalho.php'
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .page {
        padding: 0 1rem;
    }
</style>

<div class="page" id="menuClientes">
    <v-app>
        <h1>Novos clientes</h1>
        <div>
            <v-data-table :headers="cabecalho" :items="colaboradores" class="elevation-1">
                <template v-slot:item.valor_liquido="{ item }">
                        R$ {{ item.valor_liquido }}
                </template>
            </v-data-table>
        </div>
    </v-app>
</div>

<script  type="module" src="js/novos-clientes.js"></script>