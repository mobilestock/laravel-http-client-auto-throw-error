<?php
require_once 'cabecalho.php';

acessoUsuarioFornecedor();
$str = $_POST['id'] ?? ($_GET['id'] ?? '');
?>
<v-app class="container-fluid" id="comprasVue">
    <input id="id_compra" type="hidden" value="<?= $str ?>">
    <input id="idFornecedor" type="hidden" value="<?= $_POST['idFornecedor'] ?? $_SESSION['id_cliente'] ?>">
    <v-card color="light-blue darken-2" dark class="pa-4 mb-4">
        <!--filtros -->
        <v-row>
            <v-col cols="12" sm="6" class="hidden-sm-and-down">
                <v-card-title class="py-6">
                    <h1 class="font-weight-bold display-2">Pedidos e Reposições</h1>
                </v-card-title>
                <v-card-subtitle class="ml-4">{{ new Date().toLocaleString('pt-br',{dateStyle: 'full'}) }}</v-card-subtitle>
                <v-card-subtitle class="ml-4 h4">Número do Pedido: {{ filtros.id }}</v-card-subtitle>
            </v-col>
            <v-col cols="12" sm="6" class="d-flex align-items-center justify-content-end">
                <v-autocomplete v-model="filtros.fornecedor" :items="selectFornecedor" :search-input.sync="buscaFornecedor" cache-items outlined dense label="Nome do Fornecedor" item-text="nome" item-value="id" hide-no-data style="max-width: 500px;" v-show="!fornecedor && !filtros.id"></v-autocomplete>
            </v-col>
            <v-col cols="12" sm="12" class="d-flex flex-wrap" :class="$vuetify.breakpoint.smAndDown ? 'flex-column' : ''">
                <v-btn class="ma-2" color="error" href="compras.php">Voltar</v-btn>
                <v-spacer></v-spacer>
                <v-btn :disabled="!edicao_fornecedor" class="ma-2" color="green" :href="'relatorios/compras-relatorio-compra.php?id='+filtros.id" v-show="filtros.id">Visualizar Relatório</v-btn>
                <v-btn :disabled="!edicao_fornecedor" class="ma-2" color="teal" :href="'relatorios/compras-lista-etiquetas.php?id='+filtros.id" v-show="!fornecedor && filtros.id">Visualizar Etiquetas</v-btn>
                <v-btn :disabled="!edicao_fornecedor" class="ma-2" color="deep-purple darken-1" id="baixar-etiquetas-coletivas" @click="buscaEtiquetasColetivas()" v-show="filtros.id">Baixar Etiquetas Coletivas</v-btn>
                <v-btn :disabled="!edicao_fornecedor" class="ma-2" color="indigo" id="baixar-etiquetas-unitarias" @click="buscaEtiquetasUnitarias()" v-show="filtros.id">Baixar Etiquetas Unitárias</v-btn>
            </v-col>
        </v-row>
    </v-card>
    <v-dialog v-model="modalConsignado" persistent max-width="430">
        <v-card>
            <v-card-title class="h6">
                Produto Fulfillment
            </v-card-title>
            <v-card-text>
                Este produto não pode ser adicionado na compra.
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="#616161" dark @click="modalConsignado = false">
                    Ok
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
    <!-- <v-alert type="error" prominent class="mt-2" v-show="possuiIncompleto && fornecedor">
        <v-row align="center" no-gutters>
            <v-col cols="10" class="d-flex flex-column">
                <span>Caro fornecedor, você possuí produtos com cadastro incompleto.</span>
                <span> Esses produtos podem ser identificados pela borda vermelha e pelo símbolo
                    <v-icon right small dark class="align-baseline">
                        fas fa-exclamation-circle
                    </v-icon>.
                </span>
            </v-col>
            <v-col cols="2">
                <v-btn light href="fornecedores-produtos.php">Regularizar</v-btn>
            </v-col>
        </v-row>
    </v-alert> -->
    <v-row no-gutters>
        <v-col cols="12" sm="5" :class="$vuetify.breakpoint.smAndUp ? 'pr-2' : ''">
            <!--Lista De Produtos -->
            <v-card :disabled="edicao_fornecedor">
                <v-toolbar color="primary" dark>
                    <v-toolbar-title>Lista de Produtos</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-text-field v-model="pesquisaProdutos"  append-icon="search" label="Filtrar" single-line hide-details style="max-width: 300px;"></v-text-field>
                </v-toolbar>
                <v-data-table
                    class="elevation-1"
                    no-data-text="Nenhum produto localizado"
                    no-results-text="Nenhum dado encontrado"
                    :headers="headersTabelaDemanda"
                    :items="listaProdutosDemanda"
                    :sort-by="['id']"
                    :sort-desc="true"
                >
                    <template v-slot:item="{ item, index }">
                        <tr>
                            <td class="text-center p-0"><a target="_blank" :href="'fornecedores-produtos.php?id=' + item.id">{{item.id}}</a></td>
                            <td @click="visualizarFoto(item)" class="text-center p-0"><img contain :src="item.caminho ? item.caminho : 'images/img-placeholder.png'" height="70px"></td>
                            <td class="text-center p-0">
                                <div>
                                    <div>{{item.descricao}} {{ item.cores }}</div>
                                    <small>{{ item.nome_comercial }}</small>
                                </div>
                            </td>

                            <td class="p-0" v-if="item.children.length > 0">
                                <table class="table text-center my-1">
                                    <tbody>
                                        <tr class="d-flex justify-content-center">
                                            <td v-for="grade in item.children" :key="grade.key" class="d-flex flex-column table-bordered p-1">
                                                <b>{{grade.nome_tamanho}}</b>
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <span :class=" grade.total < 0 ? 'text-red' : ''" v-bind="attrs" v-on="on">{{grade.total}}</span>
                                                    </template>
                                                    <span>Produtos negativos significam que existem clientes aguardando este produto.</span>
                                                </v-tooltip>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td class="p-0 text-center">
                                <v-tooltip top v-if="item.incompleto == 1 && fornecedor">
                                    <template v-slot:activator=" { on, attrs }">
                                        <v-btn small color="error" v-bind="attrs" @click="cadastroIncompleto()" v-on="on">
                                            <v-icon small>fas fa-exclamation-circle</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Produto com cadastro incompleto. É necessário completar seu cadastro para adicioná-lo ao pedido.</span>
                                </v-tooltip>

                                <v-tooltip top>
                                    <template v-slot:activator=" { on, attrs }">
                                        <v-btn small color="primary" v-bind="attrs" v-on="on" @click="adicionarProduto(item,index)" :disabled="filtros.situacao > 1 ">
                                            <v-icon small>fas fa-shopping-cart</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Adicionar o produto a compra</span>
                                </v-tooltip>
                            </td>
                        </tr>
                    </template>
                </v-data-table>
            </v-card>
        </v-col>
        <v-col cols="12" sm="7" :class="$vuetify.breakpoint.smAndUp ? 'pl-2' : 'mt-4'">
            <!--Lista De Acicionados -->
            <v-card ref="produtosAdicionados">
                <v-toolbar :disabled="edicao_fornecedor" :color="listaProdutosAdicionados.length > 0 ? 'green' : 'primary'" dark class="justify-content-between">
                    <v-toolbar-title>Produtos Adicionados ({{listaProdutosAdicionados.length}})</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-text-field :disabled="edicao_fornecedor" v-model="filtroTabelaAdicionados" append-icon="search" label="Filtrar" single-line hide-details style="max-width: 300px;"></v-text-field>
                </v-toolbar>
                <v-card-title>
                    <!-- <div class="d-flex justify-content-around align-items-center"> -->
                    <div class="d-flex">
                        <v-icon :disabled="edicao_fornecedor" color="warning">fas fa-shopping-cart</v-icon>
                        <v-subheader>Total: {{totalCompra.toLocaleString("pt-BR", { style: "currency" , currency:"BRL"})}}</v-subheader>
                    </div>
                    <v-spacer></v-spacer>
                    <v-menu ref="dataEmisao" :disabled="edicao_fornecedor" v-model="menu2" :close-on-content-click="false" :nudge-right="40" transition="scale-transition" offset-y min-width="290px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-text-field :disabled="edicao_fornecedor" :error="erroData" outlined dense hide-details v-model="textPrevisao" label="Data Previsão:" prepend-icon="event" readonly v-bind="attrs" v-on="on" append-icon="mdi-close" @click:append.stop.prevent="filtros.data_previsao = '';" style="max-width: 300px;" class="px-4"></v-text-field>
                        </template>
                        <v-date-picker :disabled="edicao_fornecedor" v-model="filtros.data_previsao" @input="menu2 = false" :first-day-of-week="1" locale="pt-br"></v-date-picker>
                    </v-menu>
                    <v-btn
                        color="green accent-4"
                        class="text-white"
                        :disabled=" filtros.situacao == 2 || listaProdutosAdicionados.length == 0 || !filtros.data_previsao || edicao_fornecedor || !filtros.id"
                        @click="dialogConcluirReposicao = true"
                    >Concluir Reposição</v-btn>
                    <!-- </div> -->
                </v-card-title>
                <v-data-table :headers="headerTabelaAdicionados" :search="filtroTabelaAdicionados" class="elevation-1" :footer-props="{
                            'items-per-page-options': [10, 20, 30, 40, -1],
                            itemsPerPageText: 'Itens por página',
                            'items-per-page-all-text': 'Todos'
                        }" :items-per-page="10" no-data-text="Nenhum produto adicionado" no-results-text="Nenhum dado adicionado" :items="listaProdutosAdicionados">
                    <template v-slot:item="{ item, index }">
                        <tr>
                            <td :disabled="edicao_fornecedor" class="text-center p-1">{{item.id}}</td>
                            <td :disabled="edicao_fornecedor" @click="visualizarFoto(item)" class="text-center p-1"><img contain :src="item.caminho ? item.caminho : 'images/img-placeholder.png'" height="70px"></td>
                            <!-- <td class="text-center">{{item.descricao}}</td> -->
                            <td :disabled="edicao_fornecedor" class="text-center p-0" v-if="item.children">
                                <table class="table text-center my-1">
                                    <tbody>
                                        <tr class="d-flex justify-content-center">
                                            <td v-for=" (grade,index) in item.children" :key="grade.key" class="d-flex flex-column table-bordered p-1">
                                                <b>{{grade.nome_tamanho}}</b>
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <span :class=" item.inputsGrade.novaGrade[index].quantidade > 0 ? 'text-blue' : ''" v-bind="attrs" v-on="on">{{item.inputsGrade.novaGrade[index].quantidade}}</span>
                                                    </template>
                                                    <span>Quantidade de pares adicionados.</span>
                                                </v-tooltip>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0"><small>{{item.inputsGrade.caixas}}</small></td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0"><small>{{item.quantidadeTotal}}</small></td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0"><small>{{parseFloat(item.valorTotal).toLocaleString("pt-BR", { style: "currency" , currency:"BRL"})}}</small></td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0"><small>{{item.situacao ? item.situacao.nome : 'Em aberto'}}</small></td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0">
                                <v-btn icon color="warning" :disabled="edicao_fornecedor || item.situacao && item.situacao.situacao != 1" @click="produto = item ; dialogDetalhesProduto = true">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                            </td>
                            <td :disabled="edicao_fornecedor" class="text-center p-0">
                                <v-btn
                                    icon
                                    color="error"
                                    :disabled="item.situacao && item.situacao.situacao != 1 || dialogExcluirProduto || loading"
                                    @click="queroExcluirProduto(item,index)"
                                >
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </td>
                        </tr>
                    </template>
                </v-data-table>
            </v-card>
        </v-col>
    </v-row>

    <!-- MODAL FOTO -->
    <v-dialog v-model="modalFoto.open" max-width="500" style="z-index: 2000;" scrollable>
        <v-card>
            <v-toolbar color="primary" dark>
                <v-toolbar-title>{{modalFoto.cardTitle}}</v-toolbar-title>
            </v-toolbar>

            <v-card-text class="d-flex justify-content-center">
                <img contain :src="modalFoto.cardPath ? modalFoto.cardPath : 'images/img-placeholder.png'" style="max-width: 400px;">
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" text @click="modalFoto.open = false">
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDetalhesProduto" scrollable transition="dialog-bottom-transition" @click:outside="limpaModal" style="z-index: 2000" max-width="1200px">
        <v-card>
            <v-toolbar dark color="primary" class="mb-2">
                <v-toolbar-title>Detalhes do Produto</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon dark @click="dialogDetalhesProduto = false; limpaModal()">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-card-text>
                <v-row>
                    <v-col cols="12">
                        <v-card outlined>
                            <v-simple-table>
                                <template v-slot:default>
                                    <thead>
                                        <tr>
                                            <th class="text-center"></th>
                                            <th class="text-center">Descrição</th>
                                            <th class="text-center">Grade</th>
                                            <th class="text-center">Valor Unitário</th>
                                            <th class="text-center">Quantidade Total</th>
                                            <th class="text-center">Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td @click="visualizarFoto(produto)" class="text-center" style="max-width: 100px;"><img contain :src="produto.caminho ? produto.caminho : 'images/img-placeholder.png'" style="max-width: 100px;"></td>
                                            <td class="text-center">{{produto.descricao}}</td>
                                            <td class="text-center" v-if="produto.hasOwnProperty('children') && produto.children.length > 0">
                                                <table class="table text-center my-1">
                                                    <tbody>
                                                        <tr class="d-flex justify-content-center">
                                                            <td style="width: 100px;" class="d-flex flex-column table-bordered">
                                                                <small class="mb-2">Grade</small>
                                                                <small class="py-1">Saldo Atual</small>
                                                                <small class="py-1">Quantidade Adquirida</small>
                                                                <small class="py-1">Novo Saldo</small>
                                                            </td>
                                                            <td v-for=" (grade,index) in produto.children" :key="grade.key" class="d-flex flex-column table-bordered justify-content-between">
                                                                <b class="mb-1">{{grade.nome_tamanho}}</b>
                                                                <v-tooltip top>
                                                                    <template v-slot:activator="{ on, attrs }">
                                                                        <span class="py-1" :class=" grade.total < 0 ? 'text-red' : ''" v-bind="attrs" v-on="on">{{grade.total}}</span>
                                                                    </template>
                                                                    <span>Produtos negativos significam que existem clientes aguardando este produto.</span>
                                                                </v-tooltip>
                                                                <v-tooltip top>
                                                                    <template v-slot:activator="{ on, attrs }">
                                                                        <span class="py-1 text-blue" v-bind="attrs" v-on="on">{{ inputsGrade.novaGrade[index].quantidade ? inputsGrade.novaGrade[index].quantidade * inputsGrade.caixas : '0'}}</span>
                                                                    </template>
                                                                    <span>Quantidade de pares adicionados nesta compra</span>
                                                                </v-tooltip>
                                                                <v-tooltip top v-if="novaGrade[index]">
                                                                    <template v-slot:activator="{ on, attrs }">
                                                                        <span class="py-1" :class=" novaGrade[index].total == 0 ? 'text-blue' : (novaGrade[index].total > 0 ? 'text-green' : 'text-red') " v-bind="attrs" v-on="on">{{novaGrade[index].total}}</span>
                                                                    </template>
                                                                    <span>Saldo de pares após a compra</span>
                                                                </v-tooltip>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <v-tooltip top>
                                                    <template v-slot:activator="{ on, attrs }">
                                                        <v-btn x-small class="mb-1" color="primary" @click.stop.prevent="dialogDetalhesGrade = true" v-bind="attrs" v-on="on">Detalhes</v-btn>
                                                    </template>
                                                    <span>Detalhes do estoque.</span>
                                                </v-tooltip>
                                            </td>
                                            <td class="text-center">{{ converteReais(produto.valor_custo_produto)}}</td>
                                            <td class="text-center">{{ quantidadeTotal}}</td>
                                            <td class="text-center">{{ converteReais(valorTotal) }}</td>
                                        </tr>
                                    </tbody>
                                </template>
                            </v-simple-table>
                        </v-card>
                    </v-col>
                    <v-col cols="12">
                        <v-card outlined v-if="produto.hasOwnProperty('children') && produto.children.length > 0">
                            <v-form id="formulario" enctype="multipart/form-data" ref="form" v-model="valid">
                                <v-row no-gutters>
                                    <v-col cols="12" class="d-flex align-baseline">
                                        <v-card-title>
                                            Grade
                                        </v-card-title>
                                        <v-spacer></v-spacer>
                                        <v-text-field class="px-2" label="Caixas" outlined dense style="max-width: 150px;" type="number" v-model="inputsGrade.caixas" :rules="[rules.required, rules.valorMin(inputsGrade.caixas,1,'caixas')]"></v-text-field>
                                    </v-col>
                                    <v-col v-if="produto.children.length > 0" v-for="(grade, index) in produto.children" :key="index" class="d-flex flex-column text-center px-2">
                                        <span class="subtitle-1 mb-1">{{grade.nome_tamanho}}</span>
                                        <v-text-field :value="inputsGrade.novaGrade[index].quantidade" @input="value => calcula(value, index)" label="Quantidade" outlined dense type="number" :rules="[rules.required, rules.valorMin(inputsGrade.novaGrade[index].quantidade ?? 0,0,'nome_tamanho')]"></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-form>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    :disabled="loading"
                    :loading="loading"
                    @click="salvarCompra()"
                >Salvar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDetalhesGrade" max-width="500px">
        <v-card v-if="dialogDetalhesGrade">
            <v-card-title class="headline">Detalhes do Estoque</v-card-title>

            <v-card-text>
                <table class="table text-center my-1" v-if="dialogDetalhesGrade">
                    <tbody>
                        <tr class="d-flex justify-content-center">
                            <td style="width: 100px;" class="d-flex flex-column table-bordered">
                                <small class="mb-2">Grade</small>
                                <small class="py-2">Estoque Disponível</small>
                                <small class="py-2">Reservados</small>
                                <small class="py-2">Reposição Prevista</small>
                                <small class="py-2">Total</small>
                            </td>
                            <td v-for=" (grade,index) in produto.children" :key="grade.key" class="d-flex flex-column table-bordered justify-content-between">
                                <b class="mb-1">{{grade.nome_tamanho}}</b>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span class="py-2" :class=" grade.estoque < 0 ? 'text-red' : (grade.estoque > 0 ? 'text-blue' : '')" v-bind="attrs" v-on="on">{{grade.estoque}}</span>
                                    </template>
                                    <span>Saldo em nosso estoque.</span>
                                </v-tooltip>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span class="py-2" :class=" grade.reservados < 0 ? 'text-red' : (grade.reservados > 0 ? 'text-blue' : '')" v-bind="attrs" v-on="on">{{grade.reservados}}</span>
                                    </template>
                                    <span>Quantidade repares reservados.</span>
                                </v-tooltip>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span class="py-2" :class=" grade.previsao < 0 ? 'text-red' : (grade.previsao > 0 ? 'text-blue' : '')" v-bind="attrs" v-on="on">{{grade.previsao ? grade.previsao : 0}}</span>
                                    </template>
                                    <span>Quantidade de pares comprados aguardando entrega.</span>
                                </v-tooltip>
                                <span class="py-2 separador-total" :class=" grade.total < 0 ? 'text-red' : 'text-green'">{{grade.total}}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" text @click="dialogDetalhesGrade = false">
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogDownloadEtiquetas" persistent max-width="420" @input="v => v || sair()">
        <v-card>
            <v-card-title>Deseja fazer o download das etiquetas?</v-card-title>
            <v-divider></v-divider>
            <v-card-actions>
                <v-btn small dark color="primary" @click="dialogDownloadEtiquetas = false; sair()">Talvez mais tarde</v-btn>
                <v-spacer></v-spacer>
                <v-btn small dark color="green darken-1" @click="dialogDownloadEtiquetas = false; baixarTodasEtiquetas()">Baixar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Dialog Confirmação Exclusão Item -->
    <v-dialog
        persistent
        width="22rem"
        height="7rem"
        v-model="dialogExcluirProduto"
    >
        <v-card>
            <v-card-title>
                <h6 class="text-break">Tem certeza que deseja <b>excluir</b> este <b>produto</b>?</h6>
            </v-card-title>
            <v-card-text class="text-center">
                <span class="text-danger">Essa ação não poderá ser desfeita!</span>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="error"
                    :disabled="loading"
                    :loading="loading"
                    @click="excluirProduto()"
                >Continuar</v-btn>
                <v-btn
                    :disabled="loading"
                    @click="() => {
                        dialogExcluirProduto = false;
                        produtoSeraExcluido = null;
                    }"
                >Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Dialog Confirmação Conclusão Reposição -->
    <v-dialog
        persistent
        width="22rem"
        height="7rem"
        v-model="dialogConcluirReposicao"
    >
        <v-card>
            <v-card-title>
                <h6 class="text-break">Tem certeza que deseja <b>concluir</b> esta <b>reposição</b>?</h6>
            </v-card-title>
            <v-card-text class="text-center">
                <span class="text-danger">Após concluir a compra não será possivel alterá-la!</span>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="error"
                    :disabled="loading"
                    :loading="loading"
                    @click="concluiReposicao()"
                >Continuar</v-btn>
                <v-btn
                    :disabled="loading"
                    @click="dialogConcluirReposicao = false"
                >Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-overlay :value="loading">
        <v-progress-circular indeterminate size="64"></v-progress-circular>
    </v-overlay>

    <v-snackbar v-model="snackbar.mostrar" timeout="2000" :color="snackbar.cor" dark>
        {{ snackbar.mensagem }}

        <template v-slot:action="{ attrs }">
            <v-btn icon v-bind="attrs" @click="snackbar = { mostrar: false, cor: '', mensagem: '' }">
                <v-icon>mdi-close</v-icon>
            </v-btn>
        </template>
    </v-snackbar>
</v-app>

<style lang="scss" scoped>
    a {
        text-decoration: none !important;
    }

    .separador-total {
        border-top: thin solid rgba(0, 0, 0, .12);
        border-top-width: thin;
        border-top-style: solid;
        border-top-color: rgba(0, 0, 0, 0.5);
    }
</style>
<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="js/compras-cadastrar.js<?= $versao ?>"></script>
<script src="js/tools/formataMoeda.js"></script>
<script src="js/MobileStockApi.js"></script>
