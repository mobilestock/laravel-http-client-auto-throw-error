<?php require_once __DIR__ . '/cabecalho.php' ?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
table {
  width: 100%;
}
tr {
  display: flex;
}
th, td {
  flex: 1;
  font-size: 0.75rem;
}
</style>

<v-app id="marketplace-fornecedor">
  <div class="container-md">
    <h3 class="text-center">Detalhamento Financeiro</h3>
    <br />
    <v-row>
      <v-col>
        <v-menu
          v-model="menuDataInicial"
          :close-on-content-click="false"
          offset-y
        >
          <template v-slot:activator="{ on, attrs }">
            <v-text-field
              v-model="dataInicialFormatada"
              label="Data inicial"
              prepend-icon="mdi-calendar"
              v-bind="attrs"
              v-on="on"
              readonly
            ></v-text-field>
          </template>
          <v-date-picker
            v-model="dataInicial"
            @input="menuDataInicial = false"
          ></v-date-picker>
        </v-menu>
      </v-col>
      <v-col>
        <v-menu
          v-model="menuDataFinal"
          :close-on-content-click="false"
          offset-y
        >
          <template v-slot:activator="{ on, attrs }">
            <v-text-field
              v-model="dataFinalFormatada"
              label="Data Final"
              prepend-icon="mdi-calendar"
              v-bind="attrs"
              v-on="on"
              readonly
            ></v-text-field>
          </template>
          <v-date-picker
            v-model="dataFinal"
            @input="menuDataFinal = false"
          ></v-date-picker>
        </v-menu>
      </v-col>
    </v-row>
    <v-btn
      block
      class="my-5"
      @click="limparFiltros()"
      v-if="dataInicial || dataFinal"
      :disabled="carregando"
    >
      Limpar filtros
    </v-btn>
    <br />
    <table v-if="itens?.length">
      <tr>
        <th class="text-left">Data</th>
        <th class="text-center" style="flex:2">Origem</th>
        <th class="text-center">Valor</th>
        <th class="text-right">Saldo</th>
      </tr>
      <tr class="py-2 border-bottom" v-for="item in itens">
        <td class="text-left">{{ item.data }}</td>
        <td class="text-center" style="flex:2">
          {{ item.origem }} <span v-if="item.motivo_cancelamento === 'Defeito'"> (Defeito)</span>
          <span v-if="item.transacao_origem > 0"> - {{ item.transacao_origem }}</span>
        </td>
        <td :class="'text-center ' + corDinheiro(item.valor)">
          {{ formatarDinheiro(item.valor) }}
          <span v-if="item.valor_pago" :class="corDinheiro(item.valor_pago)"><br />{{ formatarDinheiro(item.valor_pago) }}</span>
        </td>
        <td class="text-right">
          <b>{{ formatarDinheiro(item.saldo) }}</b>
        </td>
      </tr>
    </table>
    <p v-else class="mt-3 text-center">
      <b>{{ !itens ? 'Aguarde...' : 'Não há movimentações' }}</b>
    </p>
  </div>
  <v-snackbar v-model="mostrarSnackBar" timeout="2000">{{ mensagemSnackBar }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/marketplace-fornecedor.js"></script>