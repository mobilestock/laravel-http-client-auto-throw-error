<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioFornecedor();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<v-app id="administracao-seller">
  <div class="container">
    <v-btn block large class="mb-3" @click="irParaTela('marketplace-fornecedor.php')">Detalhamento Financeiro</v-btn>
    <v-btn block large class="mb-3" disabled>Lançamento Futuros (Em breve)</v-btn>
    <v-btn block large class="mb-3" @click="irParaTela('estoque-detalhado.php')">Detalhamento Estoque</v-btn>
    <v-btn block large class="mb-3" @click="irParaVendas()" :loading="carregandoRequisicao" :disabled="carregandoRequisicao">Ver minhas vendas</v-btn>
    <v-btn block large class="mb-3" @click="irParaTela('meu-desempenho-meulook.php')">Meu desempenho</v-btn>
  </div>
  <v-snackbar v-model="mostrarSnackBar" timeout="2000">{{ mensagemSnackBar }}</v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/administracao-seller.js"></script>