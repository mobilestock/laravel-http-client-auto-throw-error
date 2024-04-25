<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioVendedor();

?>

<head>
  <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
  <meta charset="UTF-8">
</head>

<v-app id="conferenciaEstoqueReferenciaVue">
  <v-main>
    <template>
      <div style="margin-left: 1.3rem;">
        <h2>
          <b>Conferir estoque por referência</b>
        </h2>
      </div>
    </template>
    <div id="corpo">
      <v-row>
        <div style="width: 13.75rem">
          <v-text-field
            autofocus
            clearable
            dense
            outlined
            prepend-inner-icon="mdi-magnify"
            label="Referência ou ID"
            :loading="isLoading"
            @keydown="pesquisaRapida"
            v-model="pesquisa"
          ></v-text-field>
        </div>
        <v-btn
          color="success"
          class="botoes"
          :disabled="pesquisa === null || pesquisa === ''"
          :loading="isLoading"
          @click="buscaProduto(pesquisa)"
        >
          Pesquisar
        </v-btn>
      </v-row>
    </div>
    <div v-if="listaProdutos.length > 0">
      <v-data-table
        :headers="headerInformacoes"
        :items="listaProdutos"
        :loading="isLoading"
      >
        <template v-slot:item.acao="{ item }">
          <v-btn color="success" @click="redireciona(item)">
            <v-icon dark>mdi-magnify</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </div>
    <div class="botoes">
      <v-btn color="error" @click="retornar">Voltar</v-btn>
    </div>
  </v-main>

  <template>
    <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
      {{ snackbar.texto }}
    </v-snackbar>
  </template>
</v-app>

<style>
  #corpo {
    margin: 1.5rem 2rem 0 2rem;
  }
  .botoes {
    margin-left: 2rem;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/estoque-conferencia-referencia.js<?= $versao ?>"></script>