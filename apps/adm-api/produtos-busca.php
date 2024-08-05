<?php

use MobileStock\repository\ProdutosRepository;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/cabecalho.php';

$conexao = Conexao::criarConexao();

if (isset($_GET['id']) && $_GET['id']) {
    $produto = ProdutosRepository::buscaDetalhesProduto($conexao, $_GET['id']);
}
?>

<style>
    th {
        text-align: center
    }

    .lista-autocomplete {
        transition: visibility .1s;
    }

    .input-produto:not(:focus)~.lista-autocomplete {
        visibility: collapse;
    }
</style>

<template type="text/x-template" id="referencias">
    <div>
        <div class="row align-items-baseline">
            <div class="col-lg-4">
                <span class="d-block text-center">Estoque</span>
                <div class="d-flex align-items-center justify-content-around">
                    <span class="d-flex flex-column">
                        <span>Estoque</span>
                        <span>Vendido</span>
                    </span>
                    <table class="table text-center">
                        <tbody>
                            <tr class="d-flex justify-content-center">
                                <td v-for="grade in produto.estoque" class="d-flex flex-column table-bordered p-1">
                                    <b>{{ grade.nome_tamanho }}</b>
                                    <span>{{grade.qtd}}</span>
                                    <span>{{grade.vendido}}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="d-flex align-items-baseline justify-content-between border-bottom pb-2">
                    ID:
                    <h5 class="m-0 p-0">{{ produto.id }}</h5>
                </div>
                <div class="d-flex align-items-baseline justify-content-between border-bottom pb-2">
                    Descricao:
                    <h6 class="m-0 p-0">{{ produto.descricao }}</h6>
                </div>
                <div class="d-flex align-items-baseline justify-content-between border-bottom pb-2">
                    Localização:
                    <h5 class="m-0 p-0">{{ produto.localizacao }}</h5>
                </div>
                <div class="d-flex align-items-baseline justify-content-between border-bottom pb-2 mt-2">
                    Fornecedor:
                    <h6 class="m-0 p-0">{{ produto.fornecedor }}</h6>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <div class="overflow-auto">
                <table class="table table-sm table-striped table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th colspan="12">
                                Últimos registros de hoje
                            </th>
                        </tr>
                        <tr>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Tamanho</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(movimentacao, index) in produto.historico_movimentacoes" :key="index">
                            <th>
                                <div class="w-100 d-flex align-items-center justify-content-center">
                                    <span :class="`p-2 h4 mt-0 pt-0 mb-0 pb-0 badge badge-${(movimentacao.tipo_movimentacao === 'M') ? 'dark' : (movimentacao.tipo_movimentacao === 'S' ? 'danger' : 'success')}`">{{ (movimentacao.tipo_movimentacao === 'M') ? '=' : (movimentacao.tipo_movimentacao === 'S' ? '-' : '+') }}</span>
                                </div>
                            </th>
                            <th>{{ movimentacao.descricao }}</th>
                            <th>{{ movimentacao.tamanho }}</th>
                            <th>{{ movimentacao.data_hora }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-2">
            <div class="overflow-auto">
                <table class="table table-sm table-striped table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th colspan="12">Histórico de localizações</th>
                        </tr>
                        <tr>
                            <th>Localização</th>
                            <th>Qtd</th>
                            <th>Usuário</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(localizacao, index) in produto.historico_localizacoes" :key="index">
                            <th>
                                <div class="d-flex align-items-center justify-content-evenly h4">
                                    <div class="badge badge-dark">{{ localizacao.old }}</div>
                                    <i class="m-1 fas fa-long-arrow-alt-right"></i>
                                    <div class="badge badge-dark">{{ localizacao.new }}</div>
                                </div>
                            </th>
                            <th>{{ localizacao.qtd }}</th>
                            <th>{{ localizacao.usuario }}</th>
                            <th>{{ localizacao.data }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</template>

<template type="text/x-template" id="reposicoes">
    <div class="overflow-auto">
        <table class="table table-sm table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>SKU</th>
                    <th>Data Criação</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(reposicao, index) in reposicoes">
                    <tr :key="reposicao.id_reposicao">
                        <th>{{ reposicao.sku }}</th>
                        <th>{{ reposicao.data_criacao }}</th>
                        <th>{{ reposicao.situacao }}</th>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

<template type="text/x-template" id="transacoes">
    <div class="overflow-auto">
        <table class="table table-sm table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th colspan="1">#</th>
                    <th>Tamanho</th>
                    <th>Cliente</th>
                    <th>Data</th>
                    <th>Pago</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(transacao, index) in transacoes" :key="index">
                    <th>{{ transacao.id }}</th>
                    <th>{{ transacao.nome_tamanho }}</th>
                    <th>{{ transacao.nome_cliente }}</th>
                    <th>{{ transacao.data_hora }}</th>
                    <th><input type="checkbox" v-model="transacao.esta_pago" disabled></th>
                    <th>
                        <a target="_blanc" :href="`transacao-detalhe.php?id=${transacao.id}`">
                            <i class="fas fa-edit"></i>
                        </a>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<template type="text/x-template" id="trocas">
    <div class="overflow-auto">
        <table class="table table-sm table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Confirmada</th>
                    <th>Tamanho</th>
                    <th>Cliente</th>
                    <th>Taxa</th>
                    <th>Preco</th>
                    <th>Data</th>
                    <th>Detalhes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(troca, index) in trocas" :key="index">
                    <th>
                        <v-simple-checkbox v-model="troca.esta_confirmada" disabled></v-simple-checkbox>
                    </th>
                    <th>{{ troca.nome_tamanho }}</th>
                    <th>{{ troca.nome_cliente }}</th>
                    <th>{{ troca.taxa | dinheiro }}</th>
                    <th>{{ troca.preco | dinheiro }}</th>
                    <th>{{ troca.data }}</th>
                    <th>
                        <button
                            @click="buscaDetalhesTroca(troca.uuid)"
                            type="button"
                            class="btn btn-primary"
                            data-toggle="modal"
                            data-target="#exampleModal"
                        >
                            <i class="fas fa-info"></i>
                        </button>
                    </th>
                    <th>
                        <a target=" _blanc" :href="`pedido-troca-pendente.php?identificacao=${troca.uuid}`">
                            <i class="fas fa-edit"></i>
                        </a>
                    </th>
                </tr>
            </tbody>
        </table>
        <div :class="`modal bd-example-modal-lg ${detalhes_trocas.length > 0 ? 'show' : ''}`" id="exampleModal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Detalhes da troca</h5>
                    </div>
                    <div class="modal-body">
                        <div v-if="detalhes_trocas.length === 0">
                            Dados não encontrados!
                        </div>
                        <div v-else>
                            <table class="table table-sm table-striped table-hover table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Data</th>
                                        <th>Id da entrega</th>
                                        <th>Id do produto</th>
                                        <th>Id da transação</th>
                                        <th>Nome do usuário</th>
                                        <th>Situação</th>
                                        <th>Origem</th>
                                        <th>Ponto</th>
                                        <th>Foto</th>
                                        <th>Tamanho</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>{{ detalhes_trocas.data }}</th>
                                        <th>{{ detalhes_trocas.id_entrega }}</th>
                                        <th>{{ detalhes_trocas.id_produto }}</th>
                                        <th>{{ detalhes_trocas.id_transacao }}</th>
                                        <th>{{ detalhes_trocas.nome_usuario }}</th>
                                        <th>{{ detalhes_trocas.situacao }}</th>
                                        <th>{{ detalhes_trocas.origem }}</th>
                                        <th>{{ detalhes_trocas.ponto }}</th>
                                        <th>
                                            <img :src="detalhes_trocas.caminho_foto" style="width: 100%" />
                                        </th>
                                        <th>{{ detalhes_trocas.nome_tamanho }}</th>
                                        <th v-if="detalhes_trocas.tipo === 'DE'">Defeito</th>
                                        <th v-else>Normal</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="resetaDados()">Ok
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<div class="body-novo container-fluid" id="app">
    <input type="hidden" id="descricao_query" value="<?= $produto['descricao'] ?? '' ?>">

    <form class="row align-items-start" @submit.prevent="buscaProduto">
        <div :class="`${$vuetify.breakpoint.mobile ? 'col-md-12' : 'col-md-8'}`" style="min-height: 3000px;">
            <div>
                <div class="row align-items-baseline">
                    <div class="col-sm-9">
                        <input
                            autofocus
                            v-model="produto"
                            :class="`${!produto ? 'is-invalid' : 'is-valid'} input-produto form-control position-relative `"
                            type="search"
                            name="descricao"
                            placeholder="ID - Produto"
                            id="produto"
                        >
                    </div>
                    <div class="col-sm-3">
                        <input
                            list="tamanhoAutoComplete"
                            autocomplete="off" type="text"
                            v-model="tamanho"
                            class="form-control"
                            placeholder="Tamanho"
                            name="tamanho"
                            id="tamanho"
                        >
                        <datalist id="tamanhoAutoComplete">
                            <option v-for="i in numerosAutocomplete" :value="i"></option>
                        </datalist>
                    </div>
                </div>
                <small>Pesquise pelo ID do produto</small>
            </div>

            <div class="mt-3" v-if="busca.length !== 0 && !loading">
                <referencias :produto="busca.referencias" v-if="menuAtivo === 'Referencias'"></referencias>
                <reposicoes :reposicoes="busca.reposicoes" v-else-if="menuAtivo === 'Reposicoes'"></reposicoes>
                <transacoes :transacoes="busca.transacoes" v-else-if="menuAtivo === 'Transacoes'"></transacoes>
                <trocas :trocas="busca.trocas" v-else-if="menuAtivo === 'Trocas'"></trocas>
            </div>
            <div class="mt-3 text-center" v-else-if="loading">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div :style="`${!$vuetify.breakpoint.mobile ? 'min-height: 600px;' : ''} `" :class="`${$vuetify.breakpoint.mobile ? 'fixed-footer' : 'd-flex flex-column justify-content-between align-item-center'} bg-light w-100`">
                <span>
                    <h2 v-if="!$vuetify.breakpoint.mobile" class="text-center">Relatório</h2>
                    <hr v-if="!$vuetify.breakpoint.mobile">
                    <div v-if="busca.length !== 0" style="white-space: nowrap;" :class="`${$vuetify.breakpoint.mobile ? 'btn-group overflow-auto' : 'btn-group-vertical'} w-100`">
                        <button type="button" @click="menuAtivo = key" v-for="(item, key) in opcoesRelatorio" :class="`${$vuetify.breakpoint.mobile ? 'h-50 btn-sm flex-column-reverse align-items-center' : 'p-3 align-items-baseline justify-content-between border-bottom btn-block'} d-flex btn rounded-0 shadow-none border-0 ${menuAtivo === key ? 'active' : ''} btn-outline-dark m-0`">
                            <span>{{ key }}</span>
                            <h3 class="m-0 p-0">{{ item }}</h3>
                        </button>
                    </div>
                </span>
                <div class="w-100 d-flex align-items-center justify-content-between">
                    <button type="submit" :disabled="!produto" class="rounded-0 btn btn-block btn-primary shadow-none btn-lg">Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <v-snackbar
        :color="snackbar.cor"
        v-cloak
        v-model="snackbar.mostrar"
    >
        {{ snackbar.texto }}
    </v-snackbar>

</div>


<script src="js/produtos-busca.js<?= $versao ?>"></script>
