<?php require_once 'cabecalho.php';
require_once __DIR__ . '/regras/alertas.php';
acessoUsuarioVendedor(); ?>

<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">

<style>
    .header-table {
        font-size: 2vw;
    }

    .numeracao {
        margin: 1vw;
        font-size: 2vw;
    }

    @media screen and (min-width: 1600px) {
        .numeracao {
            margin: 1vw;
            font-size: 3vw;
        }
    }
</style>

<script type="text/x-template" id="renderiza-compras">
    <v-list rounded>
        <div class="d-flex justify-content between align-items-center w-100">
            <v-subheader>Número da compra</v-subheader>
            <v-spacer></v-spacer>
            <v-subheader>Quantidade</v-subheader>
        </div>
        <v-list-group color="error" v-for="(compra, i) in compras" :key="i" v-model="compra.aberta" :prepend-icon="compra.situacao == 2 ? compra.caixas.filter(el => el.voltar).length === compra.caixas.length ? 'mdi-check text-danger' : 'mdi-check' : 'mdi-close'" no-action>
            <template v-slot:activator>
                <v-list-item-content>
                    <v-list-item-title>{{ compra.id }}</v-list-item-title>
                    <v-list-item-subtitle>{{ compra.data_emissao }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>{{ compra.caixas.reduce((total, item) => total += item.qtd,0) }}</v-list-item-action>
            </template>

            <v-list-item dense v-for="caixa in compra.caixas" :key="caixa.cod_barras" @click="caixa.voltar = !caixa.voltar; $emit('item-selecionado')">
                <v-list-item-content>
                    <v-list-item-subtitle class="text-danger">{{ caixa.usuario_deu_baixa }} ({{ caixa.qtd }})</v-list-item-subtitle>
                    <v-list-item-subtitle v-text="caixa.cod_barras"></v-list-item-subtitle>
                    <v-list-item-subtitle>{{ caixa.data_baixa }}</v-list-item-subtitle>
                </v-list-item-content>

                <v-list-item-action>
                    <v-checkbox color="error" :input-value="caixa.voltar"></v-checkbox>
                </v-list-item-action>
            </v-list-item>
        </v-list-group>
    </v-list>
</script>

<div id="produtosAguardandoEntrada">
    <v-app>
        <v-overlay :value="overlay">
            <v-progress-circular indeterminate size="64"></v-progress-circular>
        </v-overlay>

        <v-dialog v-model="dialogVoltarCompras" max-width="1000px" scrollable>
            <v-card v-if="conteudoModal">
                <v-toolbar dark color="error">
                    <v-toolbar-title>Lista de compras ({{ conteudoModal.length }})</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn dark icon tile @click="dialogVoltarCompras = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <v-overlay absolute :opacity=".2" :value="!conteudoModal">
                    <v-progress-circular :size="50" color="error" indeterminate></v-progress-circular>
                </v-overlay>

                <v-card-text>
                    <br>
                    <div v-if="situacaoModal === 1">
                        <v-alert dense outlined type="error">
                            Você só pode voltar uma compra com todos os pares por <b>completo</b>
                        </v-alert>
                        <renderiza-compras :compras="conteudoModal"></renderiza-compras>
                    </div>

                    <div v-else>
                        <v-alert dense outlined type="error">
                            Quantidade produtos aguardando entrada: <b>{{ totalProdutoAtual }}</b>
                        </v-alert>
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <v-subheader>Produtos que voltarão</v-subheader>
                            <v-subheader>Nº Compra</v-subheader>
                        </div>
                        <v-list rounded>
                            <v-list-item v-ripple v-for="(gradeSelecionada, key) in gradesComprasSelecionadas" :key="key">
                                <v-list-item-icon>
                                    <v-icon>fas fa-shopping-basket mt-2</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <table class="table text-center my-1">
                                        <tbody>
                                            <tr class="d-flex justify-content-center">
                                                <td v-for="grade in gradeSelecionada.grade" :key="grade.key" class="d-flex flex-column table-bordered p-1">
                                                    <b>{{ grade.nome_tamanho || grade.tamanho }}</b>
                                                    <span>{{grade.qtd}}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </v-list-item-content>
                                <v-list-item-action>{{ gradeSelecionada.id }}</v-list-item-action>
                            </v-list-item>
                        </v-list>

                    </div>

                    <span v-if="!conteudoModal"><br><br><br><br><br></span>


                </v-card-text>
                <v-card-actions>
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <span>
                            <v-btn v-if="situacaoModal === 2" @click="situacaoModal = 1">Voltar</v-btn>
                        </span>

                        <v-btn v-if="situacaoModal === 1" @click="situacaoModal = 2" :disabled="conteudoModal.filter(el => el.caixas.filter(item => item.voltar).length > 0).length === 0" color="error">
                            Próximo
                        </v-btn>
                        <v-btn :loading="loadingModal" v-else-if="situacaoModal === 2" @click="confirmarVoltaCaixasCompras" :disabled="totalProdutoAtual < conteudoModal.reduce((total, el) => total += el.caixas.filter(item => item.voltar).map(item => item.qtd).reduce((total, item) => total += item, 0), 0)" color="error">
                            Confirmar ({{ conteudoModal.reduce((total, el) => total += el.caixas.filter(item => item.voltar).map(item => item.qtd).reduce((total, item) => total += item, 0), 0) }})
                        </v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="dialogVoltarEstoque" max-width="1000px" scrollable>
            <v-card>
                <v-toolbar dark color="error">
                    <v-toolbar-title>Lista de compras
                        <!-- ({{ conteudoModal.length }}) -->
                    </v-toolbar-title>

                    <v-spacer></v-spacer>
                    <v-btn dark icon tile @click="dialogVoltarEstoque = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>

                <v-card-text>
                    <br>
                    <div v-if="situacaoModalVoltarEstoque === 1">
                        <v-alert dense outlined type="error">
                            Selecione uma caixa para ser devolver produtos
                        </v-alert>
                        <renderiza-compras :compras="conteudoModalVoltarEstoque" @item-selecionado="situacaoModalVoltarEstoque = 2"></renderiza-compras>
                    </div>

                    <div v-else>
                        <v-alert dense outlined type="error">
                            Informe os produtos que você removeu do estoque
                        </v-alert>
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <v-subheader>Produtos que voltarão</v-subheader>
                            <v-subheader>Nº Compra</v-subheader>
                        </div>
                        <v-list rounded>
                            <v-list-item v-for="(gradeSelecionada, key) in gradesEstoqueSelecionadas" :key="key">
                                <v-list-item-icon>
                                    <v-icon>fas fa-shopping-basket mt-2</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-form v-model="validadoModalEstoque">
                                        <table class="table text-center my-1">
                                            <tbody>
                                                <tr class="d-flex justify-content-center">
                                                    <td v-if="grade.qtd" v-for="grade in gradeSelecionada.grade" style="width: 70px;" :key="grade.key" class="d-flex flex-column table-bordered p-1">
                                                        <b>{{grade.nome_tamanho || grade.tamanho}}</b>
                                                        <v-text-field :placeholder="grade.qtd.toString()" dense :rules="[(v) => (parseInt(v) < grade.qtd || parseInt(v) == grade.qtd) || 'Deve ser menor ou igual a grade']" style="text-align:center;" type="number" name="grade-qtd" v-model="grade.qtd_removida" :max="grade.qtd" min="0"></v-text-field>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </v-form>
                                </v-list-item-content>
                                <v-list-item-action>{{ gradeSelecionada.id }}</v-list-item-action>
                            </v-list-item>
                        </v-list>
                    </div>

                </v-card-text>

                <v-card-actions>
                    <div class="w-100 d-flex align-itens-center justify-content-between">
                        <span>
                            <v-btn @click="situacaoModalVoltarEstoque = 1">Voltar</v-btn>
                        </span>

                        <span>
                            <v-btn @click="confirmaProdutosEstoqueDevolvidosCompra" :disabled="!validadoModalEstoque" class="error">Confirmar</v-btn>
                        </span>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.mostrar" :color="snackbar.cor">
            {{ snackbar.texto }}

            <template v-slot:action="{ attrs }">
                <v-btn text v-bind="attrs" @click="snackbar = false">
                    Fechar
                </v-btn>
            </template>
        </v-snackbar>
        <v-container fluid style="transform: translateY(-30px);">
            <!-- <div class="d-flex justify-content-between">
                <span></span> -->
                <h4 class="text-center ">Produtos aguardando entrada</h4>
                <!-- <v-chip small class="btn" href="estoque-sem-localizacao.php" dark>
                    <v-icon>
                        mdi-arrow-right
                    </v-icon>
                </v-chip>
            </div> -->
            <br>
            <div class="d-flex justify-content-center">
                <div class="text-center">
                    <div class="d-flex">
                        <v-chip @click="listaProdutosAguardandoEntrada = listaCompras" class="m-1 w-100 d-block" small color="error" label text-color="white">
                            Compras
                            <span class="ml-1 badge badge-light">
                                {{ listaCompras.length }}
                            </span>
                            <span class="ml-1 badge badge-secondary">
                                {{ listaCompras.reduce((total, item) => total += parseInt(item.qtd_compra), 0) }}
                            </span>
                        </v-chip>

                    </div>

                    <div class="d-flex">
                        <v-chip @click="listaProdutosAguardandoEntrada = listaFotosSeparar" class="m-1 w-100 d-block" small color="orange" label text-color="white">
                            Foto para separar
                            <span class="ml-1 badge badge-light">
                                {{ listaFotosSeparar.length }}
                            </span>
                            <span class="ml-1 badge badge-secondary">
                                {{ listaFotosSeparar.reduce((total, item) => total += parseInt(item.qtd), 0) }}
                            </span>
                        </v-chip>

                        <v-chip @click="listaProdutosAguardandoEntrada = listaDevolucoesFotos" class="m-1 w-100 d-block" small dark label text-color="white">
                            Fotos devoluções
                            <span class="ml-1 badge badge-light">
                                {{ listaDevolucoesFotos.length }}
                            </span>
                            <span class="ml-1 badge badge-secondary">
                                {{ listaDevolucoesFotos.reduce((total, item) => total += parseInt(item.qtd), 0) }}
                            </span>
                        </v-chip>
                    </div>

                    <div class="d-flex">
                        <v-chip @click="listaProdutosAguardandoEntrada = listaPedidosCancelados" class="m-1 w-100 d-block" small dark label text-color="white">
                            Pedido cancelado
                            <span class="ml-1 badge badge-light">
                                {{ listaPedidosCancelados.length }}
                            </span>
                            <span class="ml-1 badge badge-secondary">
                                {{ listaPedidosCancelados.reduce((total, item) => total += parseInt(item.qtd), 0) }}
                            </span>
                        </v-chip>

                        <v-chip @click="listaProdutosAguardandoEntrada = listaProdutos" class="m-1 w-100 d-block" small dark label text-color="white">
                            Tudo
                            <span class="ml-1 badge badge-light">
                                {{ listaProdutos.length }}
                            </span>
                            <span class="ml-1 badge badge-secondary">
                                {{ listaProdutos.reduce((total, item) => total += parseInt(item.qtd), 0) }}
                            </span>
                        </v-chip>
                    </div>

                </div>
            </div>
            <div class="d-flex flex-row-reverse">
                <a href="http://zxing.appspot.com/scan?ret=<?= $enderecoSiteInterno; ?>produtos-aguardando-entrada.php?cod_barras={CODE}">Leitor</a>
            </div>
            <div class="col">
                <v-text-field clearable v-model="procurarProduto" append-icon="mdi-magnify" label="Procurar"></v-text-field>
            </div>
            <input type="hidden" id="usuarioLogado" value="<?= usuarioLogado() ?>">
            <input type="hidden" id="inputCod_barras" value="<?= $_GET['cod_barras'] ?>">
            <?php unset($_GET['cod_barras']) ?>

            <v-data-iterator no-data-text="Nenhum registro" no-results-text="Nenhum dado encontrado" loading-text="Buscando dados" transition="slide-x-transition" hide-default-footer :page.sync="paginacao" no-data-text="Sem resultados" sort-by="usuario_resp,data_hora,qtd" sort-desc :items-per-page="15" :search="procurarProduto" ref="expandableTable" :items="listaProdutosAguardandoEntrada">

                <template v-slot:default="props">
                    <transition name="slide-x-transition">
                        <v-lazy transition="slide-x-transition">
                            <div class="w-100 col-sm-12 d-inline-block">

                                <div style="width: fit-content;" class="w-auto" v-for="item in props.items">

                                    <v-card color="error" block v-if="item.tipo_entrada.indexOf('Compra') != -1" class="mt-2 w-100 ">

                                        <template v-slot:progress indeterminate>
                                            <v-progress-linear indeterminate color="white"></v-progress-linear>
                                        </template>

                                        <div class="w-100 d-block text-white">

                                            <div class="card-header header-table align-items-center d-flex justify-content-center pt-5">
                                                <h6 style="top: 0; right: 0" class="position-absolute bg-dark pr-2 pl-2 pt-1 pb-1 rounded">{{ item.usuario_resp }} </h6>
                                                <h4>{{ item.id_produto }} - {{ item.produto }} - {{ item.tipo_entrada }}
                                                    <v-chip small>
                                                        {{ listaProdutosAguardandoEntrada === listaTrocas ? item.qtd_troca : item.qtd_compra }}
                                                    </v-chip>
                                                </h4>
                                            </div>

                                            <div class="card-body bg-light rounded-bottom">
                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Localização:</h5>
                                                    <v-spacer></v-spacer>
                                                    <span>
                                                        <h5 v-if="item.localizacao">{{ item.localizacao }}</h5>
                                                        <v-chip v-else color="error">
                                                            Definir localização
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Entrada:</h5>
                                                    <v-spacer></v-spacer>
                                                    <h6>{{ item.data_hora | formataData }}</h6>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <v-spacer></v-spacer>
                                                    <span class="d-block">
                                                        <v-chip class="numeracao" dark x-small class="m-1" v-for="numero in item.tamanho.split(',')">
                                                            {{ numero }}
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="w-100 header-table mt-2 d-flex justify-content-between align-items-center">
                                                    <!-- <span>
                                                        <v-btn @click="abreModalVoltarCompras(item)" color="error" outlined>
                                                            Voltar compras
                                                        </v-btn>
                                                        <v-btn @click="abreModalVoltarEstoque(item)" color="warning" outlined>Voltar estoque</v-btn>
                                                    </span> -->
                                                    <v-spacer></v-spacer>
                                                    <span>
                                                        <v-btn class="btn" small :href="'estoque-sem-localizacao.php?cod=' + item.cod_barras" dark>
                                                            Localizar
                                                        </v-btn>
                                                        <v-btn light icon class="btn" :href="'fornecedores-produtos.php?id=' + item.id_produto">
                                                            <v-icon>
                                                                mdi-pencil
                                                            </v-icon>
                                                        </v-btn>
                                                    </span>
                                                </div>
                                            </div>

                                        </div>
                                    </v-card>

                                    <v-card v-else-if="item.tipo_entrada.indexOf('Separar para foto') != -1" color="orange" class="mt-2 w-100">
                                        <div class="w-100 d-block text-white ">

                                            <div class="card-header header-table mt-1 align-items-center d-flex justify-content-center pt-5">
                                                <h6 style="top: 0; right: 0" class="position-absolute bg-dark pr-2 pl-2 pt-1 pb-1 rounded">{{ item.usuario_resp }} </h6>
                                                <h4>{{ item.id_produto }} - {{ item.produto }} - {{ item.tipo_entrada }}
                                                    <v-chip small>
                                                        {{ item.qtd }}
                                                    </v-chip>
                                                </h4>
                                            </div>
                                            <div class="card-body bg-light rounded-bottom">
                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Localização:</h5>
                                                    <v-spacer></v-spacer>
                                                    <span>
                                                        <h5 v-if="item.localizacao">{{ item.localizacao }}</h5>
                                                        <v-chip v-else color="error">
                                                            Definir localização
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Entrada:</h5>
                                                    <v-spacer></v-spacer>
                                                    <h6>{{ item.data_hora | formataData }}</h6>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <v-spacer></v-spacer>
                                                    <span class="d-block">
                                                        <v-chip class="numeracao" dark x-small x-small class="m-1" v-for="numero in item.tamanho.split(',')">
                                                            {{ numero }}
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="header-table mt-2 d-flex flex-row-reverse align-items-center">
                                                    <!-- <v-btn @click="separarItem(item)" class="btn" small dark>
                                                        Separar
                                                    </v-btn> -->
                                                    <v-btn light icon class="btn" :href="'fornecedores-produtos.php?id=' + item.id_produto">
                                                        <v-icon>
                                                            mdi-pencil
                                                        </v-icon>
                                                    </v-btn>
                                                </div>
                                            </div>

                                        </div>
                                    </v-card>


                                    <v-card v-else-if="item.devolucao === true" dark class="mt-2 w-100">
                                        <div class="w-100 d-block text-white ">

                                            <div class="card-header header-table mt-1 align-items-center d-flex justify-content-center pt-5">
                                                <h6 style="top: 0; right: 0" class="position-absolute bg-dark pr-2 pl-2 pt-1 pb-1 rounded">{{ item.usuario_resp }} </h6>
                                                <h4>{{ item.id_produto }} - {{ item.produto }} - {{ item.tipo_entrada }}
                                                    <v-chip small>
                                                        {{ listaProdutosAguardandoEntrada === listaTrocas ? item.qtd_troca : item.qtd }}
                                                    </v-chip>
                                                </h4>
                                            </div>
                                            <div class="card-body bg-light rounded-bottom">
                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Localização:</h5>
                                                    <v-spacer></v-spacer>
                                                    <span>
                                                        <h5 v-if="item.localizacao">{{ item.localizacao }}</h5>
                                                        <v-chip v-else color="error">
                                                            Definir localização
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <h5>Entrada:</h5>
                                                    <v-spacer></v-spacer>
                                                    <h6>{{ item.data_hora | formataData }}</h6>
                                                </div>

                                                <div class="header-table mt-1 d-flex jusitfy-content-between">
                                                    <v-spacer></v-spacer>
                                                    <span class="d-block">
                                                        <v-chip class="numeracao" dark x-small x-small class="m-1" v-for="numero in item.tamanho.split(',')">
                                                            {{ numero }}
                                                        </v-chip>
                                                    </span>
                                                </div>

                                                <div class="header-table mt-2 d-flex flex-row-reverse align-items-center">
                                                    <v-btn class="btn" small :href="'estoque-sem-localizacao.php?cod=' + item.cod_barras" dark>
                                                        Localizar
                                                    </v-btn>
                                                    <v-btn light icon class="btn" :href="'fornecedores-produtos.php?id=' + item.id_produto">
                                                        <v-icon>
                                                            mdi-pencil
                                                        </v-icon>
                                                    </v-btn>
                                                    <v-btn light icon target="_blanc" :href="`pedido-troca-pendente.php?identificacao=${item.identificao}`">
                                                        <v-icon>
                                                            fas fa-sync
                                                        </v-icon>
                                                    </v-btn>
                                                </div>
                                            </div>

                                        </div>
                                    </v-card>

                                </div>
                            </div>
                        </v-lazy>
                    </transition>
                </template>

            </v-data-iterator>
            <v-lazy transition="slide-x-transition">
                <v-pagination x-small dark circle color="error" :length="Math.ceil(listaProdutosAguardandoEntrada.length / 15)" v-model="paginacao"></v-pagination>
            </v-lazy>
        </v-container>
    </v-app>
</div>

<script src="js/produtos-aguardando-entrada.js<?= $versao ?>"></script>