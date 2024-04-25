<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioFornecedor();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="listaSellersPagar">

    <header>
        <h1 class="text-center">Fila de Transferências</h1>
    </header>

    <v-card elevation="1" class="m-2" >
    <v-card
            class="m-2"
            outlined
        >
            <v-card-title class="justify-center">
                Totalizador de Pagamentos
            </v-card-title>
            <v-card-text class="d-flex justify-content-around">
                <div class="row">
                  <div class="col-sm-4 text-center">
                    <span>Total de Saques:
                        <b v-cloak>{{listaTotais.valor_pagamento}}</b>
                    </span>
                  </div>
                  <div class="col-sm-4 text-center">
                    <span>Total Faltando:
                        <b v-cloak>{{listaTotais.valor_pendente}}</b>
                    </span>
                  </div>
                  <div class="col-sm-4 text-center">
                    <span>Total Saldo Mobile:
                        <b v-cloak>{{listaTotais.saldo}}</b>
                    </span>
                  </div>
                  <div class="col-sm-4 text-center">
                    <span>Somatório dos valores filtrados:
                        <b v-cloak>{{somaValoresFiltrados}}</b>
                    </span>
                  </div>
                </div>
            </v-card-text>
            <v-card-actions class="justify-center">
                <v-btn
                    color="amber"
                    class="text-decoration-none"
                    href="monitora-pagamento-auto.php"
                >
                    Monitorar Pagamento Automático
                </v-btn>
            </v-card-actions>
        </v-card>

    </v-card>

    <br />

    <v-card class="elevation-1 m-2 p-2">
      <v-text-field
          v-model="filtro"
          append-icon="mdi-magnify"
          label="Filtrar"
          single-line
          hide-details
      ></v-text-field>
    </v-card>

    <v-data-table
        :custom-filter="pesquisa_customizada"
        :headers="listaPagamentosHeader"
        :items="listaPagamentos"
        :loading="loading"
        :disabled="disabled"
        class="elevation-1 m-2"
        item-key="id_lancamento"
        :search="filtro"
    >

        <template v-slot:item.id_prioridade="{ item }" >
            <p>{{ item.id_prioridade }}</p>
            <small v-if="item.id_transferencia > 0">{{ item.id_transferencia }}</small>
        </template>

        <template v-slot:item.nome_titular="{ item }" >
            <p>
                {{ item.nome_titular }} <small>(ID Conta: {{ item.id }})</small>
            </p>
            <small>{{ item.razao_social }} (ID Colaborador: {{ item.id_colaborador }})</small>
            <br />
            <span :class="corReputacao(item.reputacao)">
                {{ item.reputacao }}
            </span>
        </template>

        <template v-slot:item.valor_pendente="{ item }" >
            <p>{{ item.valor_pendente }}</p>
        </template>

        <template v-slot:item.valor_pago="{ item }" >
            <p>{{ item.valor_pago }}</p>
        </template>

        <template v-slot:item.valor_pagamento="{ item }" >
            <p>{{ item.valor_pagamento }}</p>
        </template>

        <template v-slot:item.saldo="{ item }" >
            <p
                :class="item.saldo > 0 ? 'text-success' : 'text-danger'"
            >{{ item.saldo }}</p>
        </template>

        <template v-slot:item.situacao="{ item }" >
            <p :class="traduzSituacao(item.situacao).class">
                {{ traduzSituacao(item.situacao).texto }}
            </p>
        </template>

        <template v-slot:item.pagamento_bloqueado="{ item }" >
            <v-switch
                v-model="item.pagamento_bloqueado"
                :disabled="disabled"
                @click="bloqueiaContaPagamento(item)"
            ></v-switch>
        </template>

        <template v-slot:item.opcoes="{ item }">
            <div class="d-flex flex-column justify-center align-center">
              <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                      color="success"
                      elevation="2"
                      small
                      class="m-2"
                      aria-label="Inteirar Transferência"
                      @click="mostrarDialogInteiraTransferencia(item)"
                      v-on="on"
                      v-bind="attrs"
                  >
                      <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                  </v-btn>
                </template>
                <span>Inteirar Transferencia</span>
              </v-tooltip>
              <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                    color="error"
                    elevation="2"
                    small
                    class="m-2"
                    aria-label="Excluir Saque"
                    @click="mostrarDialogApagarSaque(item)"
                    v-on="on"
                    v-bind="attrs"
                  >
                    <i class="fas fa-solid fa-trash"></i>
                  </v-btn>
                </template>
                <span>Excluir Transferência</span>
              </v-tooltip>
              <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                    color="yellow"
                    elevation="2"
                    small
                    class="m-2"
                    aria-label="Pagar Manualmente"
                    @click="mostrarDialogPagarManualmente(item)"
                    v-on="on"
                    v-bind="attrs"
                  >
                    <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                  </v-btn>
                </template>
                <span>Pagar Manualmente</span>
              </v-tooltip>
            </div>
        </template>

    </v-data-table>

    <v-snackbar v-cloak :color="snackbar.cor" v-model="snackbar.ativar">
        {{ snackbar.texto }}
    </v-snackbar>

    <!-- Dialog de confirmação para inteirar transferência -->
    <v-dialog
      v-model="dialogInteiraTransferencia"
      persistent
      width="500"
    >
      <v-card :loading="loadingInteiraTransferencia">
        <v-card-title class="text-h5 grey lighten-2">
          Deseja inteirar a transferência {{ dialogInteiraTransferenciaItem }}?
        </v-card-title>

        <v-card-text class="d-flex justify-center align-center mt-10">
          Essa ação irá inteirar a transferência {{dialogInteiraTransferenciaItem}} e não poderá ser desfeita.
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            text
            :disabled="disabled"
            @click="fecharDialogInteiraTransferencia"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            text
            :disabled="disabled"
            @click="inteirarTransferencia"
          >
            Inteirar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog de confirmação para apagar um saque -->
    <v-dialog
      v-model="dialogApagarSaque"
      persistent
      width="500"
    >
      <v-card :loading="loadingApagarSaque">
        <v-card-title class="text-h5 error text-white">
          Deseja apagar o saque {{ dialogApagarSaqueItem }}?
        </v-card-title>

        <v-card-text class="d-flex justify-center align-center mt-10">
          Essa ação irá&nbsp;<b>apagar</b>&nbsp;o saque {{ dialogApagarSaqueItem }} e não poderá ser desfeita.
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            text
            :disabled="disabled"
            @click="fecharDialogApagarSaque"
          >
            Não apagar o saque
          </v-btn>
          <v-btn
            color="error"
            text
            :disabled="disabled"
            @click="apagarSaque"
          >
            Apagar o saque
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog de confirmação para pagamento manual -->
    <v-dialog
      v-model="dialogPagarManualmente"
      persistent
      width="500"
    >
      <v-card :loading="loadingPagarManualmente">
        <v-card-title class="text-h5 grey lighten-2">
          Pagar transferencia {{dialogPagarManualmenteItem}} manualmente?
        </v-card-title>

        <v-card-text class="d-flex justify-center align-center mt-10">
          Essa ação não poderá ser desfeita!!
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            text
            :disabled="disabled"
            @click="fecharDialogPagarManualmente"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            text
            :disabled="disabled"
            @click="pagarManualmente"
          >
            Confirmar Pagamento
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

</v-app>

<style>
    p {
        margin: 0!important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/v-mask/dist/v-mask.min.js"></script>
<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script src="js/lista-sellers-transferencias.js<?= $versao ?>" type="module"></script>
<script src="js/tools/removeAcentos.js"></script>
