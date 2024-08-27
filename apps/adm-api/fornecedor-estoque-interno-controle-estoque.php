<?php
require_once __DIR__ . '/cabecalho.php';
acessoUsuarioFornecedor();
?>

<link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<style>
    .linha-grades {
        display: flex;
        flex-wrap: wrap;
        gap: 0.15rem;
        justify-content: center;
        margin: 0.25rem auto;
        margin-bottom: 0.5rem;
        max-width: 25rem;
    }
    .grade {
        align-items: center;
        display: flex;
        flex-direction: column;
        font-size: 0.8rem;
        font-weight: bold;
        margin: 0.05rem 0.1rem;
    }
    .grade > span {
        min-width: 1.5rem;
        font-size: 0.8rem;
        width: 100%;
        padding: 0.75rem;
        text-align: center;
        width: 100%;
    }
    .grade > #cabecalho {
        padding: 0.25rem 0.75rem;
    }
    .grade > #conteudo {
        padding: 0.5rem 1rem;
    }
</style>

<div id="app">
    <v-app>
        <v-main>
            <v-card>
                <v-data-table
                    dense
                    hide-default-footer
                    :headers="headersProdutosEstoque"
                    :items="produtosEstoque"
                    :items-per-page="-1"
                    :loading="loadingProdutosEstoque"
                >
                    <template v-slot:top>
                        <v-container>
                            <v-row no-gutters>
                                <v-col>
                                    <h5>Controle estoque</h5>
                                </v-col>

                                <v-col class="text-center">
                                    <v-btn dark @click="dialogConfirmarZerarEstoque = true">Zerar Todo Estoque</v-btn>
                                </v-col>

                                <v-col>
                                    <v-text-field @keydown.enter="listaProdutosEstoque" v-model="pesquisaProdutos" label="Pesquisa" append-icon="mdi-magnify" :loading="loadingProdutosEstoque" outlined single-line hide-details dense></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </template>
                    <template v-slot:item.id="{ item }">
                        <a :href="'fornecedores-produtos.php?id=' + item.id">{{ item.id }}</a>
                    </template>
                    <template v-slot:item.foto="{ item }">
                        <img :src="item.foto ? item.foto : 'images/img-placeholder.png'" :alt="item.id" class="img-fluid" style="width: 100px">
                    </template>
                    <template v-slot:item.estoque="{ item }">
                        <div class="linha-grades">
                            <div
                                class="grade"
                                :key="index"
                                v-for="(grade, index) in item.estoque"
                            >
                                <span
                                    class="bg-dark text-white"
                                    id="cabecalho"
                                >{{ grade.nome_tamanho }}</span>
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span
                                            class="border border-1 border-dark"
                                            id="conteudo"
                                            :class="grade.total < 0 ? 'text-red' : ''"
                                            v-bind="attrs"
                                            v-on="on"
                                        >{{ grade.estoque }}</span>
                                    </template>
                                    <span>Produtos negativos significam que existem clientes aguardando este produto.</span>
                                </v-tooltip>
                            </div>
                        </div>
                    </template>
                    <template v-slot:item.acoes="{ item }">
                        <v-btn @click="abreModalMovimentacaoEstoque(item)" color="success" :loading="isLoadingBloqueado">Alterar</v-btn>
                    </template>
                </v-data-table>
                <br />
                <div class="d-flex justify-content-around pb-4">
                    <v-btn
                        dense
                        :dark="!!pagina"
                        :disabled="!pagina"
                        :loading="loadingProdutosEstoque"
                        @click="pagina--"
                    >
                        <v-icon>mdi-chevron-left</v-icon>
                        Produtos anteriores
                    </v-btn>
                    <v-btn
                        :dark="produtosEstoque.length >= 100"
                        dense
                        :disabled="!maisPaginas || produtosEstoque.length < 100"
                        :loading="loadingProdutosEstoque"
                        @click="pagina++"
                    >
                        Proximos produtos
                        <v-icon>mdi-chevron-right</v-icon>
                    </v-btn>
                </div>
            </v-card>

            <v-dialog
                :persistent="loadingCorrigeEstoque"
                :value="Object.keys(produtoModalAlteraEstoqueProduto).length"
                @input="fechaModalMovimentacaoEstoque"
                :fullscreen="!sellerBloqueado" style="z-index: 2000;"
                transition="dialog-bottom-transition"
                :width="sellerBloqueado ? '23rem' : ''"
            >
                <template v-if="sellerBloqueado">
                    <v-card width="23rem">
                        <v-toolbar dark>
                            <v-icon>mdi-alert-circle</v-icon> &ensp; Reposição de Estoque Bloqueada
                            <v-spacer></v-spacer>
                            <v-btn dark icon @click="fechaModalMovimentacaoEstoque(false)">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-card-title style="text-align: justify;">
                            <h6>
                                Sua permissão para repor estoque foi removida
                                <span v-if="mediaCancelamentos > 30">.</span>
                                <span v-if="mediaCancelamentos <= 30"> e por isso seus estoques foram zerados.</span>
                            </h6>
                        </v-card-title>
                        <v-card-text v-if="mediaCancelamentos > 30">
                            Detectamos que <b style="color: red;">{{ mediaCancelamentos }}%</b> dos seus produtos foram corrigidos pelo sistema e por isso seus estoques foram zerados.
                        </v-card-text>
                        <v-card-text>
                            Entre em contato com nosso suporte pelo WhatsApp para mais esclarecimentos!
                        </v-card-text>
                        <v-card-actions style="justify-content: center;">
                            <v-btn dark color="var(--cor-padrao-whatsapp)" @click="contatoSuporte" :loading="loadingWhatsapp">
                                <v-icon>mdi-whatsapp</v-icon>
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </template>
                <template v-else>
                    <v-card :disabled="loadingCorrigeEstoque" v-if="Object.keys(produtoModalAlteraEstoqueProduto).length > 0">
                        <v-toolbar color="error" class="text-white">
                            Alterar estoque do produto {{ produtoModalAlteraEstoqueProduto.id }}
                            <v-spacer></v-spacer>
                            <v-btn dark icon @click="fechaModalMovimentacaoEstoque(false)">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <div class="row m-0">
                            <div class="col-sm-4" v-if="produtoModalAlteraEstoqueProduto.foto">
                                <img class="w-100" :src="produtoModalAlteraEstoqueProduto.foto" :alt="produtoModalAlteraEstoqueProduto.descricao">
                            </div>
                            <div class="col-sm-4" v-else>
                                <v-alert dense type="info">Este produto não contém foto. <br/> Você deve adicionar uma foto para poder fazer alterações no estoque.</v-alert>
                                <v-btn :href="`fornecedores-produtos.php?id=${produtoModalAlteraEstoqueProduto.id}`" block>Adicionar foto</v-btn>
                            </div>
                            <div class="col-sm-8">
                                <v-form ref="formularioCorrigir" @submit.prevent="corrigeEstoque">
                                    <v-select
                                        :disabled="!produtoModalAlteraEstoqueProduto.foto"
                                        :rules="[v => !!v || 'Preencha um tipo de movimentação']"
                                        :items="[{ text: 'Entrada', value: 'ENTRADA' }, { text: 'Saída', value: 'SAIDA' }]"
                                        v-model="tipoMovimentacao"
                                        label="Tipo de movimentação"
                                    ></v-select>
                                </v-form>
                                <v-data-table disable-pagination hide-default-footer disable-sort :headers="headersMovimentacaoManual" :items="produtoModalAlteraEstoqueProduto.estoque">
                                    <template v-slot:item.tamanho="{ item }">
                                        <v-chip dark>{{ item.nome_tamanho }}</v-chip>
                                    </template>
                                    <template v-slot:item.acao="{ item }">
                                        <div class="d-flex w-100">
                                            <v-text-field :disabled="!produtoModalAlteraEstoqueProduto.foto" label="Quantidade" v-model="item.qtd_movimentar" @input="v => calculaTotalEstoque(item, v)" type="number" outlined single-line hide-details dense></v-text-field>
                                        </div>
                                    </template>
                                </v-data-table>
                                <v-form ref="formularioCorrigir" @submit.prevent="corrigeEstoque">
                                    <template v-if="produtoModalAlteraEstoqueProduto.foto">
                                        <v-btn
                                            :loading="loadingCorrigeEstoque"
                                            type="submit"
                                            block
                                            :disabled="!tipoMovimentacao || (produtoModalAlteraEstoqueProduto.estoque.filter(el => !el.qtd_movimentar).length === produtoModalAlteraEstoqueProduto.estoque.length)"
                                            :color="(tipoMovimentacao == 'ENTRADA') ? 'success' : 'error'"
                                        >
                                            {{
                                                tipoMovimentacao == '' ? 'Corrigir'
                                                    : tipoMovimentacao == 'ENTRADA'
                                                        ? '+ Acrescentar Estoque'
                                                        : '- Subtrair Estoque'
                                            }}
                                        </v-btn>
                                    </template>
                                </v-form>
                            </div>
                        </div>
                    </v-card>
                </template>
            </v-dialog>
        </v-main>

        <!-- Dialog Confirmar Zerar Estoque -->
        <v-dialog persistent width="35rem" v-model="dialogConfirmarZerarEstoque">
            <v-card>
                <v-card-title class="justify-content-center">
                    Tem certeza que deseja zerar seu estoque interno?
                </v-card-title>
                <v-card-text>
                    <p class="text-center text-danger">
                        Atenção: Essa ação <b>não afetará</b>
                        os produtos já reservados pelo clientes, a sua reputação ou o estoque fulfillment.
                    </p>
                </v-card-text>
                <v-card-actions class="d-flex justify-content-around">
                    <v-btn
                        color="error"
                        :dark="!carregandoZerandoEstoque"
                        :disabled="carregandoZerandoEstoque"
                        @click="dialogConfirmarZerarEstoque = false"
                    >Cancelar</v-btn>
                    <v-btn
                        color="success"
                        :dark="!carregandoZerandoEstoque"
                        :disabled="carregandoZerandoEstoque"
                        :loading="carregandoZerandoEstoque"
                        @click="zerarEstoqueResponsavel"
                    >Confirmar</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <template>
            <v-snackbar
                :color="snackbar.cor"
                v-model="snackbar.ativar"
                style="z-index: 3000"
            >
                {{ snackbar.texto }}
            </v-snackbar>
        </template>
    </v-app>
</div>

<script src="js/MobileStockApi.js"></script>
<script src="js/whatsapp.js<?= $versao ?>"></script>
<script src="js/fornecedor-estoque-interno-controle-estoque.js" type="module"></script>
