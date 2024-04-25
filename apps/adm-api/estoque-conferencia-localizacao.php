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

<v-app id="conferenciaEstoqueLocalizacaoVue">
  <v-main>
    <template>
      <div style="margin-left: 1.3rem;">
        <h2>
          <b>Conferir estoque por localização</b>
        </h2>
      </div>
    </template>
    <div id="corpo">
      <v-row>
        <div style="width: 13.75rem;">
          <v-autocomplete
            auto-select-first
            autofocus
            clearable
            dense
            outlined
            prepend-inner-icon="mdi-map-marker"
            label="Localização"
            :disabled="localizacaoSelecionada !== ''"
            :items="localizacoes"
            :loading="isLoading"
            v-model="localizacao"
          ></v-autocomplete>
        </div>
        <v-btn
          color="success"
          class="selecionar"
          :disabled="localizacao === null || localizacao === ''"
          :loading="isLoading"
          @click="localizacaoSelecionada = localizacao"
          v-if="localizacaoSelecionada === ''"
        >
          Selecionar local
        </v-btn>
        <v-btn
          color="error"
          class="selecionar"
          :disabled="isLoadingAnalise"
          @click="limpaLocal"
          v-else
        >
          Selecionar outro local
        </v-btn>
      </v-row>
      <v-row>
        <v-text-field
          dense
          outlined
          label="Código de barras"
          prepend-inner-icon="mdi-barcode"
          style="width: 13.75rem;"
          :disabled="localizacaoSelecionada === ''"
          v-model="codigoDigitado"
          @keydown="salvaCodigo"
        ></v-text-field>
        <h3>
          <b class="selecionar">Pares: &ensp; {{ codigosSelecionados.length }}</b>
        </h3>
        <v-spacer></v-spacer>
        <v-btn color="success" :disabled="localizacaoSelecionada === ''" :loading="isLoadingAnalise" @click="analisarCodigos">Analisar</v-btn>
      </v-row>
    </div>
    <template v-if="codigosSelecionados.length > 0">
      <div>
        <v-data-table
          hide-default-footer
          :headers="headersListaCodigos"
          :items="codigosSelecionados"
          :items-per-page="codigosSelecionados.length"
          :loading="isLoadingAnalise"
        >
          <template v-slot:item.acao="{ item }">
            <v-btn color="error" :loading="isLoadingAnalise" @click="removeCodigo(item)">
              <v-icon dark>mdi-close</v-icon>
            </v-btn>
          </template>
          <template v-slot:item.codigo="{ item }">
            <b>{{ item.codigo }}</b>
          </template>
        </v-data-table>
      </div>
    </template>
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

  .selecionar {
    margin-left: 2rem;
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/estoque-conferencia-localizacao.js<?= $versao ?>"></script>