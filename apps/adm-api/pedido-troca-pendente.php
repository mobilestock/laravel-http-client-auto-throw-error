<?php

use MobileStock\helper\DB;
use MobileStock\service\ColaboradoresService;

// $link = "pedido-confirmar.php";
require_once 'cabecalho.php';
require_once 'classes/colaboradores.php';
require_once 'classes/troca-pendente.php';
require_once 'classes/painel.php';
require_once 'classes/cadastros.php';

acessoUsuarioVendedor();


//armazena usuario logado
$usuario = idUsuarioLogado();
$id_cliente = "";
if (isset($_GET['identificacao'])) {
  $pedido = DB::select("SELECT *, (SELECT colaboradores.razao_social FROM colaboradores WHERE colaboradores.id = troca_pendente_item.id_cliente) razao_social FROM troca_pendente_item WHERE uuid = '{$_GET['identificacao']}'", [], null, 'fetch');
  $id_cliente = $pedido['id_cliente'];
  $redirect = true;
} else {
  if (isset($_GET['cliente'])) {
    $id_cliente = $_GET['cliente'];
    verificaSeEstaEmUso($usuario, $id_cliente);
  } elseif (clienteSessao()) {
    $id_cliente = clienteSessao();
    verificaSeEstaEmUso($usuario, $id_cliente);
  }


  $cliente = buscaCliente($id_cliente);
  bloqueiaCliente($id_cliente, $usuario);
  $pedido['razao_social'] = $cliente['razao_social'];
}
$contatoClienteBotao = ColaboradoresService::buscaTelefoneCliente($id_cliente);
// registraClienteSessao($id_cliente);
?>

<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">

<style>
  input {
    background-color: unset !important;
  }

  .v-list-item {
    transition: border-radius .4s;
  }

  #painel .form-control {
    border: solid 2px;
  }

  .v-tabs-bar {
    width: max-content;
  }

  input[type="checkbox"] {
    z-index: 99999999999999;
  }

  .contato {
    color: white;
    background-color: forestgreen;
    font-size: medium;
    height: auto;
  }

  .contato:hover {
    opacity: 80%;
  }
</style>

<script type="text/x-template" id="trocas-confirmadas">
  <div>
    <v-card class="mt-2" outlined>
      <v-card-title>
        <h3 class="text-center w-100">Trocas Pendentes Confirmadas</h3>
      </v-card-title>

      <v-dialog v-model="expanded">
        <v-card v-if="itemExpand" class="mt-3">
          <v-card-title>Detalhes taxas</v-card-title>
          <v-card-text>
            <span v-html="itemExpand.detalhes_taxa"></span>
          </v-card-text>
        </v-card>
      </v-dialog>

      <v-card-text>
        <div class="row p-3 d-flex justify-content-center" style="align-items: center;">
          <v-text-field clearable label="Filtrar" append-icon="mdi-magnify" v-model="filtroTrocasPendentesConfirmadas"></v-text-field>
          <v-btn @click="gerarEtiquetasTrocaConfirmata" small :disabled="!trocasPendentesConfirmadas.find(el => el.marcado_etiqueta)" color="deep-purple accent-4" class="ml-2 text-white">Gerar etiquetas</v-btn>
        </div>
        <v-data-table :item-class="calculaCorItemTabela" :footer-props="{'items-per-page-text':'Itens por página'}" :search="filtroTrocasPendentesConfirmadas" sort-desc sort-by="data_hora" :loading="loadingTrocaPendente" :headers="cabecalhoTrocasPendentesConfirmadas" :items="trocasPendentesConfirmadas">

            <template v-slot:item.nome_tamanho="{ item }">
              <v-chip dark>{{ item.nome_tamanho }}</v-chip>
            </template>

            <template v-slot:item.preco="{ item }">
              <v-chip color="gray">R$ {{ item.preco }}</v-chip>
            </template>

            <template v-slot:item.data_hora="{ item }">
              {{ item.data_hora | formataData }}
            </template>

            <template v-slot:item.data_compra="{ item }">
              {{ item.data_compra | formataData }}
            </template>

            <template v-slot:item.taxa="{ item }">
              <v-chip color="warning" @click="expanded = !expanded; itemExpand = item">
                {{ item.taxa.toLocaleString("pt-br", {style: "currency",currency: "BRL"}) }}
              </v-chip>
            </template>

            <template v-slot:item.acoes="{ item }">
              <div class="d-flex align-content-center justify-content-center">
<!--                <v-btn @click="removerItemTrocaPendenteConfirmada(item)" icon color="error">-->
<!--                  <v-icon>mdi-delete</v-icon>-->
<!--                </v-btn>-->
                <v-checkbox v-model="item.marcado_etiqueta" class="mt-2 m-0 p-0"></v-checkbox>
              </div>
            </template>

            <template v-slot:item.descricao_defeito="{ item }">
              <v-checkbox :label="item.descricao_defeito" disabled v-model="item.defeito == 1 ? true : false"></v-checkbox>
            </template>

        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- <div class="row d-flex flex-row-reverse">
      <div class="col-sm-4">
        <v-btn color="deep-purple accent-4" dark href="relatorios/pedido-troca-pendente-relatorio.php" class="btn btn-default btn-block"><b>VISUALIZAR RELATÓRIO</b></v-btn>
      </div>
      <div class="col-sm-4"></div>
      <div class="col-sm-4">
      </div>
    </div> -->
    </div>
  </div>
</script>

<script type="text/x-template" id="trocas-agendadas">
  <div>
    <v-form ref="form" @submit.prevent>
      <v-card color="grey lighten-5" dense>
        <v-card-title class="p-0 d-flex justify-content-between primary lighten-1 text-white">
          <div @click="toggleHistorico = !toggleHistorico" class="col-sm-8 w-100 d-flex justify-content-between">
            Filtrar Histórico
            <v-icon large v-if="!toggleHistorico" dark>mdi-chevron-down</v-icon>
            <v-icon large v-if="toggleHistorico" dark>mdi-chevron-up</v-icon>
          </div>
          <div class="col-sm-4 h-100">
            <input type="hidden" name="id_cliente" value="<?= $id_cliente; ?>">
            <v-text-field v-model="filtroCodigoBarras" prepend-inner-icon="mdi-barcode-scan" dark clearable flat solo-inverted hide-details label="Código de barras" id="codigo_barras" />
          </div>
        </v-card-title>
        <input type="hidden" id="usuarioLogado" value="<?= $_SESSION['usuario_logado'] ?>">

        <v-expand-transition>
          <div v-show="toggleHistorico">
            <v-card-text class="row m-0 d-flex justify-content-between" id="grupo-filtro">

              <v-card class="col-sm-12">
                <v-text-field label="Número do pedido" v-model="faturamentoFiltro"></v-text-field>
              </v-card>

              <v-card light class="col-sm-6 mt-2">
                <v-autocomplete v-model="tamanhoFiltro" label="Numeração" item-value="value" item-text="name" :items="categorias.find(el => el.id == categoriaFiltro)?.subcategoria === 'RO' ? [{name: 'P', value: 20},{name: 'M', value: 30}, {name: 'G', value: 40}, {name: 'GG', value: 45}] : numeracao"></v-autocomplete>
                <v-autocomplete v-model="fornecedorFiltro" item-text="razao_social" item-value="id" :items="fornecedores" label="Fornecedor"></v-autocomplete>
                <v-select label="Linha" :items="linhas" item-text="nome" item-value="id" v-model="linhaFiltro"></v-select>
              </v-card>

              <v-card light class="col-sm-6 mt-2">
                <input-categorias nome='Categorias' descricao='Buscar por categorias' v-model="categoriaFiltro" :amostra="categorias" :entrada="categorias"></input-categorias>
                <v-text-field label="Referencia / ID" v-model="corFiltro"></v-text-field>
              </v-card>

              <v-card-actions class="w-100 d-flex justify-content-between">
                <v-btn color="error" @click="reset">
                  Limpar
                  <v-icon>
                    mdi-close
                  </v-icon>
                </v-btn>

                <v-btn dark type="submit" @click="filtrar" class="btn btn-dark">
                  Filtrar
                  <i class="fas fa-search"></i>
                </v-btn>
              </v-card-actions>
            </v-card-text>
            
            <v-overlay absolute :opacity=".3" :value="overlay">
              <v-progress-circular indeterminate size="64"></v-progress-circular>
            </v-overlay>
            <br>

            <v-container class="position-relative">
              <v-card class="card shadow p-1" v-for="(pedido, index) in ordernarLista" :key="index">
                <label :for="'abre-painel-' + index" class="w-100">
                  <v-card-title class="row d-flex justify-content-between">

                    <span>
                      <h5 class="mt-3 mb-0 pb-0 text-center">{{ pedido[0]['id_faturamento'] | filtraIdFaturamento}}</h5>
                      <small>{{ pedido[0]['data_hora'] }}</small>
                    </span>
                    <v-btn :id="'abre-painel-' + index" icon @click="fecharPainel(index)">
                      <v-icon>fas fa-chevron-down</v-icon>
                    </v-btn>
                  </v-card-title>
                </label>

                <v-list :id="'painel_' + index" v-show="abrirPaineis" class="w-100">

                  <v-list-item class="p-0 pb-1" :id="'painel_' + i + '_' + item.uuid" v-for="(item, i) in pedido" :key="i">

                    <v-lazy transition="slide-x-transition" class="w-100">

                      <div v-ripple :class="`${listaTrocasAgendadas.find(el => el.uuid === item.uuid) ? 'text-danger border border-danger': 'border border-white'} bg-white rounded-pill pl-5 pr-5 pt-2 pb-2 d-flex w-100 h-100`">
                        <v-overlay class="mb-2" v-if="item.mensagem_indisponivel !== ''" absolute>
                          <v-icon>mdi-lock</v-icon>
                          {{ item.mensagem_indisponivel }}
                          <!-- Esse produto já passou do prazo de troca de 365 dias -->
                        </v-overlay>
                        <a class="ml-2 mt-1" :href="item.fotoProduto" target="_blank">
                          <v-img class="rounded" :src="item.fotoProduto" width="80" :alt="item.descricao"/>
                        </a>
                        <div class="ml-2 d-flex justify-content-between w-100">
                          <span>
                            <h5 class="font-weight-bold">{{ `${item.id_produto} - ${item.descricao}` }}</h5>
                            <p style="font-size:14px; text-align: left;" class="card-text">{{ 'Fornecedor: ' + item.fornecedor }}
                              <br> <small>{{ 'Fornecedor Origem: ' + item.fornecedor_origem }}</small>
                              <br> Tamanho: <span>{{ item.nome_tamanho }}</span>
                            </p>
                          </span>

                          <v-btn style="height:auto" @click="adicionarItem(item, i)" v-show="item.mensagem_indisponivel === '' && !listaTrocasAgendadas.find(el => el.uuid === item.uuid)" color="error">
                            <v-icon large>mdi-plus</v-icon>
                          </v-btn>
                        </div>
                      </div>
                    </v-lazy>

                  </v-list-item>

                </v-list>
              </v-card>

              <v-card-actions class="d-flex justify-content-between position-sticky" style="bottom: 0">
                <span></span>
                <v-pagination large v-if="historicos.length !== 0" @input="filtrar" v-model="paginaHistorico" color="error" dark circle :length="historicos === false ? paginaHistorico : paginaHistorico + 1"></v-pagination>
                <span>
                  <v-btn @click="toggleHistorico = false" dark fab v-show="listaTrocasAgendadas.length !== 0 && historicos.length !== 0">
                    <v-icon>mdi-arrow-down</v-icon>
                  </v-btn>
                </span>
              </v-card-actions>
            </v-container>
          </div>
        </v-expand-transition>

      </v-card>
    </v-form>
  
    <v-card color="grey lighten-4" dense class="position-relative mt-3">
      <v-card-title class="p-0 d-flex justify-content-between grey text-white position-sticky" style="top: 70px; z-index: 300">
        <div class="col-sm-5 w-100 d-flex justify-content-between">
          Conferir trocas
          <div>
            <?php if ($contatoClienteBotao) { ?>
            
              <a class="btn btn-md contato" id="contatoCliente" target="_blank" onclick="" style="color: white" href="https://api.whatsapp.com/send/?phone=55<?= $contatoClienteBotao ?>">Contato  <i class="fab fa-whatsapp"></i></a>
            <?php } else { ?>
              <button class="btn btn-md contato" id="contatoCliente" onclick="alert('Este cliente não possui telefone cadastrado.')" style="color: white">Contato  <i class="fab fa-whatsapp"></i></button>
              
            <?php } ?>
          </div>
        </div>
        <div class="col-sm-3 h-100">
            <v-text-field v-model="filtroTrocasConferir" prepend-inner-icon="mdi-magnify" dark clearable flat solo-inverted hide-details label="Filtrar trocas"></v-text-field>
        </div>
        <div class="col-sm-4 h-100">
          <input type="hidden" name="id_cliente" value="<?= $id_cliente; ?>">
          <form @submit.prevent="bipaProdutoCodBarras">
            <v-text-field v-model="codBarrasPesquisa" prepend-inner-icon="mdi-barcode-scan" dark clearable flat solo-inverted hide-details label="Código de barras"></v-text-field>
          </form>
        </div>
      </v-card-title>

      <v-card-text>
        <v-card class="mt-5 position-sticky" style="top: 170px; z-index: 300">
          <v-card-title>
            <div class="d-flex justify-content-between align-items-center w-100">
              <span>Pares ({{ listaProdutosAdicionados.length }}/{{ listaTrocasAgendadas.length }})</span>
              <v-subheader>{{ dataIncercaoUltimaTroca }}</v-subheader>
            </div>
            <div class="d-flex justify-content-between align-items-center w-100">
              <span>
              Crédito: {{ valorTotalTrocasAgendadas | dinheiro }}
              <br>
              Taxa: {{ valorTotalTaxaTrocasAgendadas | dinheiro }}
              </span>
              <v-progress-circular
                :value="(100 / listaTrocasAgendadas.length) * listaProdutosAdicionados.length"
                class="mr-2"
              ></v-progress-circular>
            </div>
          </v-card-title>
        </v-card>

      <v-card>
        <v-container class="mt-3">
          <!-- <div class="d-flex">
            <v-subheader>PAC Indevido</v-subheader>
            <v-spacer></v-spacer>
            <v-subheader>Errado</v-subheader>
            <v-spacer></v-spacer>
            <v-subheader>Defeito</v-subheader>
            <v-spacer></v-spacer>
            <v-subheader>Correto</v-subheader>
          </div> -->
          <div class="row">
            <div class="col-sm-1">PAC Indevido</div>
            <div class="col-sm-1">Errado</div>
            <div class="col-sm-2"></div>
            <div class="col-sm-4"></div>
            <div class="col-sm-3 d-flex flex-column align-items-center justify-content-center">Defeito</div>
            <div class="col-sm-1">Correto</div>
          </div>
          <v-list class="w-100 text-center">
            <span v-if="listaTrocasAgendadas.length === 0">Nenhuma troca agendada disponivel</span>
            <v-data-iterator :search="filtroTrocasConferir" no-data-text="" hide-default-footer :items="listaTrocasAgendadas" :page="paginaTrocasAgendadas">
              <template v-slot:default="props">
                <v-list-item style="max-height: 200px" class="p-0 pb-1 d-flex flex-column" v-for="(item, i) in props.items" :key="i">
                  <div class="d-flex flex-row-reverse position-absolute w-100 pr-10 mt-1 mr-2">
                    <v-chip v-if="item.agendada" style="z-index:200" color="grey" label>Agendada</v-chip>
                  </div>
                  <v-lazy transition="slide-x-transition" class="w-100">
                    <div class="row">
                      <!-- Define o style do card: -->
                      <div v-ripple :class="`${item.alerta_defeito === true 
                        ? 'bg-warning text-dark rounded-pill pl-5 pr-5 pt-2 pb-2 d-flex w-100 h-100 justify-content-between align-items-center position-relative' 
                        : `${listaProdutosAdicionados.find(el => el.uuid === item.uuid) 
                            ? `${listaProdutosAdicionados.find(el => el.uuid === item.uuid).correto === true
                              ? 'text-primary border border-primary' 
                              : `${listaProdutosAdicionados.find(el => el.uuid === item.uuid).defeito === true
                                ? 'text-warning border border-warning'
                                : 'text-danger border border-danger'}`
                            }`
                            : 'border border-dark'
                          } rounded-pill pl-5 pr-5 pt-2 pb-2 d-flex w-100 h-100 justify-content-between align-items-center position-relative}`}`" >
                        <div class="col-sm-1">
                          <v-checkbox v-model="item.pacIndevido" :false-value="null" :disabled="listaProdutosAdicionados.find(el => el.uuid === item.uuid) && (item.correto || item.defeito || item.incorreto)" @change="item.correto = false; item.errado = false; item.defeito = false; adicionaItem(item)" :id="`checkbox-pacIndevido${item.uuid}`" color="error"></v-checkbox>
                        </div>
                        <div class="col-sm-1">
                          <label style="display: contents;" :for="`checkbox-errado${item.uuid}`">
                            <v-checkbox v-model="item.incorreto" :false-value="null" :disabled="listaProdutosAdicionados.find(el => el.uuid === item.uuid) && (item.correto || item.defeito || item.pacIndevido)" @change="item.correto = false; item.pacIndevido = false; adicionaItem(item)" :id="`checkbox-errado${item.uuid}`" color="error"></v-checkbox>
                          </label>
                        </div>
                        <div class="col-sm-2">
                          <v-img style="overflow: inherit;" class="rounded position-relative" :src="item.caminho" width="80" :alt="item.produto"></v-img>
                        </div>
                        <div class="col-sm-4">
                            <span>
                            <h5 class="font-weight-bold">{{ `${item.id_produto} - ${item.produto}` }}</h5>
                              <p style="font-size:15px; text-align: left;" class="card-text">
                                Tamanho: {{ item.nome_tamanho }}
                                  <br> Preco: {{ item.preco | dinheiro }}
                                  <br> Taxa: {{ item.taxa | dinheiro }}
                              </p>
                            </span>
                          </div>
                        <div class="col-sm-3">
                          <label :for="`checkbox-defeito${i}`" class="w-100 d-flex flex-column align-items-center justify-content-center">
                            <v-checkbox :id="`checkbox-defeito${i}`" :disabled="listaProdutosAdicionados.find(el => el.uuid === item.uuid) && (item.correto || item.incorreto || item.pacIndevido)" @change="item.correto = false; item.incorreto = false; item.pacIndevido = false; item.descricao_defeito = ''; adicionaItem(item)" color="warning" v-model="item.defeito"></v-checkbox>
                            <v-text-field solo placeholder="Esse produto é defeito" v-model="item.descricao_defeito" v-if="item.defeito"></v-text-field>
                          </label>
                          </div>
                        <div class="col-sm-1">  
                          <label :for="`checkbox-correto${item.uuid}`" class="w-100 h-100 d-flex flex-row-reverse justify-content-between align-items-center">
                            <v-checkbox v-model="item.correto" :false-value="null" :disabled="listaProdutosAdicionados.find(el => el.uuid === item.uuid) && !item.correto" @change="item.correto = true; item.pacIndevido = false; adicionaItem(item)" :id="`checkbox-correto${item.uuid}`"></v-checkbox>
                          </label>
                        </div>

                      </div>
                    </div>
                  </v-lazy>

                </v-list-item>
              </template>
            </v-data-iterator>
            <div class="position-sticky" style="bottom: 0">
              <v-pagination circle color="error" dark v-model="paginaTrocasAgendadas" :length="Math.ceil(listaTrocasAgendadas.length / 10)"></v-pagination>
            </div>
          </v-list>
        </v-container>
      </v-card>
      </v-card-text>
      <v-card-actions>
        <div class="w-100 d-flex flex-row-reverse" >
          <v-btn @click="confirmaTroca" :loading="loadingTrocaPendente" :disabled="listaProdutosAdicionados.length === 0" color="primary">Confirmar</v-btn>
        </div>
      </v-card-actions>
    </v-card>
  </div>
</script>

<div id="app" class="body-novo">
  <v-app id="aplicacao">
    <div class="container-fluid body-novo">

      <input type="hidden" id="idCliente" value="<?= $id_cliente ?>">
      <input type="hidden" id="redirect" value="<?= $redirect ?>">
      <input type="hidden" id="uuid-identificacao" value="<?= $_GET['identificacao'] ?>">
      <h1 class="text-center"><b>Troca Pendente</b></h1>

      <v-card>

        <div class="w-100 d-flex justify-content-between align-items-center">
          <h3 class="ml-2 w-100"><?= $id_cliente . ' - ' . $pedido['razao_social'] ?></h3>
          <v-tabs class="w-100" icons-and-text v-model="tabAtual">

            <v-tab v-for="(tab, i) in tabs" :key="i">
              <span class="ml-2" text-color="white">
                {{ tab.nome }}
              </span>
              <v-icon>
                {{ tab.icone }}
              </v-icon>
            </v-tab>

          </v-tabs>
          <span class="w-100 d-flex flex-row-reverse">
<!--            <v-btn outlined text onclick="mostrarDetalhes('painel')" color="primary">Saldo</v-btn>-->
            <v-btn href="pedido-painel.php" color="error">Voltar</v-btn>
          </span>
        </div>
<!--        <v-card-text id="painel" style="display:none;">-->
<!--          <div class="row">-->
<!--            --><?php //require_once 'modulo/painel.php'; ?>
<!---->
<!--            <div class="col-sm-2">-->
<!--              <label>Saldo Histórico</label>-->
<!--              <a class="btn btn-default btn-block" href="saldo-historico.php?cliente=--><?//= clienteSessao(); ?><!--&pedido=troca-pendente">Acessar</a>-->
<!--            </div>-->
<!--          </div>-->
<!---->
<!--        </v-card-text>-->

      </v-card>

      <v-main id="router-view" class="mt-3 pl-4 pr-4">

        <confirmar-troca v-show="tabAtual === 0"></confirmar-troca>
        <trocas-confirmadas v-if="tabAtual === 1"></trocas-confirmadas>
      </v-main>
  </v-app>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<!--<script src="js/mostrar-detalhes.js--><?//= $versao ?><!--"></script>-->
<script src="js/MobileStockApi.js<?= $versao ?>"></script>
<script src="js/troca-pendente-historico-produtos.js<?= $versao ?>"></script>
<?php require_once 'rodape.php';
require_once __DIR__ . '/src/components/InputCategorias.php'; ?>