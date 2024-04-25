<?php require_once 'cabecalho.php';
acessoUsuarioVendedor(); ?>

<head>
    <link href="css/entrada-compras.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<script>
    window.localStorage.setItem('idUsuarioLogado', parseInt(JSON.parse('<?php echo json_encode(idUsuarioLogado($filtro)) ?>')))
</script>
<!-- Componente Vue -->
<v-app id="entradaComprasVue" class="container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <audio id="notificacao" preload="auto">
                <source src="http://soundbible.com/grab.php?id=2154&type=mp3" type="audio/mpeg">
            </audio>

            <!-- Modal -->
            <div class="modal fade" id="modalAlerta" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document" id="conteudo-modal-compras">
                    <div class="modal-content"> 
                        <div class="modal-header printable">
                            <h3 v-if="tipoModal == 1"><strong>Alerta</strong></h3>
                            <h5 v-if="tipoModal == 2" class="modal-title" id="exampleModalLabel">Deseja remover este registro?</h5>
                            <h3 v-if="tipoModal == 3"><strong>Relatório de Entrega</strong></h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body printable">
                            <template v-if="tipoModal == 1">{{msgModal}}</template>
                            <template v-if="tipoModal == 2" v-model="msgModal">
                                <div v-for="(value, name) in msgModal" v-if="typeof(value) != 'object'">
                                    <b>{{ name | upperCase }}:</b> {{ value }}
                                </div>
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col" v-for="(value) in msgModal.grade">{{value.tamanho}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td v-for="(value) in msgModal.grade">{{value.quantidade}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-if="tipoModal == 3" v-model="conteudoImpressao">
                                <div v-for="(value, name) in conteudoImpressao" v-if="typeof(value) != 'object'">
                                    <b>{{ name | upperCase }}:</b> {{ value }}
                                </div>
                                </br>
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col" v-for="(item,name) in conteudoImpressao.produto[0]"><br>{{ name | upperCase }}</br></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(value,name) in conteudoImpressao.produto">
                                            <td v-for="item in value">{{item}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="alert alert-secondary" role="alert">Quantidade volumes: {{tamanhoLista}}</div>
                                <div class="alert alert-secondary" role="alert">
                                    <p>* Não nos responsabilizamos pela perda deste documento.</p>
                                    <p>* Favor conferir todas as informações contidas neste documento no ato da entrega.</p>
                                    <p>* Não aceitaremos reclamações posteriores.</p>
                                    <p>* O pagamento será realizado apenas com a apresentação deste documento.</p>
                                    </br> </br>
                                    <p style="text-align: center">__________________________________________________</p>
                                    <p style="text-align: center">Assinatura do Recebedor</p>
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer nao-imprimir">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                            <button v-if="tipoModal == 2" type="button" class="btn btn-primary" @click="removeLinha()">Confirmar</button>
                            <button v-if="tipoModal == 3" type="button" class="btn btn-secondary" data-dismiss="modal" id="botao-imprimir" @click.stop.prevent="printModal" style="display: flex">Imprimir &nbsp <i class="material-icons">print</i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text">Código Barras</span>
                </div>
                
                <input id="input-codBarras" type="text" class="form-control" placeholder="Insira o código de barras aqui" aria-label="Insira o código de barras aqui" aria-describedby="basic-addon1" v-model.lazy="inputBarCode" @change="adicionaRegistro" ref="search">
            </div>
            <div class="alert alert-secondary" role="alert" v-show="(quantidadeLida> 0)">Quantidade lida: {{quantidadeLida}}</div>
            <div class="table-responsive-md table-responsive" id="table-container">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <template>
                                <th v-for="thead in thead"> {{ thead }}</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template>
                            <tr v-for="(item, index) in listaEntradas" :id="item.codigo_barras" :key="`item-${index}`" @click="abreModal(index, item)">
                                <td> {{ item[index].codigo_barras }}</td>
                                <td> {{ item[index].cod_compra }}</td>
                                <td> {{ item[index].fornecedor }}</td>
                                <td> <a :href="'fornecedores-produtos.php?id=' + item[index].id_produto" target="_blank" rel="noopener noreferrer">{{ item[index].produto }}</a></td>
                                <td> {{ item[index].pares }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between" id="botoes-navegacao-container">
                <a href="compras.php"> <button class="btn btn-danger">Voltar</button></a>
                <label for="tamanhoFoto">Insira tamanho para foto:</label>
            <input type="text" v-model="tamanhoFoto" id="tamanhoFoto" class="form-control col-sm-2"><br>
                <button class="btn btn-primary" id="botao_enviar" @click="enviar">Enviar</button>
            </div>
            <div class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" id="alert-toast" data-delay="4000">
                <div class="toast-body">
                    <div id="conteudo-toast"></div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <h4>Histórico de Entradas</h4>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between" v-for="(item, i) in listaHistorico" :key="i">
                    {{item[0].data}} - {{item[0].fornecedor}}
                    <i class="material-icons btn btn-outline-secondary btn-small" @click="imprimirHistorico(i)">print</i>
                </li>
            </ul>
        </div>
    </div>

    <template>
        <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
            {{ snackbar.texto }}
        </v-snackbar>
    </template>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/entrada-compras.js<?= $versao ?>"></script>