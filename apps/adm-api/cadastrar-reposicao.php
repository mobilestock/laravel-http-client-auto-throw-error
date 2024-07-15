<?php
require_once __DIR__ . '/cabecalho.php';

acessoUsuarioFornecedor();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="cadastrar-reposicao">
    <template>
        <div id="cabecalho">
            <v-card
                flat
                dark
                class="infos-card"
                color="var(--cor-primaria-mobile-stock)"
            >
                <v-row>
                    <v-col>
                        <v-card-title>
                            <h1 style="font-weight: bold">Cadastrar Reposição</h1>
                        </v-card-title>
                        <v-card-subtitle>
                            <h3 v-if="editando">Visualizar/Editar a Reposição: {{ idReposicao }}</h3>
                            <h3 v-else>Iniciar nova Reposição</h3>
                        </v-card-subtitle>
                        <v-card-subtitle>
                            <h3 v-if="editando">{{ dataFormatada }}</h3>
                            <h3 v-else>{{ dataHoje }}</h3>
                        </v-card-subtitle>
                    </v-col>
                    <v-col
                        cols="12"
                        sm="6"
                        class="d-flex align-items-center justify-content-end"
                    >
                        <v-autocomplete
                            class="mt-4"
                            cache-items
                            dense
                            hide-no-data
                            outlined
                            item-text="nome"
                            item-value="id"
                            label="Nome do fornecedor"
                            style="max-width: 31.25rem;"
                            :disabled="carrinhoRepor.length > 0"
                            :items="listaFornecedores"
                            :loading="isLoadingFornecedor"
                            :search-input.sync="buscaFornecedor"
                            v-model="filtros.idFornecedor"
                            v-show="!editando"
                        />
                    </v-col>
                </v-row>
                <v-card-actions class="d-flex justify-content-between w-100">
                    <v-btn color="red" @click="voltar()">Voltar</v-btn>
                    <v-spacer></v-spacer>
                    <v-btn
                        :disabled="!editando"
                        class="ma-2"
                        color="indigo"
                        @click="buscaEtiquetasUnitarias()"
                        v-show="editando"
                    >Baixar Etiquetas Unitárias</v-btn>
                </v-card-actions>
            </v-card>
        </div>
        <br />
        <div id="body">
            <v-row>
                <v-col
                    cols="12"
                    sm="5"
                    :class="$vuetify.breakpoint.smAndUp ? 'pr-2' : ''"
                >
                    <!-- Lista de produtos -->
                    <v-card :disabled="editando">
                        <v-toolbar dark :color="editando ? '' : 'primary'">
                            <v-toolbar-title>Lista de produtos</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-text-field
                                hide-details
                                single-line
                                append-icon="search"
                                placeholder="Filtrar"
                                style="max-width: 18.75rem;"
                                :disabled="filtros.idFornecedor <= 0 || editando"
                                v-model="filtrosProdutosDisponiveis.pesquisa"
                            ></v-text-field>
                        </v-toolbar>

                        <v-data-table
                            hide-default-footer
                            class="elevation-1"
                            no-results-text="Nenhum dado encontrado"
                            :headers="headersProdutosDisponiveis"
                            :items="disponiveisRepor"
                            :items-per-page="-1"
                            :loading="isLoading || isLoadingFinaliza"
                            :no-data-text="editando ? 'Pedido de reposição já fechado' : 'Nenhum produto localizado'"
                            :custom-filter="filtroPesquisaCarrinho"
                            :search="filtrosProdutosDisponiveis.pesquisa"
                            :sort-by="['id']"
                            :sort-desc="[true]"
                        >
                            <template v-slot:item.id="{ item }">
                                <a :href="`fornecedores-produtos.php?id=${item.id}`" target="_blank" >
                                    {{ item.id }}
                                </a>
                            </template>

                            <template v-slot:item.foto="{ item }">
                                <v-img
                                    height="4rem"
                                    width="5rem"
                                    :aspect-ratio="16/9"
                                    :src="item.foto ?? 'images/img-placeholder.png'"
                                />
                            </template>

                            <template v-slot:item.adicionar_carrinho="{ item }">
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <v-btn
                                            small
                                            color="primary"
                                            :dark="!isLoadingFinaliza"
                                            :disabled="isLoadingFinaliza"
                                            :id="`adicionar-${item.id}`"
                                            v-bind="attrs"
                                            v-on="on"
                                            @click="adicionarProduto(item)"
                                        >
                                            <v-icon>mdi-cart</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Adicionar o produto ao carrinho de reposição.</span>
                                </v-tooltip>
                            </template>

                            <template v-slot:item.grades="{ item }">
                            <p class="nome-produto">{{ item.nome_comercial }}</p>
                                <table class="table text-center">
                                    <tbody>
                                        <tr class="d-flex justify-content-center flex-wrap">
                                        <td
                                            v-for="grade in item.grades"
                                            :key="grade.nome_tamanho"
                                            class="d-flex flex-column table-bordered p-1"
                                        >
                                            <b>{{ grade.nome_tamanho }}</b>
                                            <v-tooltip top>
                                                <template v-slot:activator="{ on, attrs }">
                                                    <span
                                                        :class="grade.total < 0 ? 'text-red' : (grade.total > 0 ? 'text-blue' : '')"
                                                        :disabled="filtros.situacao !== 'EM_ABERTO'"
                                                        v-bind="attrs"
                                                        v-on="on"
                                                    >{{ grade.total }}</span>
                                                </template>
                                                <span>
                                                    Produtos negativos significam que existem clientes aguardando este produto.
                                                </span>
                                            </v-tooltip>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>

                            <template v-slot:footer>
                                <hr>
                                <div class="align-items-center d-flex justify-content-around pb-2 pt-1">
                                    <v-btn
                                        fab
                                        small
                                        :dark="!isLoading && filtrosProdutosDisponiveis.pagina > 1"
                                        :disabled="isLoading || filtrosProdutosDisponiveis.pagina === 1"
                                        :loading="isLoading"
                                        @click="() => filtrosProdutosDisponiveis.pagina--"
                                    >
                                        <v-icon>mdi-chevron-left</v-icon>
                                    </v-btn>
                                    <v-chip>{{ filtrosProdutosDisponiveis.pagina }}</v-chip>
                                    <v-btn
                                        fab
                                        small
                                        :dark="!isLoading && filtrosProdutosDisponiveis.maisPags"
                                        :disabled="isLoading || !filtrosProdutosDisponiveis.maisPags"
                                        :loading="isLoading"
                                        @click="() => filtrosProdutosDisponiveis.pagina++"
                                    >
                                        <v-icon>mdi-chevron-right</v-icon>
                                    </v-btn>
                                </div>
                            </template>
                        </v-data-table>
                    </v-card>
                </v-col>

                <v-col
                    cols="12"
                    sm="7"
                    :class="$vuetify.breakpoint.smAndUp ? 'pl-2' : 'mt-4'"
                >

                <!-- Carrinho de reposição -->
                    <v-card>
                        <v-toolbar dark color="primary">
                            <v-toolbar-title>
                                <span>Produtos da Reposição</span>
                                <span>({{ carrinhoRepor?.length || 0 }})</span>
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-text-field
                                hide-details
                                single-line
                                append-icon="search"
                                placeholder="Filtrar"
                                style="max-width: 18.75rem;"
                                v-model="filtroCarrinho"
                            ></v-text-field>
                        </v-toolbar>
                        <v-card-title>
                            <div class="d-flex">
                                <v-icon color="warning">mdi-cart</v-icon>
                                <v-subheader>Total: {{ totalValorReposicao }}</v-subheader>
                            </div>
                            <v-spacer></v-spacer>
                            <v-btn
                                class="text-white"
                                color="success"
                                :disabled="(qtdProdutosCarrinho === carrinhoRepor?.length) || carrinhoRepor.length === 0 || filtros.situacao === 'ENTREGUE' || (editando && !atualizavel)"
                                :loading="isLoadingFinaliza"
                                @click="editando ? atualizarReposicao() : modalCriarReposicao = true"
                            >{{ editando ? (filtros.situacao === 'ENTREGUE' ? 'Entregue' : 'Atualizar Reposição') : 'Criar Reposição' }}</v-btn>
                            </v-card-title>
                            <v-data-table
                                class="elevation-1"
                                no-data-text="Nenhum produto agendado para reposição"
                                no-results-text="Nenhum dado encontrado"
                                :headers="headersProdutosCarrinho"
                                :items="carrinhoRepor"
                                :loading="isLoading || isLoadingFinaliza"
                                :search="filtroCarrinho"
                            >
                                <template v-slot:item.foto="{ item }">
                                    <v-img
                                        height="4rem"
                                        width="5rem"
                                        :aspect-ratio="16/9"
                                        :src="item.foto ?? 'images/img-placeholder.png'"
                                    />
                                </template>

                                <template v-slot:item.grades="{ item }">
                                    <table class="table text-center">
                                        <tbody>
                                            <tr class="d-flex justify-content-center flex-wrap">
                                                <td
                                                    v-for="grade in item.grades"
                                                    :key="grade.nome_tamanho"
                                                    class="d-flex flex-column table-bordered p-1"
                                                >
                                                    <b>{{ grade.nomeTamanho }}</b>
                                                    <span
                                                        :class="grade.novoEstoque < 0 ? 'text-red' : (grade.novoEstoque > 0 ? 'text-blue' : '')"
                                                        :disabled="filtros.situacao !== 'EM_ABERTO'"
                                                    >{{ parseInt(grade.novoEstoque * item.caixas) || 0 }}</span>
                                                    <span
                                                        v-show="editando"
                                                        class="text-green"
                                                        :disabled="filtros.situacao !== 'EM_ABERTO'"
                                                    >{{ parseInt(grade.faltaEntregar) }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </template>

                                <template v-slot:item.editar="{ item }">
                                    <v-btn
                                        icon
                                        color="warning"
                                        :disabled="isLoadingFinaliza || filtros.situacao === 'ENTREGUE' || item.situacao === 'Já entregue'"
                                        @click="adicionarProduto(item, true)"
                                    >
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>
                                </template>

                                <template v-slot:item.excluir="{ item }">
                                    <v-btn
                                        icon
                                        color="error"
                                        :disabled="isLoadingFinaliza || editando"
                                        @click="removerDoCarrinho(item.id_produto)"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </template>

                            </v-data-table>
                        </v-card-title>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </template>

    <!-- Modal selecionar tamanhos que serão respostos -->
    <v-dialog
        max-height="36rem"
        max-width="75rem"
        transition="dialog-bottom-transition"
        v-if="modalReposicao"
        v-model="modalReposicao"
        persistent
    >
        <v-card>
            <v-toolbar dark color="primary" class="mb-2">
                <v-toolbar-title>Detalhes do produto: {{ produtoEscolhido.idProduto }}</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn dark icon @click="limpaModal()" :disabled="editando">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>
            <v-card-text>
                <v-card outlined>
                    <v-simple-table fixed-header>
                        <template v-slot:default>
                            <thead>
                                <tr>
                                    <th class="text-center">Foto produto</th>
                                    <th class="text-center">Descrição</th>
                                    <th class="text-center">Grade</th>
                                    <th class="text-center">Valor Unitário</th>
                                    <th class="text-center">Quantidade Total</th>
                                    <th class="text-center">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th class="text-center">
                                        <v-img
                                            height="6.25rem"
                                            width="6.25rem"
                                            :aspect-ratio="16/9"
                                            :src="produtoEscolhido.fotoProduto ?? 'images/img-placeholder.png'"
                                            @click="!!produtoEscolhido.fotoProduto ? modalFotos = true : ''"
                                        />
                                    </th>
                                    <th class="text-center">{{ produtoEscolhido.nomeComercial }}</th>
                                    <th class="text-center">
                                        <table class="table my-1">
                                            <tbody>
                                                <tr class="d-flex justify-content-center">
                                                    <td style="width: 6.25rem;" class="d-flex flex-column table-bordered">
                                                        <small class="mb-1">
                                                            <b style="font-size: 0.9rem;">Tamanho</b>
                                                        </small>
                                                        <hr>
                                                        <small class="py-1" style="font-size: 0.7rem;">Em estoque</small>
                                                        <hr>
                                                        <small class="py-1" style="font-size: 0.7rem;">Dessa reposição</small>
                                                        <hr>
                                                        <small class="py-1" style="font-size: 0.7rem;" v-show="editando">Falta entregar</small>
                                                    </td>
                                                    <td
                                                        class="d-flex flex-column table-bordered justify-content-between"
                                                        :key="grade.key"
                                                        v-for="(grade, index) in inputGrade.novaGrade"
                                                    >
                                                        <b class="mb-1">{{ grade.nomeTamanho }}</b>
                                                        <span
                                                            class="py-1"
                                                            :class="grade.total < 0 ? 'text-red' : ''"
                                                        >
                                                            {{ grade.emEstoque }}
                                                        </span>
                                                        <span
                                                            class="py-1"
                                                            :class="(grade.novoEstoque * inputGrade.caixas) < 0 ? 'text-red' : ''"
                                                        >
                                                            {{ parseInt(grade.novoEstoque * inputGrade.caixas )}}
                                                        </span>
                                                        <span
                                                            class="py-1"
                                                            :class="corNovoSaldo(grade.total, grade.novoEstoque, inputGrade.caixas)"
                                                        >
                                                            {{ grade.faltaEntregar }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </th>
                                    <th class="text-center">{{ calculaValorEmReais(produtoEscolhido.valorUnitario) }}</th>
                                    <th class="text-center">{{ quantidadeEstoqueTotal || 0 }}</th>
                                    <th class="text-center">{{ calculaValorEmReais(valorTotal || 0) }}</th>
                                </tr>
                            </tbody>
                        </template>
                    </v-simple-table>
                </v-card>
                <br />
                <v-card outlined>
                    <v-banner
                        single-line
                        dark
                        :color="editando ? 'green' : 'blue'"
                        class="text-center"
                    >
                        {{editando ? 'Edite a quantidade de produtos que ainda faltam entregar a central' : 'Informe os produtos que serão adicionados ao estoque' }}
                    </v-banner>
                    <v-card-title>Grade
                        <v-spacer></v-spacer>
                        <v-text-field
                            dense
                            outlined
                            :disabled="editando"
                            class="px-2"
                            label="Caixas"
                            style="max-width: 9.5rem;"
                            type="number"
                            :rules="[rules.valorMin(inputGrade.caixas || 0, 1, 'caixas')]"
                            v-model="inputGrade.caixas"
                            value="1"
                        ></v-text-field>
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col
                                class="d-flex flex-column text-center px-2"
                                :key="index"
                                v-for="(grade, index) in inputGrade.novaGrade"
                            >
                                <span class="subtitle-1 mb-1">{{ grade.nomeTamanho }}</span>
                                <v-text-field
                                    v-show="!editando"
                                    dense
                                    outlined
                                    color="blue"
                                    label="Quantidade"
                                    type="number"
                                    :rules="[rules.valorMin(grade.novoEstoque || 0, 0, `${grade.nomeTamanho}`)]"
                                    v-model="inputGrade.novaGrade[index].novoEstoque"
                                ></v-text-field>
                                <v-text-field
                                    v-show="editando"
                                    dense
                                    type="number"
                                    outlined
                                    color="green"
                                    label="Remover"
                                    type="number"
                                    :rules="[rules.valorMinEMax(grade.faltaEntregar || 0, 0, grade.faltaEntregar, `${grade.nomeTamanho}`)]"
                                    @input="() => calculaFaltaEntregar(inputGrade.novaGrade[index])"
                                    v-model="inputGrade.novaGrade[index].quantidadeRemover"
                                    :disabled="!grade.editavel"
                                    @change="verificaSeAtualizavel()"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    :disabled="inputGrade.caixas < 1"
                    @click="editando ? fecharModalEditarReposicao(inputGrade.novaGrade) : adicionarAoCarrinho()"
                >
                    <v-icon>{{ editando ?  'mdi-pencil' : 'mdi-plus' }}</v-icon>
                    {{ editando ? 'Finalizar edição' : 'Adicionar ao carrinho' }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal confirmar reposição -->
    <v-dialog width="30rem" height="12.5rem" v-model="modalCriarReposicao">
        <v-card width="30rem" height="12.5rem">
            <v-card-title>
                <b>
                    <v-icon>mdi-alert</v-icon> Alerta
                </b>
            </v-card-title>
            <v-card-text class="mb-0">
                <p style="font-size: 1rem; color: black;">
                    Após criar a reposição não será mais possível excluir a reposição por inteiro!
                    <b>Deseja realmente concluir?</b>
                </p>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    small
                    elevation="0"
                    @click="modalCriarReposicao = false"
                >
                    <b>Não</b>
                </v-btn>
                <v-btn
                    small
                    elevation="0"
                    @click="criarReposicao()"
                >
                    <b>Sim</b>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal de snackbar -->
    <v-snackbar
        :color="snackbar.cor"
        v-model="snackbar.ativar"
        v-cloak
    >
        {{ snackbar.texto }}
    </v-snackbar>

</v-app>
<style>
    #cabecalho {
        margin: 0 0.7rem;
    }
    #body {
        margin: 0 0.7rem;
    }
    .infos-card {
        padding: 1rem;
    }
    .nome-produto {
        font-size: 0.8rem;
    }
</style>

<script src="js/FileSaver.min.js<?= $versao ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/tools/formataMoeda.js"></script>
<script type="module" src="js/cadastrar-reposicao.js<?= $versao ?>"></script>
