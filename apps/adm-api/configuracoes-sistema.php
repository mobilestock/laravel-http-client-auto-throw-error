<?php

use MobileStock\service\ColaboradoresService;

require_once 'cabecalho.php';
require_once 'classes/configuracoes.php';
require_once 'vendor/autoload.php';

acessoUsuarioFinanceiro();

$fornec = new ColaboradoresService();
$fornecedores = $fornec->listaColaboradores('F');
$configuracoes = buscaConfiguracoes();
?>

<style>
  /* The switch - the box around the slider */
  .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
  }

  /* Hide default HTML checkbox */
  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  /* The slider */
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
  }

  input:checked+.slider {
    background-color: #2196F3;
  }

  input:focus+.slider {
    box-shadow: 0 0 1px #2196F3;
  }

  input:checked+.slider:before {
    -webkit-transform: translateX(26px);
    -ms-transform: translateX(26px);
    transform: translateX(26px);
  }

  /* Rounded sliders */
  .slider.round {
    border-radius: 34px;
  }

  .slider.round:before {
    border-radius: 50%;
  }

  .card-menu {
    border: 1px solid #C0C0C0;
    border-radius: 5px;
    box-shadow: 5px 5px 5px #C0C0C0;
    padding: 20px;
    margin: 10px;
  }

  .container-fluid {
    color: #4F4F4F;
  }
  .tabela-taxas {
    width: 100%;
  }
  .input-alertas {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  .input-alertas textarea {
    background-color: var(--cor-fundo-botao-cinza-claro);
  }
  .input-alertas-actions {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    margin-left: 1%;
  }
  .configuracoes-raio-ponto {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-left: 2rem;
  }
  .valores-configuracoes-ponto > .v-input--dense {
    min-width: 15rem;
    margin-bottom: 0.5rem;
  }
</style>
<v-app class="fill-height p-2" id="taxasConfigVUE">

  <form action="controle/configuracoes-altera.php" method="post" id='formulario'>
    <h4><b>Configurações</b></h4><br />

    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="marketplace-tab" data-toggle="tab" href="#marketplace" role="tab" aria-controls="marketplace" aria-selected="true">Marketplace</a>
      </li>
      <li class="nav-item">
        <a
            class="nav-link"
            id="regras-fulfillment-tab"
            data-toggle="tab"
            href="#regras-fulfillment"
            role="tab"
            aria-controls="cliente"
            aria-selected="false"
        >
            Regras do Estoque Fulfillment
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="taxas-tab" data-toggle="tab" href="#taxas" role="tab" aria-controls="taxas" aria-selected="false">Taxas</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="meios-pagamento-tab" data-toggle="tab" href="#meios-pagamento" role="tab" aria-controls="taxas" aria-selected="false">Meios de pagamento</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="percentual-freteiros-tab" data-toggle="tab" href="#percentual-freteiros" role="tab" aria-controls="taxas" aria-selected="false">Configurações Frete</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="fatores-reputacao-tab" data-toggle="tab" href="#fatores-reputacao" role="tab" aria-controls="taxas" aria-selected="false">Fatores Reputação</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="pontuacoes-produtos-tab" data-toggle="tab" href="#pontuacoes-produtos" role="tab" aria-controls="taxas" aria-selected="false">Pontuações De Produtos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="valor-frete-cidade-tab" data-toggle="tab" href="#valor-frete-cidade" role="tab" aria-controls="taxas" aria-selected="false">Valores de Frete</a>
      </li>
      <li class="nav-item">
        <a
          aria-controls="taxas"
          aria-selected="false"
          class="nav-link"
          data-toggle="tab"
          href="#dias-pagamento-seller"
          id="dias-pagamento-seller-tab"
          role="tab"
        >
          Dias Pagamento
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="porcentagem-comissoes-tab" data-toggle="tab" href="#porcentagem-comissoes" role="tab" aria-controls="taxas" aria-selected="false">Porcentagens e Valores</a>
      </li>
    </ul>
    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active body-novo" id="marketplace" role="tabpanel" aria-labelledby="marketplace-tab">
        <div class="card-menu row">
          <div class="p-10 col-6">
            <h4>Marketplace - emissão de notas fiscais</h4>
            <label for="fornecedor-fisico">Fornecedor pessoa física</label>
            <select name="fornecedor_fisico" id="fornecedor_fisico" class="form-control col-sm-4 input-novo">
              <option value="">-- Pessoa física</option>
              <?php foreach ($fornecedores as $key => $f) { ?>
                <option value="<?= $f['id'] ?>" <?php if ($f['id'] == $configuracoes['fornecedor_mobile_fisico']) {
    echo 'selected';
} ?>><?= $f['razao_social'] ?></option>
              <?php } ?>
            </select><br>
            <label for="fornecedor-juridico">Fornecedor pessoa jurídica</label>
            <select name="fornecedor_juridico" id="fornecedor_juridico" class="form-control col-sm-4 input-novo">
              <option value="">-- Pessoa jurídica</option>
              <?php foreach ($fornecedores as $key => $f) { ?>
                <option value="<?= $f['id'] ?>" <?php if ($f['id'] == $configuracoes['fornecedor_mobile_juridico']) {
    echo 'selected';
} ?>><?= $f['razao_social'] ?></option>
              <?php } ?>
            </select><br>
            <label for="fornecedor-juridico">ID Zoop MobileStock</label>
            <input type="text" id="id_zoop_mobile" class="form-control col-sm-4 input-novo" name="id_zoop_mobile" value="<?= $configuracoes[
                'id_zoop_mobile'
            ] ?>">
            </select>
            <br>

          </div>
          <div class="col-6">
            <template>
              <v-card>
                <v-card-title>
                <v-dialog
                ref="dialog"
                v-model="modalDataNaoTrabalhada"
                :return-value.sync="dataDiaNaoTrabalhado"
                persistent
                width="290px"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="dataDiaNaoTrabalhado"
                    label="Selecione o dia não trabalhado"
                    prepend-icon="mdi-calendar"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="dataDiaNaoTrabalhado"
                  scrollable
                  no-title
                  :events="exibeEventos"
                >
                  <v-spacer></v-spacer>
                  <v-btn
                    text
                    color="primary"
                    @click="modalDataNaoTrabalhada = false"
                  >
                    Cancel
                  </v-btn>
                  <v-btn
                    text
                    color="primary"
                    @click="adicionaDiaNaoTrabalhado"
                    :loading="loadingInsereDiaNaoTrabalhado"
                  >
                    OK
                  </v-btn>
                </v-date-picker>
              </v-dialog>
                </v-card-title>
                <v-data-table
                  :headers="headerDiasNaoTrabalhados"
                  :items="listaDeDiaNaoTrabalhados"
                  :search="pesquisaDiasNaoTrabalhados"
                  :items-per-page="3"
                  class="elevation-1"
                >
                  <template v-slot:item.acao="{ item }">
                    <v-btn
                      dark
                      color="error"
                      @click="removerDiaNaoTrabalhado(item)"
                      :loading="loadingRemoveDiaNaoTrabalhado === item.id"
                    >
                      Remover
                    </v-btn>
                  </template>
                  <template
                    v-slot:no-data
                  >
                    Nenhuma data encontrada
                  </template>
                </v-data-table>
              </v-card>
            </template>
          </div>
        </div>
        <div class="row mx-2">
          <div class="col-sm-3">
            <a href="menu-sistema.php" class="btn btn-danger btn-block text-white">Voltar</a>
          </div>
          <div class="col-sm-6">
          </div>
          <div class="col-sm-3">
            <button type="submit" class="btn btn-primary btn-block">Salvar</button>
          </div>
        </div>
      </div>
      <br>
      <div
        class="tab-pane fade"
        id="regras-fulfillment"
        role="tabpanel"
        aria-labelledby="regras-fulfillment-tab"
    >
        <v-card>
            <v-card-text>
                <div>
                    <h3 class="text-center">Estoque Parado</h3>
                    <br />
                    <div class="d-flex align-center" style="gap: 1rem;">
                        <v-text-field
                            outlined dense
                            hide-details
                            label="Dias para avisar que o produto está parado."
                            type="number"
                            step="any"
                            :loading="carregandoMudarConfiguracoesEstoqueParado"
                            :disabled="carregandoMudarConfiguracoesEstoqueParado"
                            v-model.number="configuracoesEstoqueParado.qtd_maxima_dias"
                        ></v-text-field>
                        <v-text-field
                            outlined dense
                            hide-details
                            label="Dias após o aviso para descontar o preço do produto."
                            type="number"
                            step="any"
                            :loading="carregandoMudarConfiguracoesEstoqueParado"
                            :disabled="carregandoMudarConfiguracoesEstoqueParado"
                            v-model.number="configuracoesEstoqueParado.dias_carencia"
                        ></v-text-field>
                        <v-text-field
                            outlined dense
                            hide-details
                            label="Porcentagem de desconto a ser aplicada."
                            type="number"
                            step="any"
                            :loading="carregandoMudarConfiguracoesEstoqueParado"
                            :disabled="carregandoMudarConfiguracoesEstoqueParado"
                            v-model.number="configuracoesEstoqueParado.percentual_desconto"
                        ></v-text-field>
                        <v-btn
                            color="primary"
                            :loading="carregandoMudarConfiguracoesEstoqueParado"
                            :disabled="carregandoMudarConfiguracoesEstoqueParado || !houveAlteracaoConfiguracoesEstoqueParado"
                            @click="atualizarConfiguracoesEstoqueParado"
                        >Salvar</v-btn>
                    </div>
                </div>
                <hr />
                <div>
                    <h3 class="text-center">Horários para fazer separação fulfillment</h3>
                    <br />
                    <div class="d-flex justify-content-around">
                        <div>
                            <h5>Horários cadastrados:</h5>
                            <v-list>
                                <v-list-item
                                    v-for="(horario, index) in separacaoFulfillment.horarios"
                                    :key="index"
                                >
                                    <v-list-item-content>
                                        <v-list-item-title>{{ horario }}</v-list-item-title>
                                    </v-list-item-content>
                                    <v-list-item-action>
                                        <v-btn
                                            icon
                                            @click="() => separacaoFulfillment.horarios.splice(index, 1)"
                                        >
                                            <v-icon>mdi-delete</v-icon>
                                        </v-btn>
                                    </v-list-item-action>
                                </v-list-item>
                            </v-list>
                        </div>
                        <div class="d-flex flex-column">
                            <v-time-picker
                                color="bg-dark"
                                format="24hr"
                                :disabled="separacaoFulfillment.carregando"
                                :loading="separacaoFulfillment.carregando"
                                v-model="separacaoFulfillment.novoHorario"
                            ></v-time-picker>
                            <br />
                            <v-btn
                                :dark="!desabilitarAdicionarHorarioSeparacao"
                                :disabled="desabilitarAdicionarHorarioSeparacao"
                                :loading="separacaoFulfillment.carregando"
                                @click="adicionarHorarioSeparacao"
                            >Adicionar Novo Horário</v-btn>
                        </div>
                    </div>
                    <br />
                    <v-btn
                        block
                        color="primary"
                        :disabled="!houveAlteracaoHorariosSeparacaoFulfillment || separacaoFulfillment.carregando"
                        :loading="separacaoFulfillment.carregando"
                        @click="salvarHorariosSeparacao"
                    >
                        Salvar Alterações
                    </v-btn>
                </div>
            </v-card-text>
        </v-card>
    </div>

      <div class="tab-pane fade" id="taxas" role="tabpanel" aria-labelledby="taxas-tab">
        <v-card outlined elevation="10" class="mx-2">
          <v-card-title primary-title>
            Taxas
            <v-spacer></v-spacer>
            <v-btn color="warning" @click="adicionaRegra()" class="ma-2">Adicionar Regra</v-btn>
            <v-btn color="primary" @click="salvarConfigTaxas()" class="ma-2">Salvar Regras</v-btn>
          </v-card-title>
          <v-card-text>
            <!-- <v-form id="formulario" enctype="multipart/form-data" ref="form" v-model="valid" lazy-validation> -->
            <v-data-table light :headers="headers" :items="listaTaxas" no-data-text="Nenhum produto localizado" no-results-text="Nenhum dado encontrado" hide-default-footer :items-per-page="-1">
              <template v-slot:body="props">
                <tbody name="slide-fade" is="transition-group">
                  <!-- <template> -->
                  <tr class="table-row" v-for="(item, index) in props.items" :key="item.id">
                    <td class="text-center">
                      <v-text-field outlined dense hide-details v-model="item.numero_de_parcelas" :rules="[rules.required, rules.valorMin(item.numero_de_parcelas,0,'tamanho palmilha')]"></v-text-field>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.juros" :rules="[rules.required, rules.valorMin(item.juros,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.mastercard" :rules="[rules.required, rules.valorMin(item.mastercard,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.visa" :rules="[rules.required, rules.valorMin(item.visa,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.elo" :rules="[rules.required, rules.valorMin(item.elo,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.american_express" :rules="[rules.required, rules.valorMin(item.american_express,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.hiper" :rules="[rules.required, rules.valorMin(item.hiper,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-text-field outlined dense hide-details type="number" step="any" v-model="item.boleto" :rules="[rules.required, rules.valorMin(item.boleto,0,'tamanho palmilha')]"></v-text-field>
                    </td>
                    <td class="text-center">
                      <v-btn color="error" dark small @click="removeRegra(index)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </td>
                  </tr>
                  <!-- </template> -->
                </tbody>
              </template>
            </v-data-table>
            <!-- </v-form> -->
          </v-card-text>
        </v-card>
      </div>

      <div class="tab-pane fade" id="meios-pagamento" role="tabpanel" aria-labelledby="taxas-tab">

        <v-card>
          <v-card-title>
            <h2 class="m-0">Meios de pagamento</h2>
            <v-spacer></v-spacer>
            <v-btn @click="salvaMeiosPagamento" :disabled="!houveAlteracaoMeiosPagamento" color="primary">Salvar</v-btn>
          </v-card-title>
          <v-container>
            <v-row no-gutters>
              <v-col cols="12" lg="3" v-for="(metodosPagamento, index) in listaMeiosPagamento" :key="index" class="mx-2">
                <v-card>
                    <v-card-title class="text-center">
                      {{ metodosPagamento.nome }}
                    </v-card-title>
                    <v-card-text>
                      <v-list dense>
                        <draggable
                          :value="metodosPagamento.meios_pagamento"
                          @input="val => $set(metodosPagamento, 'meios_pagamento', val)"
                          :animation="100"
                        >
                          <template v-for="(meioPagamento, key) in metodosPagamento.meios_pagamento">
                            <v-list-item dense selectable :key="key">
                              <v-list-item-title>{{ key + 1 }}º - {{ meioPagamento.local_pagamento }} | {{ meioPagamento.situacao }}</v-list-item-title>
                              <v-list-item-icon>
                                <v-checkbox dense :input-value="meioPagamento.situacao" false-value="desativado" true-value="ativo" @change="val => $set(meioPagamento, 'situacao', val)"></v-checkbox>
                              </v-list-item-icon>
                              <v-list-item-icon><v-btn icon><v-icon>mdi-drag</v-icon></v-btn></v-list-item-icon>
                            </v-list-item>
                          </template>
                        </draggable>
                      </v-list>
                    </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-container>
        </v-card>
      </div>

      <div class="tab-pane fade" id="percentual-freteiros" role="tabpanel" aria-labelledby="percentual-freteiros-tab">
        <v-card>
          <v-card-title>
            <h3 class="m-0">Configurações Ponto</h3>
          </v-card-title>
          <div class="configuracoes-raio-ponto">
            <div class="valores-configuracoes-ponto">
              <small>Tamanho do raio de ponto parado (mts.)</small>
              <v-text-field
                dense
                outlined
                hide-details
                type="number"
                step="any"
                :disabled="configuracoesFrete.carregando"
                :rules="[rules.valorMin(configuracoesFrete.porcentagemDeCortePontos,0,'')]"
                @change="configuracoesFrete.salvarDisponivel = true"
                v-model="configuracoesFrete.tamanhoRaioPontoParado"
              ></v-text-field>
              <small>Porcentagem de corte de pontos (%)</small>
              <v-text-field
                dense
                outlined
                hide-details
                type="number"
                step="any"
                :disabled="configuracoesFrete.carregando"
                :rules="[rules.valorMin(configuracoesFrete.porcentagemDeCortePontos,0,''), rules.valorMax(configuracoesFrete.porcentagemDeCortePontos,100,'')]"
                @change="configuracoesFrete.salvarDisponivel = true"
                v-model="configuracoesFrete.porcentagemDeCortePontos"
              ></v-text-field>
              <small>Mínimo de entregas para aplicar regra</small>
              <v-text-field
                dense
                outlined
                hide-details
                type="number"
                step="any"
                :disabled="configuracoesFrete.carregando"
                :rules="[rules.valorMin(configuracoesFrete.minimoEntregasParaCorte,1,'')]"
                @change="configuracoesFrete.salvarDisponivel = true"
                v-model="configuracoesFrete.minimoEntregasParaCorte"
              ></v-text-field>
            </div>
            <v-btn
              class="mt-2"
              @click="salvarConfiguracoesFrete"
              :disabled="!configuracoesFrete.salvarDisponivel || configuracoesFrete.carregando"
              :loading="configuracoesFrete.carregando"
              color="primary">Salvar</v-btn>
          </div>
          <hr />
          <v-card-title>
            <h3 class="m-0">Percentual de freteiros</h3>
            <v-spacer></v-spacer>
            <v-btn class="m-1" @click="adicionaNovoPercentual" color="secondary">Adicionar</v-btn>
            <v-btn class="m-1" @click="salvaPercentualFreteiros" :disabled="!houveAlteracaoPercentualFreteiros" color="primary">Salvar</v-btn>
          </v-card-title>
          <v-container>
            <v-row no-gutters>
              <v-data-table
                :headers="headersPercentuaisFreteiros"
                :items="percentuaisFreteiros"
                :hide-default-footer="true"
                disable-pagination
                class="tabela-taxas"
              >
                <template v-slot:item.de="{ item }">
                  <v-edit-dialog
                    :return-value.sync="item.de"
                  >
                    <span>{{ item.de }} Km</span>
                    <template v-slot:input>
                      <v-text-field
                        v-model="item.de"
                        :rules="[rules.valorMax(item, 99999999, 'valor inicial'), rules.valorMin(item, 1, 'valor inicial')]"
                        :disabled="item.de === 0"
                        type="number"
                        single-line
                      ></v-text-field>
                    </template>
                  </v-edit-dialog>
                </template>

                <template v-slot:item.ate="{ item }">
                  <v-edit-dialog
                    :return-value.sync="item.ate"
                  >
                    <v-icon v-if="item.ate === 999999999">mdi-all-inclusive</v-icon>
                    <span v-else>{{ item.ate }} Km</span>
                    <template v-slot:input>
                      <v-text-field
                        v-model="item.ate"
                        :rules="[rules.valorMax(item, 99999999, 'valor inicial'), rules.valorMin(item, 1, 'valor inicial')]"
                        :disabled="item.ate === 999999999"
                        type="number"
                        single-line
                      ></v-text-field>
                    </template>
                  </v-edit-dialog>
                </template>

                <template v-slot:item.porcentagem="{ item }">
                  <v-edit-dialog
                    :return-value.sync="item.porcentagem"
                  >
                    {{ item.porcentagem }}%
                    <template v-slot:input>
                      <v-text-field
                        v-model="item.porcentagem"
                        :rules="[rules.valorMax(item, 99999999, 'valor inicial')]"
                        type="number"
                        single-line
                      ></v-text-field>
                    </template>
                  </v-edit-dialog>
                </template>

                <template v-slot:item.botao="{ item, index }">
                  <v-btn icon :disabled="item.de === 0 || item.ate === 999999999" @click="removePercentualFreteiros(index)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-row>
          </v-container>
        </v-card>
      </div>

      <div class="tab-pane fade" id="fatores-reputacao" role="tabpanel" aria-labelledby="fatores-reputacao-tab">
        <v-card>
          <v-card-title>
            <div class="col row pr-0 justify-content-between">
                <h3>Fatores Reputação</h3>
                <v-btn
                  @click="alteraFatoresReputacao"
                  elevation="2"
                  :loading="reputacaoFornecedor.carregando"
                  :disabled="!houveAlteracaoFatoresReputacao"
                  color="primary"
                >
                  Salvar
                </v-btn>
            </div>
          </v-card-title>
          <v-container>
            <v-data-table
              :headers="reputacaoFornecedor.cabecalho"
              :items="reputacaoFornecedor.dados"
              :items-per-page="100"
            >
              <template v-slot:item.chave="{ item }">
                <v-tooltip top>
                  <template v-slot:activator="{ on, attrs }">
                    <span v-bind="attrs" v-on="on">
                      {{ item.chave }}
                      <v-icon small>mdi-help-circle</v-icon>
                    </span>
                  </template>
                  <span>{{ item.observacao }}</span>
                </v-tooltip>
              </template>
              <template v-slot:item.valor="{ item }">
                <v-text-field
                  v-model="item.valor"
                  type="number"
                  required
                ></v-text-field>
              </template>
            </v-data-table>
          </v-container>
        </v-card>
      </div>

      <div class="tab-pane fade" id="pontuacoes-produtos" role="tabpanel" aria-labelledby="percentual-freteiros-tab">
        <v-card>
          <v-card-title>
            <div class="col row pr-0 justify-content-between">
                <h3>Pontuações</h3>
                <v-btn
                  @click="alteraValoresPontuacoesProdutos"
                  elevation="2"
                  :loading="pontuacao.carregando"
                  :disabled="!houveAlteracaoPontuacaoProdutos"
                  color="primary"
                >
                  Salvar
                </v-btn>
            </div>
          </v-card-title>
          <v-container>
            <v-data-table
              :headers="pontuacao.cabecalho"
              :items="pontuacao.dados"
              :items-per-page="100"
            >
              <template v-slot:item.chave="{ item }">
                <v-tooltip top>
                  <template v-slot:activator="{ on, attrs }">
                    <span v-bind="attrs" v-on="on">
                      {{ item.chave }}
                      <v-icon small>mdi-help-circle</v-icon>
                    </span>
                  </template>
                  <span>{{ item.observacao }}</span>
                </v-tooltip>
              </template>
              <template v-slot:item.valor="{ item }">
                <v-text-field
                  v-model="item.valor"
                  type="number"
                  required
                ></v-text-field>
              </template>
            </v-data-table>
          </v-container>
        </v-card>
      </div>

      <div class="tab-pane fade" id="valor-frete-cidade" role="tabpanel" aria-labelledby="valor-frete-cidade">
        <v-card>
          <v-card-title>
            <div class="col row pr-0 justify-content-between">
              <h3>Valor de Frete por Cidades</h3>
              <v-btn
                @click="alteraValoresFretePorCidade"
                elevation="2"
                :loading="valoresFreteCidade.carregando"
                :disabled="!houveAlteracaoValoresFreteCidade"
                color="primary"
              >
                Salvar
              </v-btn>
            </div>
          </v-card-title>
          <v-container>
            <v-select
                label="Estado"
                name="estado"
                :items="estados"
                :loading="valoresFreteCidade.carregando"
                :value="`MG`"
                @change="buscaValoresFreteCidade"
            ></v-select>
            <v-data-table
                :disabled="valoresFreteCidade.carregando"
                :headers="valoresFreteCidade.cabecalho"
                :items="valoresFreteCidade.dados"
                :items-per-page="100"
                :loading="valoresFreteCidade.carregando"
                :search="pesquisa"
            >
              <template v-slot:item.valor_frete="{ item }">
                <v-text-field
                  v-model="item.valor_frete"
                  type="number"
                  required
                ></v-text-field>
              </template>
              <template v-slot:top>
                <div class="mx-2">
                    <v-text-field
                        dense
                        outlined
                        append-icon="mdi-magnify"
                        label="Pesquisar"
                        v-model="pesquisa"
                    ></v-text-field>
                </div>
              </template>
              <template v-slot:item.valor_adicional="{ item }">
                <v-text-field
                  v-model="item.valor_adicional"
                  type="number"
                  required
                ></v-text-field>
              </template>

              <template v-slot:item.id_colaborador_ponto_coleta="{ item }">
                <div v-show="!item.editando">
                    <span v-if="valoresFreteCidade.dadosIniciais?.find((cidade) => cidade.id === item.id)?.id_colaborador_ponto_coleta !== item.id_colaborador_ponto_coleta">
                        {{ item.razao_social }}
                        <br />
                        <span class="badge badge-warning">NÃO SALVO!</span>
                    </span>
                    <span v-else>{{ item.razao_social }}</span>
                    <v-icon @click="() => item.editando = true">mdi-pencil</v-icon>
                </div>
                <v-autocomplete
                    v-show="item.editando"
                    :items="valoresFreteCidade.listaColaboradoresFreteExpresso"
                    :loading="valoresFreteCidade.carregandoBuscaColaboradoresFreteExpresso"
                    :search-input="item.buscarColaboradorFreteExpresso"
                    @update:search-input="valor => buscarColaboradoresParaFreteExpresso(valor)"
                    hide-no-data
                    hide-selected
                    item-text="nome"
                    item-value="id"
                    label="Procure um colaborador"
                    @input="(novoValor) => mudouPontoColetaCidade(item, novoValor)"
                    prepend-icon="mdi-magnify"
                    no-filter
                    return-object
                ></v-autocomplete>
            </template>

              <template v-slot:item.dias_entregar_cliente="{ item }">
                <v-text-field
                  v-model="item.dias_entregar_cliente"
                  type="number"
                  required
                ></v-text-field>
              </template>
            </v-data-table>
          </v-container>
        </v-card>
      </div>

      <div class="tab-pane fade" id="dias-pagamento-seller" role="tabpanel" aria-labelledby="dias-pagamento-seller-tab">
        <v-form @submit.prevent="atualizaDiasPagamentoColaboradores">
          <v-card :loading="loadingDiasTransferenciaSeller" :disabled="loadingDiasTransferenciaSeller">
            <v-card-title>
              <v-card-title>Dias para Pagamento</v-card-title>
            </v-card-title>
            <div class="p-4 pt-0 pb-0">
                <v-row class="justify-center">
                  <v-col cols="1">
                     <v-text-field
                        required
                        label="Antecipação"
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_antecipacao"
                        name="dias_pagamento_transferencia_antecipacao"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        required
                        label="Entregadores"
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_ENTREGADOR"
                        name="dias_pagamento_transferencia_ENTREGADOR"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Melhores Fabricantes"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE"
                        name="dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Excelentes"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_EXCELENTE"
                        name="dias_pagamento_transferencia_fornecedor_EXCELENTE"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Regulares"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_REGULAR"
                        name="dias_pagamento_transferencia_fornecedor_REGULAR"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Ruins"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_RUIM"
                        name="dias_pagamento_transferencia_fornecedor_RUIM"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Novatos"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_fornecedor_NOVATO"
                        name="dias_pagamento_transferencia_fornecedor_NOVATO"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                  <v-col cols="1">
                      <v-text-field
                        label="Clientes"
                        required
                        type="number"
                        v-model="diasTransferenciaSeller.dias_pagamento_transferencia_CLIENTE"
                        name="dias_pagamento_transferencia_CLIENTE"
                        @input="botaoSalvarDiasPagamentoSeller = false"
                      ></v-text-field>
                  </v-col>
                </v-row>
            </div>

            <v-footer class="justify-end">
              <v-card-actions>
                <v-btn
                  color="primary"
                  type="submit"
                  :disabled="botaoSalvarDiasPagamentoSeller"
                >
                  Salvar
                </v-btn>
              </v-card-actions>
            </v-footer>
          </v-card>
        </v-form>
      </div>

      <div class="tab-pane fade" id="porcentagem-comissoes" role="tabpanel" aria-labelledby="porcentagem-comissoes">
        <v-card>
          <v-card-title class="d-flex">
            <div class="ml-10 mb-auto">
                <h3>Porcentagem Comissões por Produto</h3>
                <v-form @submit.prevent="alteraPorcentagemComissoes">
                  <v-text-field
                      v-model="porcentagemComissoes.porcentagem_comissao_ml"
                      label="Porcentagem Comissão Meu Look"
                      outlined
                      :disabled="loadingPorcentagemComissoes"
                      :loading="loadingPorcentagemComissoes"
                      type="text"
                  ></v-text-field>
                  <v-text-field
                      v-model="porcentagemComissoes.porcentagem_comissao_ms"
                      label="Porcentagem Comissão Mobile Stock"
                      outlined
                      :disabled="loadingPorcentagemComissoes"
                      :loading="loadingPorcentagemComissoes"
                      type="text"
                  ></v-text-field>
                  <v-text-field
                    v-model="porcentagemComissoes.porcentagem_comissao_ponto_coleta"
                    label="Porcentagem Comissão Ponto de Coleta"
                    outlined
                    :disabled="loadingPorcentagemComissoes"
                    :loading="loadingPorcentagemComissoes"
                    type="text"
                    ></v-text-field>
                    <v-btn type="submit" color="success" :disabled="loadingPorcentagemComissoes">Salvar</v-btn>
                </v-form>
            </div>
            <div class="ml-10 mb-auto">
              <h3>Valor Mínimo Fraude Devoluções</h3>
                <v-form @submit.prevent="alteraValorMinimoFraude">
                  <v-text-field
                    v-model="valorMinimoFraude"
                    label="Valor mínimo fraude devoluções"
                    outlined
                    :disabled="loadingValorMinimoFraude"
                    :loading="loadingValorMinimoFraude"
                    type="number"
                  ></v-text-field>
                  <v-btn type="submit" color="success" :disabled="loadingValorMinimoFraude">Salvar</v-btn>
                  </v-form>
            </div>
            <div class="ml-10 mb-auto">
                <h3>Porcentagem antecipação</h3>
                <v-form @submit.prevent="alteraPorcentagemAntecipacao" class="mt-auto">
                  <v-text-field
                    v-model="porcentagemAntecipacao"
                    label="Porcentagem Antecipação"
                    outlined
                    :disabled="loadingPorcentagemAntecipacao"
                    :loading="loadingPorcentagemAntecipacao"
                    type="number"
                  ></v-text-field>
                  <v-btn type="submit" color="success" :disabled="loadingPorcentagemAntecipacao">Salvar</v-btn>
                </v-form>
            </div>
            <div class="ml-10 mb-auto">
                <h3>Taxa Para Produto Errado</h3>
                <v-form @submit.prevent="alterarTaxaProdutoErrado" class="mt-auto">
                  <v-text-field
                    v-model="taxaDevolucaoProdutoErrado"
                    label="Taxa Para Produto Errado"
                    outlined
                    :disabled="loadingtaxaDevolucaoProdutoErrado"
                    :loading="loadingtaxaDevolucaoProdutoErrado"
                    type="text"
                  ></v-text-field>
                  <v-btn type="submit" color="success" :disabled="loadingtaxaDevolucaoProdutoErrado">Salvar</v-btn>
                </v-form>
            </div>
            <div class="ml-10 mb-auto">
                <h3>Porcentagem Bloqueio Fornecedor</h3>
                <v-form @submit.prevent="alteraTaxaBloqueioFornecedor" class="mt-auto">
                    <v-text-field
                    v-model="taxaBloqueioFornecedor"
                    label="Porcentagem Bloqueio Fornecedor"
                    outlined
                    :disabled="loadingTaxaBloqueioFornecedor"
                    :loading="loadingTaxaBloqueioFornecedor"
                    type="number"
                ></v-text-field>
                <v-btn type="submit" color="success" :disabled="loadingTaxaBloqueioFornecedor">Salvar</v-btn>
                </v-form>
            </div>
            <div class="ml-10 mb-auto">
                <h3>Porcentagem Comissões por Transação</h3>
                <v-form @submit.prevent="atualizaPorcentagemComissoesTransacao" class="mt-auto">
                    <v-text-field
                        v-model="porcentagemComissoes.comissao_direito_coleta"
                        label="Porcentagem Comissão Direito de Coleta"
                        outlined
                        :disabled="loadingPorcentagemComissoes"
                        :loading="loadingPorcentagemComissoes"
                        type="text"
                    >
                    </v-text-field>
                    <v-btn type="submit" color="success" :disabled="loadingPorcentagemComissoes">Salvar</v-btn>
                </v-form>
            </div>
          </v-card-title>
        </v-card>
      </div>

    </div>
  </form>
  <!-- OVERLAY -->
  <template>
    <div class="text-center">
      <v-overlay :value="overlay">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
      </v-overlay>
    </div>
  </template>

  <template>
    <div class="text-center">
      <v-snackbar v-model="snackbar.open" timeout="2000" :color="snackbar.color" dark>
        {{snackbar.mensagem}}

        <template v-slot:action="{ attrs }">
          <v-btn dark text v-bind="attrs" @click="snackbar.open = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </template>
      </v-snackbar>
    </div>
  </template>
  <!-- </div> -->
</v-app>

<style scoped>
  .slide-fade-enter-active {
    transition: all .3s ease;
  }

  .slide-fade-leave-active {
    transition: all .8s cubic-bezier(1.0, 0.5, 0.8, 1.0);
  }

  .slide-fade-enter,
  .slide-fade-leave-to

  /* .slide-fade-leave-active below version 2.1.8 */
    {
    transform: translateX(10px);
    opacity: 0;
  }

  .table-row {
    display: table-row;
  }

  .component-fade-enter-active,
  .component-fade-leave-active {
    transition: opacity .3s ease;
  }

  .flip-list-move {
    transition: transform 0.5s;
  }
  .no-move {
    transition: transform 0s;
  }
  .ghost {
    opacity: 0.5;
    background: #c8ebfb;
  }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDOUF6qgqqszs+iXU4UA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.24.3/vuedraggable.umd.js" integrity="sha512-MPl1xjL9tTTJHmaWWTewqTJcNxl2pecJ0D0dAFHmeQo8of+F9uF7zb2bazCX7m45K3mKRg44L1xJDeFzjmjRtA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/configuracoes.js<?= $versao ?>"></script>
<?php require_once 'rodape.php'; ?>
