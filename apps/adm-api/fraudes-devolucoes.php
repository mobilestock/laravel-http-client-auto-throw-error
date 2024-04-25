<?php 

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();

?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .page {
        padding: 0 1rem;
    }
</style>

<div class="page" id="menuFraudesDevolucoes">
    <v-app>
        <v-main>
            <div class="d-flex mt-5 justify-content-around align-center">
                <h1>Menu Fraude Devoluções</h1>
                <v-text-field
                    v-model="pesquisa"
                    hide-details
                    label="Pesquisa"
                    filled
                    rounded
                    dense
                    single-line
                    append-icon="mdi-magnify" class="shrink mx-4">
                </v-text-field>
            </div>
            <div>
                <v-overlay :value="carregando">
                    <v-progress-circular indeterminate size="64"></v-progress-circular>
                </v-overlay>
                <v-data-table
                    :headers="cabecalho"
                    :items="colaboradores_suspeitos"
                    class="elevation-1 mt-4"
                    :search="pesquisa"
                    :footer-props="{ 'items-per-page-options': [30, 50, 100, 200, 300] }"
                >
                    <template v-slot:item.telefone="{ item }">
                        <a @click.prevent="() => telefone_modal = {telefone: item.telefone, nome: item.razao_social}" v-cloak>{{ item.telefone }}</a>
                    </template>
                    <template v-slot:item.limite="{ item }">
                        <v-form @submit.prevent="val => alteraValorMinimo(item.id, val, item.limite)">
                            <v-text-field :value="item.limite" />
                        </v-form>
                    </template>
                    <template v-slot:item.situacao="{ item }">
                        <v-select
                            hide-details
                            dense
                            :value="item.situacao"
                            @input="val => mudaSituacaoFraude(val, item)"
                            :items="situacoes_fraude"
                        >
                            <template v-slot:selection="{ item }">
                                <v-icon v-if="item.value === 'PE'" color="warning">mdi-alert-circle</v-icon>
                                <v-icon v-else-if="item.value === 'LT'">mdi-alert-circle-check</v-icon>
                                <span class="ml-2" v-cloak>{{ item.text }}</span>
                            </template>
                            <template v-slot:item="{ item }">
                                <v-icon v-if="item.value === 'PE'" color="warning">mdi-alert-circle</v-icon>
                                <v-icon v-else-if="item.value === 'LT'">mdi-alert-circle-check</v-icon>
                                <span class="ml-2" v-cloak>{{ item.text }}</span>
                            </template>
                        </v-select>
                    </template>
                </v-data-table>
            </div>
            
            <v-snackbar v-model="snackbar.open" :color="snackbar.cor" v-cloak>{{ snackbar.texto }}</v-snackbar>
        </v-main>
    </v-app>

    <v-dialog :value="colaborador_alterar !== null" @input="val => !val && (colaborador_alterar = null)" max-width="500px" :persistent="carregando" v-cloak>
        <v-card :loading="carregando" :disabled="carregando">
            <v-card-title>Atenção!</v-card-title>
            <v-card-text>
                <div v-if="colaborador_alterar?.situacao === 'FR'">
                    <b>O colaborador que for marcado como fraude terá:</h6>
                    <ol>
                        <li>Logística bloqueada.</li>
                        <li>Expedição bloqueada.</li>
                        <li>Transferência bloqueada.</li>
                    </ol>
                </div><br>
                Tem certeza que deseja alterar? <br>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn :disabled="carregando" @click="colaborador_alterar = null" text>Não</v-btn>
                <v-btn :disabled="carregando" @click="confirmaMudancaSituacaoFraude" text color="error">Sim</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog :value="telefone_modal !== null" @input="val => !val && (telefone_modal = null)" max-width="500px" v-cloak>
        <v-card>
            <v-card-title>Contactar cliente</v-card-title>
            <v-card-text>
                <v-img :src="qrcodeCliente"></v-img>
            </v-card-text>
        </v-card>
    </v-dialog>
</div>

<script src="js/whatsapp.js<?= $versao ?>"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script type="module" src="js/fraudes-devolucoes.js"></script>