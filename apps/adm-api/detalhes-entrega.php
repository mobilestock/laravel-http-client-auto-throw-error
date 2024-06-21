<?php

require_once __DIR__ . '/cabecalho.php';
acessoUsuarioVendedor();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<style>
    .v-application p {
        margin-bottom: 0.1rem !important;
    }
</style>
<input type="hidden" id="id-entrega" value="<?= $_GET['id'] ?>" />

<div class="container-fluid" id="app">
    <v-app>
        <v-card>
            <v-card-title class="h3">
                Detalhes da Entrega&ensp;<b>#{{ id_entrega }}</b>
            </v-card-title>
            <div class="row">
                <div class="pl-4 infos col-4">
                    <p>Data: <b>{{ detalhes_entrega.data_criacao }}</b></p>
                    <p>Destino: <b>{{ detalhes_entrega.destino }}</b></p>
                    <p>Volumes: <b>{{ detalhes_entrega.volumes }}</b></p>
                    <p>Situação: <b>{{ detalhes_entrega.situacao }}</b></p>
                    <p>Valor Total da Entrega: <b>{{ calculaValorEmReais(detalhes_entrega.valor_total) }}</b></p>
                    <p v-if="!!detalhes_entrega.data_expedicao">Data Expedição: <b>{{ detalhes_entrega.data_expedicao }}</b></p>
                    <p v-if="!!detalhes_entrega.usuario_expedicao">Usuário Expedição: <b>{{ detalhes_entrega.usuario_expedicao }}</b></p>
                    <br />
                    <v-btn :loading="loading" @click="buscaInformacoesParaEntregador()">
                        Relatório da entrega
                        &ensp;
                        <v-icon>mdi-eye</v-icon>
                    </v-btn>
                </div>
                <div class="col-6">
                    <div v-if="detalhes_entrega.tipo_ponto == 'PM'">
                        <p>Entregador:</p>
                        <p v-cloak><b>{{ detalhes_entrega.nome_ponto }}</b></p>
                        <br/>
                        <v-btn
                            v-if="['Aberto', 'Na expedição'].includes(detalhes_entrega.situacao)"
                            @click="ENTREGADOR_exibirModalMudarEntregador = true"
                        >
                            Mudar entregador
                        </v-btn>
                    </div>
                </div>
                <div class="col-2 d-flex justify-content-center">
                    <v-btn color="primary" elevation="2" @click="exibirEtiquetasVolume = true">Etiquetas</v-btn>
                </div>
                <div class="col-4 d-flex ml-auto" v-if="detalhes_entrega.tem_devolucao_pendente">
                    <span class="error--text text-uppercase">
                        <b>Este cliente tem devoluções pendentes.</b>
                        <br>
                        <b>Por isso não é possível forçar entrega.</b>
                    </span>
                </div>
            </div>
            <br />
            <b><p class="pl-4">Produtos da entrega</p></b>
            <v-data-table
                :loading="loading"
                :headers="lista_produtos_headers"
                :items="lista_produtos"
                :footer-props="{'items-per-page-options': [50, 100, 200, -1]}"
                :items-per-page="50">
                <template v-slot:item.id_transacao="{ item }">
                    <a target="_blank" :href="'transacao-detalhe.php?id=' + item.id_transacao">
                        {{ item.id_transacao }} <v-icon style="font-size: 1rem; color: gray;">mdi-link-variant</v-icon>
                    </a>
                </template>
                <template v-slot:item.razao_social="{ item }">
                    {{ item.razao_social }} ({{ item.id_cliente }})
                    <br />
                    <span class="badge badge-warning">Dest: {{ item.nome_destinatario }}</span>
                </template>
                <template v-slot:item.origem="{ item }">
                    {{ item.origem }}
                </template>
                <template v-slot:item.preco="{ item }">
                    {{ calculaValorEmReais(item.preco) }}
                </template>
                <template v-slot:item.descricao="{ item }">
                    <i><a target="_blank" :href="'fornecedores-produtos.php?id=' + item.id_produto">{{ item.id_produto }}</a></i> - {{ item.descricao }}
                </template>
                <template v-slot:item.historico_logistica="{ item }">
                    <p v-if="item.historico_logistica">{{ item.historico_logistica.usuario }}<br /><small>{{ item.historico_logistica.data_adicionado }}</small></p>
                    <p v-else>Sem registro</p>
                </template>
                <template v-slot:item.recebedor="{ item }">
                    <span v-if="item.recebedor.nome_recebedor">
                        <small>{{ item.recebedor.nome_recebedor }}</small><br/>
                        <small>{{ item.recebedor.data_entrega }}</small>
                    </span>
                </template>
                <template v-slot:item.qrcode="{ item }">
                    <v-btn @click="qrcodeProduto = item">
                    <v-icon style="font-size: 1rem; color: gray;">mdi-qrcode</v-icon>
                    </v-btn>
                </template>
                <template v-slot:item.entrega="{ item }">
                    <v-chip
                        dark
                        color="red darken-2"
                        v-if="item.saldo_cliente < 0"
                    >SALDO NEGATIVO
                    </v-chip>
                    <v-btn
                        block
                        dark
                        small
                        color="green darken-2"
                        v-else-if="item.situacao_entrega !== 'EN' && !detalhes_entrega.tem_devolucao_pendente"
                        @click="produtoForcarEntrega = item"
                    >Forçar Entrega
                    </v-btn>
                </template>
                <template v-slot:item.troca="{ item }">
                    <v-chip
                        dark
                        color="deep-orange darken-4"
                        v-if="item.situacao_entrega === 'EN' && ids_produtos_frete.includes(item.id_produto)"
                    >ETIQUETA DE FRETE</v-chip>
                    <v-btn
                        block
                        small
                        color="warning"
                        v-else-if="item.situacao_entrega === 'EN' && !item.ja_estornado"
                        @click="produtoForcarTroca = item"
                    >Forçar troca</v-btn>
                </template>
            </v-data-table>
        </v-card>

        <!-- Modal Forçar Entrega -->
        <v-dialog
            max-width="25rem"
            :persistent="carregandoForcarEntrega"
            :value="produtoForcarEntrega !== null"
        >
            <v-card>
                <v-card-title>
                    Forçar Entrega
                    <v-spacer></v-spacer>
                    <v-btn icon @click="produtoForcarEntrega = null">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    Tem certeza que deseja forçar a entrega desse produto?
                    <br />
                    <b>Essa ação não poderá ser desfeita.</b>
                </v-card-text>
                <hr />
                <v-card-actions class="d-flex flex-row-reverse">
                    <v-btn
                        :disable="carregandoForcarEntrega"
                        :loading="carregandoForcarEntrega"
                        @click="produtoForcarEntrega = null"
                    >Não</v-btn>
                    <v-btn
                        color="error"
                        :disable="carregandoForcarEntrega"
                        :loading="carregandoForcarEntrega"
                        @click="forcarEntrega"
                    >Sim</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog
            max-width="400px"
            :value="produtoForcarTroca !== null"
            @input="val => val || (produtoForcarTroca = null)"
            :persistent="produtoForcarTroca?.loading"
        >
            <v-card>
                <v-card-title>
                    Forçar troca
                    <v-spacer></v-spacer>
                    <v-btn icon @click="produtoForcarTroca = null">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    Tem certeza que deseja forçar a troca desse produto? <br>
                    <b>Essa ação não poderá ser desfeita.</b>
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions>
                    <div class="w-100 d-flex flex-row-reverse">
                        <v-btn @click="produtoForcarTroca = null" :disabled="carregandoForcarTroca">Não</v-btn>
                        <v-btn
                            :loading="carregandoForcarTroca"
                            :disabled="carregandoForcarTroca"
                            color="error"
                            @click="forcarTroca"
                        >
                            Sim
                        </v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Modal para exibir o QRCode dos produtos -->
        <v-dialog
            max-width="35em"
            v-model="exibirQrcodeProduto"
            @click:outside="qrcodeProduto = null"
        >
            <v-card>
                <v-card-title>
                    QRCode do produto
                    <v-spacer></v-spacer>
                    <v-btn icon @click="qrcodeProduto = null">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="text-center">
                    <p><b>{{ qrcodeProduto?.razao_social }}</b></p>
                    <p>{{ qrcodeProduto?.descricao }} - {{ qrcodeProduto?.nome_tamanho }}</p>
                    <img class="img-fluid" :src="qrcodeProduto?.qrcode" :alt="qrcodeProduto?.razao_social" />
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Modal de relatório da entrega -->
        <v-dialog v-model="detalhes_relatorio_aberto" persistent>
            <v-card v-show="detalhes_relatorio_aberto">
                <div id="imprimivel">
                    <v-toolbar dark>
                        <v-toolbar-title>
                            <div class="div_relatorio">
                                Relatório da entrega: {{ id_entrega }}
                            </div>
                            <div class="entregador_relatorio">
                                Entregador: {{ detalhes_entrega.nome_ponto }}
                            </div>
                        </v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-banner
                            color="red"
                            single-line
                            dark
                            shaped
                            icon="mdi-information"
                            class="pr-5"
                        >
                            Esta tela só possui informações do destinatário!
                        </v-banner>
                        <v-btn icon @click="detalhes_relatorio_aberto = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-toolbar>
                    <br />
                    <v-card-text>
                        <v-data-table
                            disable-filtering
                            disable-pagination
                            disable-sort
                            hide-default-footer
                            :headers="detalhes_relatorio_headers"
                            :items="detalhes_relatorio"
                        >
                            <template v-slot:item.tem_troca="{ item }">
                                <div class="d-flex justify-content-center">
                                    <v-checkbox
                                        disabled
                                        v-model="item.tem_troca"
                                    ></v-checkbox>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </div>
                <v-card-action class="d-flex pb-2 pr-2 justify-content-end">
                    <v-btn
                        class="mr-1"
                        color="error"
                        @click="detalhes_relatorio_aberto = false"
                    >
                        Fechar
                    </v-btn>
                    <v-btn
                        dark
                        class="ml-1"
                        color="grey darken-4"
                        @click="imprimirRelatorio"
                    >
                        Imprimir <v-icon>mdi-printer</v-icon>
                    </v-btn>
                </v-card-action>
            </v-card>
        </v-dialog>

        <!-- Dialog para exibir o QRCode da entrega -->
        <v-dialog
            max-width="40em"
            v-model="exibirEtiquetasVolume"
        >
            <v-card>
                <v-card-title>
                    <div v-for="(etiqueta, index) in lista_etiquetas" :key="etiqueta">
                        <v-btn
                            class="m-1"
                            color="primary"
                            elevation="2"
                            @click="mostrarEtiquetaVolume(etiqueta, index+1)">Volume {{ index+1 }}</v-btn>
                    </div>
                    <v-spacer></v-spacer>
                </v-card-title>
                <v-card-text class="text-center">
                    <h3 v-if="imagemEtiquetaVolume?.id"><b>Volume {{ imagemEtiquetaVolume?.id }}</b></h3>
                    <img class="img-fluid" :src="imagemEtiquetaVolume?.qrcode" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="exibirEtiquetasVolume = false">Fechar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog para mudar o entregador -->
        <v-dialog
            max-width="40em"
            v-model="ENTREGADOR_exibirModalMudarEntregador"
            persistent
        >
            <v-card>
                <v-card-title>
                    <h3>Alterar o entregador</h3>
                </v-card-title>
                <v-card-text class="text-center">
                    <div class="col col-12">
                        <v-autocomplete
                            label="Busque por nome do entregador"
                            :items="ENTREGADOR_listaPontosEncontrados"
                            :search-input.sync="ENTREGADOR_buscarPontosPorNome"
                            :disabled="ENTREGADOR_loadingAlterarEntregador || ENTREGADOR_loadingSalvarEntregador"
                            :loading="ENTREGADOR_loadingAlterarEntregador"
                            v-model="ENTREGADOR_itemSelecionado"
                            @change="selecionaPonto"
                            @keyup.lazy="ENTREGADOR_buscarPontosPorNome"
                        >
                        </v-autocomplete>
                    </div>
                <p v-if="ENTREGADOR_novoTipoFrete">
                    Você tem certeza que deseja alterar o entregador da entrega {{ id_entrega }}
                    para <b>{{ ENTREGADOR_novoTipoFrete.nome }}</b>?
                </p>
                <br/>
                <div class="alert alert-danger" role="alert" v-if="ENTREGADOR_mensagemAlerta">
                    {{ ENTREGADOR_mensagemAlerta }}
                </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        v-if="ENTREGADOR_novoTipoFrete"
                        color="error"
                        elevation="2"
                        :loading="ENTREGADOR_loadingSalvarEntregador"
                        :disabled="ENTREGADOR_loadingSalvarEntregador"
                        @click="alterarEntregador(id_entrega)"
                    >
                        Salvar
                    </v-btn>
                    <v-btn
                        color="primary"
                        elevation="2"
                        :disabled="ENTREGADOR_loadingSalvarEntregador"
                        @click="limparDadosEntregador"
                    >
                        Cancelar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Novo alerta para a tela -->
        <v-snackbar
            :color="snackbar.cor"
            v-model="snackbar.ativar"
        >
            {{ snackbar.texto }}
        </v-snackbar>
    </v-app>
</div>

<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script type="module" src="js/detalhes-entrega.js"></script>
<script src="js/printThis.js"></script>
