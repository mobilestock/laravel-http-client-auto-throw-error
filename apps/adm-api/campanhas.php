<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    #previsualizacao-iphonese {
        width: calc(375px - 1rem);
    }
    #previsualizacao-iphonexr {
        width: calc(414px - 1rem);
    }
    #previsualizacao-pc {
        width: calc(768px - 1rem);
    }
    #previsualizacao-campanha-ativa {
        max-width: 500px;
    }
    .previsualizacao {
        object-fit: cover;
        aspect-ratio: 5 / 1;
    }
</style>

<v-app id="campanhas">
    <v-container>
        <v-form>
            <h2>Nova campanha</h2>
            <v-row>
                <v-col>
                    <v-text-field
                        v-model="formulario.url_pagina"
                        type="url"
                        label="URL da página"
                        :loading="carregando"
                        :disabled="carregando"
                        placeholder="https://meulook.net.br/produto/74718"
                        required
                    ></v-text-field>
                </v-col>
                <v-col>
                    <v-file-input
                        v-model="formulario.url_imagem"
                        label="Imagem (Proporção ideal 5:1)"
                        accept=".png, .jpg, .jpeg"
                        :loading="carregando"
                        :disabled="carregando"
                        required
                    ></v-file-input>
                </v-col>
            </v-row>
            <v-col v-if="formulario.url_imagem" class="d-flex flex-column justify-center align-center">
                <h3>Pre-visualização:</h3>
                <span>Iphone SE</span>
                <img class="previsualizacao mb-3" id="previsualizacao-iphonese" alt="Imagem Iphone SE" />
                <span>Iphone XR</span>
                <img class="previsualizacao mb-3" id="previsualizacao-iphonexr" alt="Imagem Iphone XR" />
                <span>PC</span>
                <img class="previsualizacao mb-5" id="previsualizacao-pc" alt="Imagem Iphone PC" />
                <v-btn
                    :loading="carregando"
                    :disabled="inputCriarDisabled"
                    color="primary"
                    @click="criarCampanha"
                >
                    Criar Campanha
                </v-btn>
            </v-col>
        </v-form>
        <hr>
        <div
            v-if="campanhaAtiva.id"
            class="d-flex flex-column align-center"
        >
            <h2>Campanha Ativa</h2>
            <img
                class="mb-5"
                id="previsualizacao-campanha-ativa"
                :src="campanhaAtiva.url_imagem"
                alt="Imagem Campanha Ativa"
            />
            <v-btn
                :disabled="carregando"
                color="error"
                @click="dialogConfirmarDesativarCampanha = true"
            >
                Desativar Campanha
            </v-btn>
        </div>
    </v-container>
    <v-dialog
        v-model="dialogConfirmarDesativarCampanha"
        width="auto"
    >
        <v-card>
            <v-card-title>Desativar Campanha</v-card-title>
            <v-card-text>
                <span>Tem certeza que deseja desativar a campanha?</span>
            </v-card-text>
            <v-card-actions>
                <v-btn
                    :loading="carregando"
                    color="error"
                    @click="desativarCampanha"
                >
                    Desativar Campanha
                </v-btn>
                <v-btn
                    :loading="carregando"
                    color="primary"
                    @click="dialogConfirmarDesativarCampanha = false"
                >
                    Cancelar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <v-snackbar v-model="snack.ativo">{{ snack.mensagem }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/campanhas.js"></script>
