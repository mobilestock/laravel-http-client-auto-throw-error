<?php

require_once 'cabecalho.php';

acessoUsuarioFornecedor();
?>
<style>
    #image-container {
        max-width: 100vw;
    }

    #image-container>img {
        width: 100%;
    }
</style>
<input type="hidden" id="gera_mensagem" value="<?php //($mensagem['id'] ? 1 : 0) 
                                                ?>">
<div class="modal fade bd-example-modal-lg" id="modal-gera-mensagem" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalLabel5" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">

            </div>
            <div class="modal-body">
                <img class="rounded mx-auto d-block w-50" src="images/mensagem-create.png" />-->
                <p id="image-container" class=" text-justify">
                    <?= ($mensagem['mensagem'] ? $mensagem['mensagem'] : "") ?>
                </p>
            </div>
            <div class="modal-footer justify-content-center btn-group">
                <input type="checkbox" id="li-concordo" name="li-concordo">-->
                <input type="hidden" name="id_colaborador" value=<?php //($mensagem['mensagem'] ? $id_fornecedor : "") 
                                                                    ?>>
                <button value="<?= $mensagem['id'] ?>" name="li-concordo" id="li-concordo" type="button" class="btn btn-block btn-danger">Estou ciente</button>

            </div>
        </div>
    </div>
</div>
<v-app class="container-fluid" id="comprasVue">
    <v-card color="light-blue darken-2" dark class="pa-4 mb-4">
        <!--filtros -->
        <v-row>
            <v-col cols="12" class="hidden-sm-and-down">
                <v-card-title class="py-6">
                    <h1 class="font-weight-bold display-2">{{fornecedor ? 'Lista de Pedidos' : 'Reposições'}}</h1>
                </v-card-title>
                <v-card-subtitle class="ml-4">{{ new Date().toLocaleString('pt-br',{dateStyle: 'full'}) }}</v-card-subtitle>
            </v-col>
            <v-col cols="6" sm="2">
                <v-text-field v-model="filtros.id" label="Número" outlined dense></v-text-field>
            </v-col>
            <v-col cols="6" sm="4" v-show="!fornecedor">
                <v-autocomplete v-model="filtros.fornecedor" :items="selectFornecedor" :search-input.sync="buscaFornecedor" cache-items outlined dense label="Nome do Fornecedor" item-text="nome" item-value="id" clearable></v-autocomplete>
            </v-col>
            <v-col cols="6" sm="3">
                <v-menu ref="menu" v-model="menu" :close-on-content-click="false" :return-value.sync="datesEmissao" transition="scale-transition" offset-y min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field outlined dense hide-details v-model="dateRangeText" label="Data Emissão:" prepend-inner-icon="event" readonly v-on="on" append-icon="mdi-close" @click:append.stop.prevent="filtros.data_inicial_emissao = filtros.data_fim_emissao = ''; datesEmissao = []"></v-text-field>
                    </template>
                    <v-date-picker v-model="datesEmissao" color="primary" no-title scrollable range :first-day-of-week="1" locale="pt-br">
                        <v-spacer></v-spacer>
                        <v-btn text color="primary" @click="menu = false">Cancel</v-btn>
                        <v-btn text color="primary" @click="$refs.menu.save(datesEmissao)">OK</v-btn>
                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="6" sm="3">
                <v-menu ref="menu2" v-model="menu2" :close-on-content-click="false" :return-value.sync="datesPrevisao" transition="scale-transition" offset-y min-width="290px">
                    <template v-slot:activator="{ on }">
                        <v-text-field outlined dense hide-details v-model="dateRangeTextPrevisao" label="Data Previsão:" prepend-inner-icon="event" readonly v-on="on" append-icon="mdi-close" @click:append.stop.prevent="filtros.data_inicial_previsao = filtros.data_fim_previsao = ''; datesPrevisao = []"></v-text-field>
                    </template>
                    <v-date-picker v-model="datesPrevisao" color="primary" no-title scrollable range :first-day-of-week="1" locale="pt-br">
                        <v-spacer></v-spacer>
                        <v-btn text color="primary" @click="menu2 = false">Cancel</v-btn>
                        <v-btn text color="primary" @click="$refs.menu2.save(datesPrevisao)">OK</v-btn>
                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="6" sm="2">
                <v-text-field v-model="filtros.tamanho" label="Tamanho" outlined dense></v-text-field>
            </v-col>
            <v-col cols="6" sm="6" md="4">
                <v-text-field outlined dense label="Referência" v-model="filtros.referencia"></v-text-field>
            </v-col>
            <v-col cols="6" sm="6" md="3">
                <v-select :items="listaSituacoes" label="Situação" item-text="situacao" item-value="id" v-model="filtros.situacao" outlined dense></v-select>
            </v-col>
            <v-col cols="6" sm="3" class="text-right">
                <v-btn color="orange" @click="buscaListaCompras(true)">Pesquisar <v-icon right>mdi-magnify</v-icon>
                </v-btn>
            </v-col>
        </v-row>
    </v-card>
    <v-card>
        <v-row>
            <v-col cols="12">
                <v-card-title primary-title>
                    <v-btn color="error" :href=" fornecedor ? 'dashboard-fornecedores.php' : 'menu-sistema.php'">Voltar</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn dark color="green" href="compras-cadastra.php">Cadastrar</v-btn>
                </v-card-title>
                <v-card-text>
                    <v-data-table :headers="headers" :items="listaCompras" :options.sync="options" :server-items-length="itemsPorPagina" :loading="loading" class="elevation-1" no-data-text="Nenhum registro" no-results-text="Nenhum dado encontrado" loading-text="Buscando dados">
                        <template v-slot:item="{ item }">
                            <tr>
                                <td class="text-start">{{item.id}}</td>
                                <td class="text-center">{{item.fornecedor}}</td>
                                <td class="text-center">{{item.situacao_nome}}</td>
                                <td class="text-center">{{item.valor_total | moneyMask}}</td>
                                <td class="text-center">{{converteData(item.data_emissao)}}</td>
                                <td class="text-center">{{converteData(item.data_previsao)}}</td>
                                <td class="text-center">
                                    <form method="POST" action="compras-cadastra.php">
                                        <input type="hidden" name="id" :value="item.id">
                                        <v-btn dark small :color="item.situacao == 2 ? 'green' : 'warning'" type="submit">
                                            <v-icon>{{item.situacao == 2 ?'fas fa-eye' : 'mdi-pencil'  }}</v-icon>
                                        </v-btn>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <v-btn small :disabled="fornecedor" color="primary" type="submit" @click="buscaCodigoBarrasCompra(item.id)">
                                        <v-icon>mdi-magnify</v-icon>
                                    </v-btn>
                                </td>

                            </tr>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-col>
        </v-row>
    </v-card>

    <v-dialog v-model="dialog" max-width="500px" scrollable style="z-index: 2000;">
        <v-card>
            <v-toolbar dark color="error">
                <v-toolbar-title>Lista de Produtos ({{listaCodigoBarras.length}})</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn dark icon tile @click="dialog = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-card-text>
                <v-list three-line dense>
                    <template v-for="(item, index) in listaCodigoBarras">
                        <v-list-item :key="item.title">
                            <template v-slot:default="{ active, toggle }">
                                <v-list-item-content>
                                    <v-list-item-title :class="item.saldo >= 0 ? 'green--text' : 'red--text'">
                                    <a :href="'fornecedores-produtos.php?id=' + item.id_produto" target="_blank" rel="noopener noreferrer">{{ item.desc_produto}}</a>
                                    </v-list-item-title>
                                    <v-list-item-subtitle class="text--primary">Situação: {{ item.situacao == 2 ? 'Entregue' : 'Em Aberto'}}</v-list-item-subtitle>
                                    <v-list-item-subtitle>Cod: {{ item.codigo_barras }}</v-list-item-subtitle>
                                    <v-list-item-subtitle>Quantidade: {{item.quantidade}}</v-list-item-subtitle>
                                    <!-- <v-list-item-subtitle>
                                        <template v-if="item.caminho === '' && item.situacao == 1 ">
                                            <v-autocomplete dense filled @input="atualizaNumeracaoProdutosIguais(item.id_produto, item.tamanhoParaFoto)" v-model="item.tamanhoParaFoto" :rules="[(v) => !!v || 'Preencha as cores']" chips deletable-chips small-chips hide-details label="Informe tamanho para foto" :items="item.grade_autocomplete" item-value="tamanho" item-text="nome_tamanho" :search-input.sync="valorTamanhoDescrito" @input="valorTamanhoDescrito = ''" clearable>
                                        </template>
                                    </v-list-item-subtitle> -->
                                </v-list-item-content>
                                <v-list-item-action class="no-print-this">
                                    <v-list-item-action-text>Baixar</v-list-item-action-text>
                                    <v-btn color="primary" :disabled="item.situacao == 2" icon tile @click="baixarItemCompra(item.tamanhoParaFoto, item, index)">
                                        <v-icon>fas fa-arrow-down</v-icon>
                                    </v-btn>
                                </v-list-item-action>
                            </template>
                        </v-list-item>

                        <v-divider v-if="index + 1 < listaCodigoBarras.length" :key="index"></v-divider>
                    </template>
                </v-list>
            </v-card-text>
        </v-card>
    </v-dialog>

    <v-overlay :value="overlay" style="z-index: 3000;">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
    </v-overlay>

    <v-snackbar v-model="snackbar.open" timeout="3000" :color="snackbar.color" style="z-index: 2200">
        {{ snackbar.text }}

        <template v-slot:action="{ attrs }">
            <v-btn color="white" icon v-bind="attrs" @click="snackbar.open = false" tile>
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </template>
    </v-snackbar>
</v-app>

<script>
    // if ($('#gera_mensagem').val() != 0) {
    //     $('#modal-gera-mensagem').modal('show');
    // }
    // $('#li-concordo').click(function() {

    //     var mensagem = $(this).val();
    //     var cliente = $('[name=id_colaborador]').val();
    //     $.ajax({
    //         type: "POST",
    //         dataType: "json",
    //         url: 'src/controller/ClienteMensagensController.php',
    //         data: {
    //             mensagem: mensagem,
    //             cliente: cliente,
    //             action: "AtualizaLeituraMensagem"
    //         },
    //         success: function(data) {
    //             $('#modal-gera-mensagem').modal('hide');
    //             console.log("ok");
    //         },
    //         error: function() {

    //         }
    //     }).done(function(data) {



    //     });
    // });
</script>
<script src="js/MobileStockApi.js"></script>
<script src="js/compras.js<?= $versao; ?>"></script>