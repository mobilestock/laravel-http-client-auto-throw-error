<?php
require_once __DIR__ . '/cabecalho.php'
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .container {
        padding: 0 0.5rem;
    }
</style>

<div id="pesquisas" class="container">
    <v-app>
        <h1>Pesquisas meulook</h1>
        <v-data-table :headers="cabecalho" :items="pesquisas" class="elevation-1" :loading="loading" :footer-props="{'items-per-page-options': [10, 50, 100, -1]}" :items-per-page="10">
            <template v-slot:item.razao_social="{ item }">
                <span style="text-transform: capitalize;">
                    {{ item.razao_social }}
                </span>
            </template>
        </v-data-table>
    </v-app>
</div>

<script type="module">
    import pt from "https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js"
    var app = new Vue({
        el: "#pesquisas",
        vuetify: new Vuetify({
            lang: {
                locales: { pt },
                current: "pt"
            }
        }),
        data() {
            return {
                cabecalho: [
                    this.itemGrade('Nome', 'razao_social'),
                    this.itemGrade('Pesquisa', 'pesquisa'),
                    this.itemGrade('Horário', 'horario', true)
                ],
                pesquisas: [],
                loading: false
            }
        },
        methods: {
            async buscaPesquisas() {
                await MobileStockApi('api_meulook/logging/pesquisa')
                    .then(response => response.json())
                    .then(json => {
                        this.pesquisas = json.data
                    })
            },
            itemGrade(label, valor, ordenavel = false) {
                return {
                    text: label,
                    align: 'start',
                    sortable: ordenavel,
                    value: valor
                }
            },
        },
        async mounted() {
            this.loading = true
            await this.buscaPesquisas().finally(() => {
                this.loading = false
            })
        }
    })
</script>