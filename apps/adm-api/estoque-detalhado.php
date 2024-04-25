<?php
require_once __DIR__ . '/cabecalho.php';

acessoUsuarioFornecedor();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<style>
    .responsivo {
        display: grid;
        grid-template-columns: 1fr;
        max-width: 	1024px;
    }
    @media screen and (min-width: 540px) {
        .responsivo {
            grid-template-columns: 1fr 1fr;
        }
    }
    .linha-grades {
        display: flex;
        flex-wrap: wrap;
        gap: 0.15rem;
        justify-content: center;
        margin: 0 auto;
        margin-bottom: 0.5rem;
        max-width: 20rem;
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
        width: 100%;
        padding: 0.1rem 0.2rem;
        text-align: center;
        width: 100%;
    }
    .id-produto {
        background-color: black;
        border-bottom-right-radius: 0.5rem;
        border-top-left-radius: 0.3rem;
        color: var(--cor-text);
        padding: 0 0.3rem;
        position: absolute;
    }
    .desc-produto {
        font-size: 1rem;
        font-weight: bold;
        text-align: center;
        max-width: 18.75rem;
    }
    .infos-produto {
        display: inline;
        margin: 0 1rem;
    }
    .infos-tamanho {
        color: var(--cor-text);
        display: flex;
        height: 3rem;
        justify-content: space-around;
    }
    .infos-tamanho p {
        margin-bottom: 0;
    }
    .ver-mais > span {
        display: flex;
        flex-direction: column;
        font-size: 1rem;
    }
</style>

<v-app id="estoqueDetalhadoVue">
    <v-main class="container-md">
        <h3 class="text-center">Detalhamento 
            <span v-if="estoqueAtual === 'FULFILLMENT'">Estoque Fulfillment</span>
            <span v-if="estoqueAtual === 'EXTERNO'">Estoque Interno</span>
            <span v-if="estoqueAtual === 'AGUARD_ENTRADA'">Aguardando Entrada</span>
            <span v-if="estoqueAtual === 'PONTO_RETIRADA'">Ponto de Retirada</span>
        </h3>
        <br />
        <v-row class="d-flex justify-content-between text-center">
            <v-col>
                <v-btn
                    width="8.5rem"
                    :dark="estoqueAtual === 'FULFILLMENT'"
                    :loading="isLoading"
                    @click="verEstoque('FULFILLMENT')"
                >Fulfillment</v-btn>
            </v-col>
            <v-col>
                <v-btn
                    width="8.5rem"
                    :dark="estoqueAtual === 'EXTERNO'"
                    :loading="isLoading"
                    @click="verEstoque('EXTERNO')"
                >Externo</v-btn>
            </v-col>
            <v-col>
                <v-btn
                    width="8.5rem"
                    :dark="estoqueAtual === 'AGUARD_ENTRADA'"
                    :loading="isLoading"
                    @click="verEstoque('AGUARD_ENTRADA')"
                >Ag. Entrada</v-btn>
            </v-col>
            <v-col>
                <v-btn
                    width="8.5rem"
                    :dark="estoqueAtual === 'PONTO_RETIRADA'"
                    :loading="isLoading"
                    @click="verEstoque('PONTO_RETIRADA')"
                >Pt. Retirada</v-btn>
            </v-col>
        </v-row>
        <br />
        <v-window touchless v-model="estoqueAtual">
            <!-- Fulfillment -->
            <v-window-item class="align-self-center" value="FULFILLMENT">
                <v-row class="text-center">
                    <v-col>
                        <span>Estoque total: {{ quantidadeTotal.fulfillment }}</span>
                    </v-col>
                    <v-col>
                        <span>Valor total: {{ calculaValorEmReais(valorTotal.fulfillment) }}</span>
                    </v-col>
                </v-row>
                <br />
                <div
                    class="responsivo"
                    v-if="listaProdutos.fulfillment.length > 0"
                >
                    <div
                        class="m-1"
                        :key="index"
                        v-for="(produto, index) in listaProdutos.fulfillment"
                    >
                        <v-card
                            outlined
                            rounded
                            class="d-flex flex-column"
                            elevation="2"
                            height="100%"
                            max-width="25rem"
                        >
                            <v-list-item three-line>
                                <v-list-item-content style="height: fit-content;">
                                    <v-list-item-title class="text-h6 mb-1">
                                        {{ produto.descricao }}
                                    </v-list-item-title>
                                    <v-list-item-subtitle>
                                        <v-row>
                                            <v-col>
                                                <div height="5rem" width="5rem">
                                                    <v-img
                                                        height="5rem"
                                                        width="5rem"
                                                        :src="produto.produto_foto ?? 'images/img-placeholder.png'"
                                                    />
                                                </div>
                                            </v-col>
                                            <v-col class="align-self-center">Estoque total: {{ produto.total_produto }} </v-col>
                                            <v-col class="align-self-center">Valor total: {{ calculaValorEmReais(produto.valor_total_produto) }} </v-col>
                                        </v-row>
                                    </v-list-item-subtitle>
                                </v-list-item-content>
                            </v-list-item>
                            <div class="linha-grades">
                                <div class="grade" v-for="(grade) in produto.grades">
                                    <span class="bg-dark text-white">{{ grade.nome_tamanho }}</span>
                                    <span class="border border-1 border-dark">{{ grade.quantidade }}</span>
                                </div>
                            </div>
                        </v-card>
                    </div>
                </div>
                <div class="text-center" v-else>
                    <b>{{ isLoading ? 'Aguarde...' : 'Nenhum Produto encontrado nessa localização' }}</b>
                </div>
                <template v-if="maisItens.fulfillment">
                    <br />
                    <v-row class="d-flex justify-content-center">
                        <v-btn
                            dark
                            class="ver-mais"
                            height="3rem"
                            width="15rem"
                            :loading="isLoading"
                            @click="proximaPag('FULFILLMENT')"
                        >
                            <span>Clique para Ver Mais</span>
                            <v-icon>mdi-arrow-down</v-icon>
                        </v-btn>
                    </v-row>
                    <br />
                </template>
            </v-window-item>

            <!-- Externo -->
            <v-window-item class="align-self-center" value="EXTERNO">
                <v-row class="text-center">
                    <v-col>
                        <span>Estoque total: {{ quantidadeTotal.externo }}</span>
                    </v-col>
                    <v-col>
                        <span>Valor total: {{ calculaValorEmReais(valorTotal.externo) }}</span>
                    </v-col>
                </v-row>
                <br />
                <div
                    class="responsivo mx-auto"
                    v-if="listaProdutos.externo.length > 0"
                >
                    <div
                        class="m-1"
                        :key="index"
                        v-for="(produto, index) in listaProdutos.externo"
                    >
                        <v-card
                            outlined
                            rounded
                            class="d-flex flex-column"
                            elevation="2"
                            height="100%"
                            max-width="25rem"
                        >
                            <v-list-item three-line>
                                <v-list-item-content style="height: fit-content;">
                                    <v-list-item-title class="text-h6 mb-1">
                                        {{ produto.descricao }}
                                    </v-list-item-title>
                                    <v-list-item-subtitle>
                                        <v-row>
                                            <v-col>
                                                <div height="5rem" width="5rem">
                                                    <v-img
                                                        height="5rem"
                                                        width="5rem"
                                                        :src="produto.produto_foto ?? 'images/img-placeholder.png'"
                                                    />
                                                </div>
                                            </v-col>
                                            <v-col class="align-self-center">Estoque total: {{ produto.total_produto }} </v-col>
                                            <v-col class="align-self-center">Valor total: {{ calculaValorEmReais(produto.valor_total_produto) }} </v-col>
                                        </v-row>
                                    </v-list-item-subtitle>
                                </v-list-item-content>
                            </v-list-item>
                            <div class="linha-grades">
                                <div class="grade" v-for="(grade) in produto.grades">
                                    <span class="bg-dark text-white">{{ grade.nome_tamanho }}</span>
                                    <span class="border border-1 border-dark">{{ grade.quantidade }}</span>
                                </div>
                            </div>
                        </v-card>
                    </div>
                </div>
                <div class="text-center" v-else>
                    <b>{{ isLoading ? 'Aguarde...' : 'Nenhum Produto encontrado nessa localização' }}</b>
                </div>
                <template v-if="maisItens.externo">
                    <br />
                    <v-row class="d-flex justify-content-center">
                        <v-btn
                            dark
                            class="ver-mais"
                            height="3rem"
                            width="15rem"
                            :loading="isLoading"
                            @click="proximaPag('EXTERNO')"
                        >
                            <span>Clique para Ver Mais</span>
                            <v-icon>mdi-arrow-down</v-icon>
                        </v-btn>
                    </v-row>
                    <br />
                </template>
            </v-window-item>

            <!-- Aguardando Entrada -->
            <v-window-item class="align-self-center" value="AGUARD_ENTRADA">
                <v-row class="text-center">
                    <v-col>
                        <span>Estoque total: {{ quantidadeTotal.aguardEntrada }}</span>
                    </v-col>
                    <v-col>
                        <span>Valor total: {{ calculaValorEmReais(valorTotal.aguardEntrada) }}</span>
                    </v-col>
                </v-row>
                <br />
                <div
                    class="responsivo mx-auto"
                    v-if="listaProdutos.aguardEntrada.length > 0"
                >
                    <v-row
                        class="m-1"
                        :key="index"
                        v-for="(produto, index) in listaProdutos.aguardEntrada"
                    >
                        <template v-for="(grade) in produto.grades">
                            <v-card
                                outlined
                                rounded
                                elevation="2"
                                max-width="25rem"
                            >
                                <v-card-text class="my-2">
                                    <v-row class="d-flex justify-content-around">
                                        <div class="position-relative justify-content-around">
                                                <v-img
                                                    class="rounded"
                                                    height="6rem"
                                                    width="6rem"
                                                    :src="produto.produto_foto ?? 'images/img-placeholder.png'"
                                                />
                                                <b class="id-produto">{{ produto.id_produto }}</b>
                                        </div>
                                        <div>
                                            <p class="desc-produto">{{ produto.descricao }}</p>
                                            <p class="infos-produto">Valor: {{ calculaValorEmReais(produto.valor_total_produto) }}</p>
                                            <p class="infos-produto">Data: {{ grade.data_hora }}</p>
                                        </div>
                                    </v-row>
                                </v-card-text>
                                <v-card-actions
                                    class="infos-tamanho"
                                    :style="grade.defeito ? 'background-color: var(--cor-fundo-vermelho);' : 'background-color: black;'"
                                >
                                    <p>Tamanho:
                                        <b>{{ grade.nome_tamanho }}</b>
                                    </p>
                                    <p>
                                        Quantidade:
                                        <b>{{ grade.quantidade }}</b>
                                    </p>
                                </v-card-actions>
                            </v-card>
                        </template>
                    </v-row>
                </div>
                <div class="text-center" v-else>
                    <b>{{ isLoading ? 'Aguarde...' : 'Nenhum Produto encontrado nessa localização' }}</b>
                </div>
                <template v-if="maisItens.aguardEntrada">
                    <br />
                    <v-row class="d-flex justify-content-center">
                        <v-btn
                            dark
                            class="ver-mais"
                            height="3rem"
                            width="15rem"
                            :loading="isLoading"
                            @click="proximaPag('AGUARD_ENTRADA')"
                        >
                            <span>Clique para Ver Mais</span>
                            <v-icon>mdi-arrow-down</v-icon>
                        </v-btn>
                    </v-row>
                    <br />
                </template>
            </v-window-item>

            <!-- Ponto de Retirada -->
            <v-window-item class="align-self-center" value="PONTO_RETIRADA">
                <v-row class="text-center">
                    <v-col>
                        <span>Estoque total: {{ quantidadeTotal.pontoRetirada }}</span>
                    </v-col>
                    <v-col>
                        <span>Valor total: {{ calculaValorEmReais(valorTotal.pontoRetirada) }}</span>
                    </v-col>
                </v-row>
                <br />
                <div
                    class="responsivo mx-auto"
                    v-if="listaProdutos.pontoRetirada.length > 0"
                >
                    <v-row
                        class="m-1"
                        :key="index"
                        v-for="(produto, index) in listaProdutos.pontoRetirada"
                    >
                        <template v-for="(grade) in produto.grades">
                            <v-card
                                outlined
                                rounded
                                elevation="2"
                                max-width="25rem"
                            >
                                <v-card-text class="my-2">
                                    <v-row class="d-flex justify-content-around">
                                        <div class="position-relative justify-content-around">
                                                <v-img
                                                    class="rounded"
                                                    height="6rem"
                                                    width="6rem"
                                                    :src="produto.produto_foto ?? 'images/img-placeholder.png'"
                                                />
                                                <b class="id-produto">{{ produto.id_produto }}</b>
                                        </div>
                                        <div style="height: 9rem;">
                                            <p class="desc-produto">{{ produto.descricao }}</p>
                                            <p class="desc-produto" v-if="grade.descricao_defeito !== ''">{{ grade.descricao_defeito }}</p>
                                            <p class="desc-produto" style="height: 1rem;" v-else></p>
                                            <p class="desc-produto">{{ grade?.observacao_devolucao }}</p>
                                            <p class="infos-produto">Valor: {{ calculaValorEmReais(produto.valor_total_produto) }}</p>
                                            <p class="infos-produto">Data: {{ grade.data_hora }}</p>
                                        </div>
                                    </v-row>
                                </v-card-text>
                                <v-card-actions
                                    class="infos-tamanho"
                                    :style="grade.defeito ? 'background-color: var(--cor-fundo-vermelho);' : 'background-color: black;'"
                                >
                                    <p>Tamanho:
                                        <b>{{ grade.nome_tamanho }}</b>
                                    </p>
                                    <p>
                                        Quantidade:
                                        <b>{{ grade.quantidade }}</b>
                                    </p>
                                </v-card-actions>
                            </v-card>
                        </template>
                    </v-row>
                </div>
                <div class="text-center" v-else>
                    <b>{{ isLoading ? 'Aguarde...' : 'Nenhum Produto encontrado nessa localização' }}</b>
                </div>
                <template v-if="maisItens.pontoRetirada">
                    <br />
                    <v-row class="d-flex justify-content-center">
                        <v-btn
                            dark
                            class="ver-mais"
                            height="3rem"
                            width="15rem"
                            :loading="isLoading"
                            @click="proximaPag('PONTO_RETIRADA')"
                        >
                            <span>Clique para Ver Mais</span>
                            <v-icon>mdi-arrow-down</v-icon>
                        </v-btn>
                    </v-row>
                    <br />
                </template>
            </v-window-item>
        </v-window>
    </v-main>

    <v-snackbar :color="snackbar.cor" v-model="snackbar.ativar">
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/MobileStockApi.js"></script>
<script type="module" src="js/estoque-detalhado.js<?= $versao ?>"></script>