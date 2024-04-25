<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioFornecedor();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>
<style>
    .grid-produto{
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

</style>

<body>
    <div class="container-fluid" id="geraEtiquetaAvulsaVUE">
        <v-app>
            <main>
                <v-text-field
                    label="Pesquisa por ID"
                    v-model="pesquisa"
                    solo
                    hint="Digite o id do produto"
                    @keydown.enter="buscaProduto"
                >
                    <template v-slot:append-outer>
                        <v-btn
                            @click="buscaProduto"
                            :loading="carregando"
                        >
                            Buscar
                        </v-btn>
                    </template>
                    
                </v-text-field>
                <section v-if="produto != null" class="grid-produto">
                    <v-img
                        max-height="500"
                        max-width="500"
                        :src="produto?.foto"
                    ></v-img>
                    <v-col cols="12" >
                        <v-card 
                            outlined
                        >
                            <v-row no-gutters>
                                <v-col 
                                    cols="12" 
                                    class="d-flex align-center"
                                >
                                    <v-card-title>
                                        Grade
                                    </v-card-title>
                                </v-col>
                                <v-col 
                                    v-if="produto?.lista.length > 0" 
                                    v-for="(grade, index) in produto?.lista" 
                                    :key="index" 
                                    class="d-flex flex-column text-center px-2"
                                >
                                    <span class="subtitle-1 mb-1">{{grade.tamanho}}</span>
                                    <v-text-field 
                                        v-model="grade.quantidade" 
                                        label="Quantidade" 
                                        outlined 
                                        dense 
                                        type="number"
                                        @keydown.enter="imprimeEtiquetas"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-card>
                    </v-col>
                    <v-btn
                        elevation="2"
                        :disabled="!produto?.lista.some(item => item.quantidade > 0)"
                        x-large
                        @click="imprimeEtiquetas"
                    >Gerar etiquetas</v-btn>
                </section>
                <section v-else style="text-align: center;"><h4>Pesquise por algum produto</h4></section>
            </main>
            <v-snackbar v-model="snackbar" :color="colorSnackbar">
                {{ textoSnackbar }}
                <template v-slot:action="{ attrs }">
                    <v-btn outlined dark v-bind="attrs" @click="snackbar = false">
                        Fechar
                    </v-btn>
                </template>
            </v-snackbar>
        </v-app>
    </div>
</body>



<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/gera-etiqueta-avulsa.js<?= $versao ?>"></script>