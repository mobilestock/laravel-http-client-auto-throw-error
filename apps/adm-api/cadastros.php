<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioFinanceiro();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />

<div id="app">
    <v-app>
        <v-main class="pa-5">
            <h1 class="text-center mt-5">Usuários</h1>
            <v-card class="py-10 mt-10">
                <v-card-title>Pesquisar</v-card-title>
                <v-card-subtitle>
                    <span>Razão Social, telefone ou CPF</span>
                </v-card-subtitle>
                <v-card-text>
                    <v-form @submit.prevent="buscaColaboradores">
                        <v-text-field label="Pesquisar"></v-text-field>
                    </v-form>
                    <v-select
                        :items="permissoes"
                        item-text="text"
                        item-value="value"
                        label="Nível de Acesso"
                        filled
                        return-object
                        @change="defineNivelAcesso"
                    ></v-select>
                </v-card-text>
            </v-card>
            <div style="display: flex; justify-content: center;" class="mt-10">
                <v-btn onclick="location.reload()">
                <span class="material-symbols-outlined">
                    restore_page
                </span>
                    limpar
                </v-btn>
            </div>
            <v-data-table v-if="usuarios.length > 0" :headers="header_usuarios" :items="usuarios" class="elevation-1 mt-10">
                <template v-slot:item.acoes="{ item }">
                <a :href="`cadastro.php?id_colaborador=${item.id_colaborador}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="color: white; text-decoration: none;"
                    >
                        <v-btn color="primary">
                            <v-icon dark>
                                mdi-wrench
                            </v-icon>
                                Painel
                        </v-btn>
                    </a>
                </template>
            </v-data-table>
        </v-main>
        <v-snackbar v-model="snackbar.open" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
        <v-overlay absolute :value="loading">
            <v-progress-circular indeterminate size="64" />
        </v-overlay>
    </v-app>
</div>

<script type="module" src="js/cadastros.js" <?= $versao ?>></script>
<script src="js/tools/formataTelefone.js"></script>
