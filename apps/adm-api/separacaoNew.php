<?php
header("Location:pagina-indisponivel.php");
die();
// require_once 'cabecalho.php';
// require_once 'classes/faturamento.php';
// require_once 'classes/historico.php';
// require_once 'classes/cheques.php';
// require_once 'classes/lancamento.php';

// acessoUsuarioVendedor();


// unset($_SESSION['faturamento']);

// $usuario = idUsuarioLogado();

// apagaHistoricoUsuarioAntigo();

// limpaUsuarioFaturamento($usuario);

?>
<!-- <v-app class="container-fluid" id="separacaoVUE"> -->
  <!-- <h2 class="font-weight-black display-1 text-center">Separação</h2> -->
  <!-- <v-card color="primary" dark>
    <v-card-title primary-title>
      Separação
      <v-spacer></v-spacer>
      Total: {{quantidadePedidosParaSeparar}}
    </v-card-title>
    <v-card-text>
      <v-row no-gutters>
        <v-col cols="12">
          <v-row>
            <v-col cols="12" sm="6">
              <v-autocomplete v-model="filtros.cliente" :items="listaClientes" :search-input.sync="searchCliente" cache-items outlined label="Nome do Cliente" item-text="nome" item-value="id"></v-autocomplete>
            </v-col>
            <v-col cols="6" sm="4">
              <v-text-field single-line solo light v-model="filtros.faturamento" label="ID Faturamento: "></v-text-field>
            </v-col>
            <v-col cols="6" sm="2" class="text-right">
              <v-btn class="mb-2" color="orange" @click="buscaOnePedido()">Pesquisar <v-icon right>mdi-magnify</v-icon>
              </v-btn>
            </v-col>
          </v-row>
        </v-col>
        <v-col cols="12">
          <v-row>
            <v-col cols="4" sm="4">
              <h2 class="subtitle-1 font-weight-bold" id="separador">{{separador}}</h2>
            </v-col>
            <v-col cols="4" sm="4">
              <h2 class="subtitle-1 font-weight-bold text-center" id="quantidade">Quantidade: {{quantidadeItemsSeparados}}</h2>
            </v-col>
            <v-col cols="4" sm="4" class="text-right">
              <v-btn small color="error" href="index.php" id=botao-voltar>Voltar</v-btn>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
  <v-container>
    <v-slide-x-transition group tag="v-list">
      <v-row dense v-for="(item, i) in listaPedidos" :key="i">
        <v-col cols="12" v-show="(itemSelecionado.id ? itemSelecionado.id == item.id : true)">
          <v-card :color="cardColor(item)" dark>
            <v-progress-linear v-slot:progress :indeterminate="true" color="amber" height="5" :active="item.loading" striped />
            </v-progress-linear>
            <v-row>
              <v-col cols="7">
                <v-card-title class="headline text-no-wrap">Pedido: {{item.id}}</v-card-title>
                <v-card-subtitle v-show="item.acrescimos">Acréscimos: {{item.acrescimos}}</v-card-subtitle>
                <v-card-subtitle>Cliente: {{item.nome}}</v-card-subtitle>
                <v-card-subtitle v-show="item.separador">Separador: {{item.separador}}</v-card-subtitle>
                <v-card-subtitle v-show="item.quantidade">Pares: {{item.quantidade}}</v-card-subtitle>
              </v-col>

              <v-col cols="5" class="d-flex flex-column">
                <v-card-subtitle v-text="item.status" class="text-right"></v-card-subtitle>
                <span class="pr-4 text-right">{{ converteData(item.data_fechamento)}} </span>
              </v-col>
            </v-row>

            <v-card-actions>
              <v-row>
                <v-col cols="6">
                  <v-radio-group v-model="item.prioridade" :mandatory="false" row @change="togglePrioridadeSeparacao(item)" v-show="!separando">
                    <v-radio label="Cliente Aguardando" :value="-1"></v-radio>
                    <v-radio label="Prioridade Alta" :value="0"></v-radio>
                    <v-radio label="Normal" :value="1"></v-radio>
                  </v-radio-group>
                </v-col>

                <v-col cols="6" class="d-flex justify-content-end align-items-end">
                  <template v-if="item.status_separacao == 4">
                    <v-btn text @click.stop.prevent="getOnePedidosParaSeparar(item)" v-show="!separando" v-if="item.id_separador == userID "> Retomar </v-btn>
                  </template>

                  <template v-else>
                    <v-btn outlined dark @click.stop.prevent="getOnePedidosParaSeparar(item)" v-show="!separando" v-if="item.status_separacao != 2 && item.status_separacao != 6"> Separar </v-btn>
                  </template>
                </v-col>
              </v-row>
            </v-card-actions>

            <v-expand-transition>
              <div v-show="item.detalhes">
                <v-divider></v-divider>
                <v-card-text>
                  <template>
                    <v-text-field single-line solo light v-model="quantidadeSeparado" label="Quantidade: " readonly style="position: sticky; top: 65px; z-index: 10"></v-text-field>
                    
                    <v-list flat subheader three-line light>
                      <div class="d-flex">
                        <v-subheader>Corrigir</v-subheader>
                        <v-spacer></v-spacer>
                        <v-subheader>Produtos</v-subheader>
                        <v-spacer></v-spacer>
                        <v-subheader>Separar</v-subheader>
                      </div>

                      <v-list-item-group multiple active-class="">
                        <v-list-item three-line v-for="(produto, index) in produtos" :key="index" @click.stop.prevent="">
                          <v-checkbox color="red" v-model="corrigidos" :value="produto.uuid" :disabled="separados.includes(produto.uuid)" @click.native.stop></v-checkbox>
                          <v-chip class="ma-2" :color="corrigidos.includes(produto.uuid) ? 'error' : separados.includes(produto.uuid) ? 'primary' : 'black'" x-large outlined dark :input-value="separados.includes(produto.uuid) || corrigidos.includes(produto.uuid)" style="height: 120px !important; width: 100%; justify-content: center">
                            <v-list-item-content @click.stop.prevent="" class="justify-content-center">
                              <v-list-item-title class="text-center text-h6 font-weight-black" :class="{'black--text': !separados.includes(produto.uuid) && !corrigidos.includes(produto.uuid) }">{{produto.descricao}}</v-list-item-title>
                              <v-chip :input-value="separados.includes(produto.uuid) || corrigidos.includes(produto.uuid)" label small dark :color="!separados.includes(produto.uuid) && !corrigidos.includes(produto.uuid) ? 'error' : corrigidos.includes(produto.uuid) ? 'error' : 'primary'" class="justify-content-center text-h6 font-weight-black py-1" style="max-width: 70%;"> {{produto.nome_tamanho || produto.tamanho}}</v-chip>
                              <v-chip :input-value="separados.includes(produto.uuid) || corrigidos.includes(produto.uuid)" label small dark :color="!separados.includes(produto.uuid) && !corrigidos.includes(produto.uuid) ? 'black' : corrigidos.includes(produto.uuid) ? 'error' : 'primary'" class="justify-content-center text-h6 font-weight-black py-1" style="max-width: 70%;"> {{produto.localizacao}}</v-chip>
                            </v-list-item-content>
                          </v-chip>
                          <v-checkbox v-model="separados" :value="produto.uuid" :disabled="corrigidos.includes(produto.uuid) || pendentes.includes(produto.uuid)"></v-checkbox>
                        </v-list-item>
                      </v-list-item-group>
                    </v-list>
                  </template>
                  <v-divider></v-divider>
                  <v-pagination circle dark v-model="pagina" :length="lengthProdutos" :total-visible="10" @next="getProdutoPaginado();" @previous="getProdutoPaginado();" @input="getProdutoPaginado()"></v-pagination>
                </v-card-text>
                <v-divider light></v-divider>
                <v-card-actions>
                  <v-card :color="cardColor(item)" dark flat width="100%">
                    <div class="d-flex justify-content-between">
                      <div class="d-flex flex-column">
                        <v-card-title class="headline text-no-wrap">Pedido: {{item.id}}</v-card-title>
                        <v-card-subtitle v-show="item.acrescimos">Acréscimos: {{item.acrescimos}}</v-card-subtitle>
                        <v-card-subtitle>Cliente: {{item.nome}}</v-card-subtitle>
                        <v-card-subtitle v-show="item.separador">Separador: {{item.separador}}</v-card-subtitle>
                        <v-card-subtitle v-show="item.quantidade">Pares: {{item.quantidade}}</v-card-subtitle>
                      </div>

                      <div class="d-flex flex-column justify-content-end">
                        <v-btn class="my-2" color="white" text @click.native.stop="cancelarSeparacaoPedido(item)">Cancelar</v-btn>
                        <v-btn class="my-2" color="white" text @click.native.stop="concluirSeparacaoPedido(item)" :disabled="(separados.length + corrigidos.length) != item.quantidade">Enviar</v-btn>
                      </div>
                    </div>
                  </v-card>
                </v-card-actions>
              </div>
            </v-expand-transition>
          </v-card>
        </v-col>
      </v-row>
    </v-slide-x-transition>
    <v-pagination v-show="!separando" circle dark v-model="filtros.page" :length="lengthPedidos" :total-visible="5" @next="getAllPedidosParaSeparar();" @previous="getAllPedidosParaSeparar();" @input="getAllPedidosParaSeparar()"></v-pagination>
  </v-container>
  <template name="loadingContainer">
    <div class="text-center">
      <v-overlay :value="overlay">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
      </v-overlay>
    </div>
  </template>
  <template name="dialogConteiner">
    <v-row justify="center">
      <v-dialog v-model="dialog" persistent max-width="290">
        <v-card>
          <v-card-title class="headline">{{dialogTitle}}</v-card-title>
          <v-card-text>{{dialogText}}</v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" text @click="dialog = !dialog">Aceitar </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-row>
  </template>
  <div class="row d-flex justify-content-start pa-4">
    <v-btn color="error" href="index.php">Voltar</v-btn>
  </div>
</v-app> -->
<!-- <script type="text/javascript" src="js/jquery.js"></script>
<script src="js/FileSaver.min.js"></script>
<?php //require_once 'rodape.php'; ?>
<script src="js/separacaoNew.js"></script> -->

<style>
  a {
    text-decoration: none !important;
  }

  #wrapper {
    display: flex;
    width: 100%;
  }

  #left-content {
    flex: 2;
  }

  #right-content {
    flex: 1;
  }

  section {
    text-align: right;
  }

  @media (max-width: 500px) {
    #wrapper {
      flex-direction: column;
    }

    #right-content {
      display: flex;
      align-items: baseline
    }

    section {
      flex: 1;
      text-align: center;
    }

    #listaCorrecao {
      flex-direction: column-reverse;
    }

  }
</style>