<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />
    <meta charset="UTF-8" />
</head>

<v-app id="grupoEntregas">

    <header class="d-flex flex-column justify-content-center align-center m-2 mt-5">
        <h1>Grupos de Entregas</h1>
        <v-row class="w-100">
            <div class="col-4 d-flex justify-center">
                <v-btn
                @click="abrirDialogNovoGrupoEntregas"
                >
                    <v-icon>mdi-plus-circle</v-icon>
                    &nbsp;Criar novo grupo
                </v-btn>
            </div>
            <div class="col-4 d-flex justify-center">
                <v-btn @click="dialogImprimirEtiquetaMobile = true">Selecionar etiquetas de clientes</v-btn>
            </div>
            <div class="col-4 d-flex justify-center">
                <v-btn
                    @click="dialogBotoesEtiquetas = true"
                >
                    Escolher tipos de etiquetas
                    &nbsp;<v-icon>mdi-printer</v-icon>
                </v-btn>
            </div>
        </v-row>
    </header>

    <v-row class="justify-content-around p-4">
        <v-card class="col-sm-2 m-2 mb-5 p-0" v-for="dia in diasSemana" :key="dia.value">
            <v-card-title class="font-weight-bold justify-center">{{ dia.text }}</v-card-title>
            <v-text-field
                v-model="dia.busca"
                append-icon="mdi-magnify"
                label="Pesquisar"
                single-line
                hide-details
                class="elevation-1 m-2 p-2"
            ></v-text-field>
            <v-data-table
                :headers="grupoEntregasHeader"
                :items="grupoEntregas.filter(item => item.dia_fechamento === dia.value)"
                class="elevation-1 m-2 "
                :loading="loadingModalEntregas"
                :mobile-breakpoint="2560"
                :search="dia.busca"
                :items-per-page="5"
            >

                <template v-slot:item.ativo="{ item }">
                    <span :class="retornaClasseStatus(item.ativado)">{{ item.ativado ? 'Ativo' : 'Inativo' }}</span>
                </template>

                <template v-slot:item.actions="{ item }">
                <v-tooltip top>
                        <template v-slot:activator="{ on, hover }">
                        <v-btn
                                small
                                icon
                                color="var(--cor-secundaria-meulook)"
                                v-bind="hover"
                                v-on="on"
                                @click="listaEntregaPeloGrupo(item)"
                                :loading="loadingListaEntregas"
                            >
                                <v-icon>mdi-eye</v-icon>
                            </v-btn>
                        </template>
                        <span>Acompanhar Grupo</span>
                    </v-tooltip>
                    <v-tooltip top>
                        <template v-slot:activator="{ on, hover }">
                            <v-btn
                                small
                                icon
                                color="primary"
                                v-bind="hover"
                                v-on="on"
                                @click="buscarDetalhesGrupoTipoFrete(item)"
                                :loading="loadingEditarGrupo == item.id"
                                :disabled="disabledEditarGrupo"
                            >
                                <v-icon>mdi-pencil</v-icon>
                            </v-btn>
                        </template>
                        <span>Editar</span>
                    </v-tooltip>
                    <v-tooltip top>
                        <template v-slot:activator="{ on, hover }">
                            <v-btn
                                :color="item.ativado ? 'amber' : 'success'"
                                :loading="loadingAtivarGrupo == item.id"
                                :disabled="disabledAtivarGrupo"
                                @click="mudarSituacaoGrupo(item)"
                                v-bind="hover"
                                v-on="on"
                                small
                                icon
                            >
                                <v-icon>{{ item.ativado ? 'mdi-close-box' : 'mdi-checkbox-marked' }}</v-icon>
                            </v-btn>
                        </template>
                        <span>{{ item.ativado ? 'Desativar' : 'Ativar' }}</span>
                    </v-tooltip>
                    <v-tooltip top>
                        <template v-slot:activator="{ on, hover }">
                            <v-btn
                                small
                                icon
                                color="error"
                                v-bind="hover"
                                v-on="on"
                                :loading="loadingApagarGrupo == item.id"
                                :disabled="disabledApagarGrupo"
                                @click="abrirDialogApagarGrupoEntregas(item)"
                            >
                                <v-icon>mdi-delete-circle</v-icon>
                            </v-btn>
                        </template>
                        <span>Excluir</span>
                    </v-tooltip>
                </template>
            </v-data-table>
        </v-card>
    </v-row>




    <!-- Dialog para adicionar ou editar grupo de entregas -->
    <v-dialog
      v-model="dialogNovoGrupoEntregas"
      transition="dialog-bottom-transition"
      persistent
      width="100%"
      max-width="45rem"
    >
      <v-card :loading="loadingNovoGrupoEntregas">
        <v-card-title class="text-h5 grey lighten-2 d-flex justify-content-between">
          <span v-if="dialogGrupoEntregasTipo === 'novo'">Novo grupo de entregas</span>
            <span v-if="dialogGrupoEntregasTipo === 'editar'">Editar grupo de entregas</span>
            <v-btn
                small
                icon
                @click="dialogNovoGrupoEntregas = false"
                :disabled="disabledDialogGrupoEntregas"
            >
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>

            <v-form>
                <v-container>
                    <v-row>
                        <v-col cols="9">
                            <v-text-field
                                label="Nome do Grupo"
                                required
                                v-model="nomeGrupoEntregas"
                                :disabled="disabledDialogGrupoEntregas"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="3">
                            <v-select
                                label="Dia da Semana"
                                v-model="diaFechamentoGrupoEntregas"
                                :items="diasSemana"
                                :disabled="disabledDialogGrupoEntregas"
                            ></v-select>
                        </v-col>
                    </v-row>
                    <v-divider></v-divider>
                    <v-row>
                        <v-col cols="12">
                            <v-card-text>
                                Selecione os pontos que farão parte deste grupo de entregas. Quando uma entrega pertencente ao grupo for fechada, as entregas em aberto dos pontos selecionados poderão ser fechadas automaticamente.
                            </v-card-text>
                            <v-row>
                                <div class="col col-12 col-md-3">
                                    <v-autocomplete
                                        label="Busque por ID"
                                        :items="listaPontosEncontrados"
                                        :search-input.sync="buscarPontosPorIdColaborador"
                                        :disabled="disabledDialogGrupoEntregas || !!buscarPontosPorNome"
                                        :loading="loadingBuscandoPontosPorIdColaborador"
                                        type="number"
                                        @change="selecionaPonto"
                                        @keyup.lazy="buscarPontosPorIdColaborador"
                                    >
                                    </v-autocomplete>
                                </div>
                                <div class="col col-12 col-md-9">
                                    <v-autocomplete
                                        label="Busque por nome do ponto ou entregador"
                                        :items="listaPontosEncontrados"
                                        :search-input.sync="buscarPontosPorNome"
                                        :disabled="disabledDialogGrupoEntregas || !!buscarPontosPorIdColaborador"
                                        :loading="loadingBuscandoPontosPorNome"
                                        @change="selecionaPonto"
                                        @keyup.lazy="buscarPontosPorNome"
                                    >
                                    </v-autocomplete>
                                </div>
                            </v-row>
                        </v-col>
                    </v-row>
                    <div class="listaPontos">
                        <div v-if="listaPontosSelecionados" v-for="item in listaPontosSelecionados">
                            <v-list-item>
                                <v-list-item-content>
                                    <v-list-item-title class="title" v-cloak>
                                        {{ item.text }}
                                        <small v-cloak v-if="item.adicionados > 0" class="badge badge-info">
                                            {{ item.adicionados }} {{ item.adicionados > 1 ? 'grupos' : 'grupo' }}
                                        </small>
                                    </v-list-item-title>
                                    <v-list-item-title class="title" v-cloak v-if="item.adicionados > 0"></v-list-item-title>
                                </v-list-item-content>
                                <v-list-item-action>
                                    <v-btn
                                        @click="removePonto(item.value)"
                                        :disabled="disabledDialogGrupoEntregas"
                                    >
                                        Remover
                                    </v-btn>
                                </v-list-item-action>
                            </v-list-item>
                        </div>
                    </div>
                </v-container>
            </v-form>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            text
            @click="dialogGrupoEntregasTipo === 'novo' ? salvarGrupoEntregas() : editarGrupoEntregas()"
            :disabled="disabledDialogGrupoEntregas || nomeGrupoEntregas == '' || listaPontosSelecionados.length == 0"
          >
            Salvar
          </v-btn>
        </v-card-actions>

      </v-card>
    </v-dialog>

    <!-- Dialog confirmação para apagar grupo de entregas -->
    <v-dialog
      v-model="dialogApagarGrupoEntregas"
      transition="dialog-bottom-transition"
      persistent
      width="100%"
      max-width="35rem"
    >
      <v-card :loading="loadingApagarGrupo">
        <v-card-title class="text-white text-h5 red lighten-1">
          <span>Apagar grupo de entregas <b v-cloak>{{ dialogApagarGrupoEntregasItem.nome_grupo }}</b>?</span>
        </v-card-title>

        <v-card-text class="mt-5 pb-0">
            <p>Tem certeza que deseja apagar o grupo de entregas <b>{{ dialogApagarGrupoEntregasItem.nome_grupo }}</b>?</p>
            <p>Esta ação não poderá ser desfeita.</p>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="d-flex justify-content-between">
          <v-btn
            color="primary"
            text
            @click="dialogApagarGrupoEntregas = ''"
            :disabled="disabledApagarGrupo"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="error"
            text
            @click="apagarGrupoEntregas(dialogApagarGrupoEntregasItem)"
            :disabled="disabledApagarGrupo"
          >
            Apagar
          </v-btn>
        </v-card-actions>

      </v-card>
    </v-dialog>

    <!-- Dialog para escolher qual forma das etiquetas irá imprimir -->
    <v-dialog
      v-model="dialogImprimirEtiquetaMobile"
      transition="dialog-bottom-transition"
      persistent
      width="100%"
      max-width="35rem"
    >
      <v-card>
        <v-card-title class="text-white text-h5 blue lighten-1 d-flex justify-content-between">
          <span>Imprimir Etiquetas de Separação</span>
            <v-btn
                small
                icon
                @click="fecharModalImprimirEtiquetas"
            >
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </v-card-title>

        <v-card-text class="mt-5 pb-0">
            <p>Como você gostaria que as etiquetas fossem geradas?</p>
        </v-card-text>

        <v-divider class="mx-4"></v-divider>

        <div v-if="listaClientesParaImprimir.length > 0" class="scroll">
            <div class="d-flex justify-content-around m-2">
                <v-btn small @click="desmarcarTodasEtiquetas">Desmarcar Tudo</v-btn>
                <v-btn small @click="marcarTodasEtiquetas">Marcar Tudo</v-btn>
            </div>
            <v-list-item v-for="(item, index) in listaClientesParaImprimir" v-bind:key="index">
                <v-list-item-action>
                <v-checkbox
                    :input-value="checkboxImprimirMobile.find(itemImprimir => itemImprimir.id_cliente === item.id_cliente)"
                    @click="adicionarItemParaImprimirEtiquetaMobile(item)"
                ></v-checkbox>
                </v-list-item-action>
                <v-list-item-content>
                    <v-list-item-title class="title">
                       ({{ item.id_cliente }}) {{ item.cliente }}
                    </v-list-item-title>
                    <small>({{ item.qtd_item }}
                        <span v-if="item.qtd_item > 1">produtos</span>
                        <span v-else>produto</span>)
                    </small>
                    <p>{{ item.data_criacao }}</p>
                </v-list-item-content>
            </v-list-item>
        </div>
        <div v-else>
            <div class="align-center d-flex flex-column justify-center">
                <div class="d-flex flex-column mb-5 w-100">
                    <v-btn
                        class="m-3 mb-2"
                        :loading="loadingImprimeEtiquetas"
                        :disabled="loadingImprimeEtiquetas"
                        @click="listarEtiquetasSeparacaoCliente('TODAS')"
                    >
                        Todas as etiquetas
                    </v-btn>
                    <small class="text-center">Imprima todas as etiquetas de produtos internos que estão liberados para separação</small>
                </div>
                <div class="d-flex flex-column mb-5 w-100">
                    <v-btn
                        class="m-3 mb-2"
                        :loading="loadingImprimeEtiquetas"
                        :disabled="loadingImprimeEtiquetas"
                        @click="listarEtiquetasSeparacaoCliente('PRONTAS')"
                    >
                        Etiquetas sem logística pendente
                    </v-btn>
                    <small class="text-center">Imprima somente etiquetas de clientes MobileStock para retirada ou transportadora e que não possuam logística externa pendentes</small>
                </div>
                <div class="d-flex flex-column mb-5 w-100">
                    <v-btn
                        class="m-3 mb-2"
                        :loading="loadingImprimeEtiquetas"
                        :disabled="loadingImprimeEtiquetas"
                        @click="listarEtiquetasSeparacaoCliente('COLETAS')"
                    >
                        Etiquetas com Coleta
                    </v-btn>
                    <small class="text-center">Imprimir etiquetas dos produtos que estão com coleta</small>
                </div>
            </div>
        </div>

        <v-divider v-if="listaClientesParaImprimir.length > 0"></v-divider>

        <v-card-actions class="d-flex justify-space-between" v-if="listaClientesParaImprimir.length > 0">
            <v-btn
                color="primary"
                text
                :disabled="loadingImprimeEtiquetas"
                @click="limparInformacoesModalImprimir"
            >
                Voltar
            </v-btn>
            <v-btn
                :loading="loadingImprimeEtiquetas"
                :disabled="loadingImprimeEtiquetas || checkboxImprimirMobile.length === 0"
                @click="imprimeEtiquetasSeparacaoCliente"
                >
                <v-icon>mdi-printer</v-icon>
                &nbsp;Imprimir
            </v-btn>
        </v-card-actions>

      </v-card>
    </v-dialog>

    <!-- Dialog para fechar entregas que pertencem ao grupo -->
    <v-dialog
      v-model="dialogListaDestinosDoGrupo"
      transition="dialog-bottom-transition"
      width="100%"
      max-width="35rem"
      persistent
    >
      <v-card>
        <v-card-title class="text-white text-h5 blue lighten-1 d-flex justify-content-between">
          <span>{{idGrupoEntregas}} - {{nomeGrupoEntregas}}</span>
        </v-card-title>

        <v-card-text class="mt-5 pb-0 font-weight-bold">
            <p>
                Esse grupo conta com os seguintes destinos, que podem ser acompanhados juntos:
            </p>
        </v-card-text>
        <template>
            <v-data-table
                :headers="listaEntregasHeader"
                :items="listaEntregas"
                :items-per-page="7"
                class="justify-center"
            >

                <template v-slot:item.nome="{item}">
                    <span>{{ item.nome }}</span>
                    <div
                        v-if="item.destinos"
                        class="mb-2"
                    >
                        <small
                            class="badge badge-info mr-2 font-weight-lighter"
                            v-for="destino in item.destinos"
                        >
                            {{destino.apelido}} <span v-if="destino.apelido">|</span> {{destino.cidade}}
                        </small>
                    </div>
                </template>

            </v-data-table>
        </template>
        <v-card-actions class="d-flex justify-content-between">
          <v-btn
            color="primary"
            @click="fecharDialogListaDestinosDoGrupo"
            :disabled="loadingAcompanharDestinos"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="error"
            @click="acompanharDestinosDoGrupo"
            :loading="loadingAcompanharDestinos"
            :disabled="loadingAcompanharDestinos"
          >
            Confirmar
          </v-btn>
        </v-card-actions>
</v-dialog>

    <!-- Modal botões etiquetas -->
    <v-dialog height="22.5rem" v-model="dialogBotoesEtiquetas">
        <v-card height="22.5rem">
            <v-toolbar dark color="blue">
                <v-toolbar-title>Tipos de Etiquetas</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon @click="dialogBotoesEtiquetas = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <br />
            <div class="containerTiposEtiquetas">
                <div>
                    <h4 class="text-center">Retirada na Central/Transportadora</h4>
                    <div class="d-flex justify-content-center">
                        <v-radio-group
                            column
                            mandatory
                            v-model="classificacoesEtiquetas.retiradaCentralTransportadora"
                        >
                            <v-radio label="Todos" value="TODAS"></v-radio>
                            <v-radio label="Prontos" value="PRONTAS"></v-radio>
                        </v-radio-group>
                    </div>
                </div>
                <div class="d-flex justify-center">
                    <v-divider vertical></v-divider>
                </div>
                <div>
                    <h4 class="text-center">Pontos de Retirada/Entregadores</h4>
                    <div class="align-center d-flex flex-column">
                        <v-radio-group
                            column
                            mandatory
                            v-model="classificacoesEtiquetas.pontosRetiradaEntregadores"
                        >
                            <v-radio label="Todos" value="TODAS"></v-radio>
                            <v-radio label="Dia específico" value="DIA"></v-radio>
                        </v-radio-group>
                        <div class="w-50">
                            <v-select
                                clearable
                                solo
                                return-object
                                label="Dia da Semana"
                                prepend-inner-icon="mdi-calendar"
                                :items="diasSemana"
                                v-if="classificacoesEtiquetas.pontosRetiradaEntregadores === 'DIA'"
                                v-model="classificacoesEtiquetas.pontosRetiradaEntregadoresDia"
                            ></v-select>
                        </div>
                    </div>
                </div>
            </div>
            <v-card-actions class="justify-content-center">
                <v-btn
                    dark
                    :disable="loadingImprimeEtiquetas"
                    :loading="loadingImprimeEtiquetas"
                    @click="imprimeEtiquetasSeparacao"
                >Imprimir</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-snackbar
        :color="snackbar.cor"
        v-cloak
        v-model="snackbar.mostrar"
    >
        {{ snackbar.texto }}
    </v-snackbar>

</v-app>

<style>
    div.listaPontos {
        overflow-y: scroll;
        height: 10em;
    }
    tr td {
        min-height: inherit !important;
        height: 2.5rem !important;
    }
    div.scroll {
        overflow-x: hidden;
        overflow-y: scroll;
        height: 30rem;
    }
    .containerTiposEtiquetas {
        display: grid;
        gap: 0.5rem;
        grid-template-columns: 1fr 0.01fr 1fr;
        grid-template-rows: 13.75rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script src="js/grupos-de-entregas.js<?= $versao ?>" type="module"></script>
