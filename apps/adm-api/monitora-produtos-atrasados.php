<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="monitoraProdutosAtrasadoVUE">
    <template>
        <div>
            <v-row id="titulo" justify="center">
                <h2>
                    Conferidos com recebimento seller pendente
                </h2>
            </v-row>
        </div>
        <div id="produtos">
            <v-row justify="center">
                <v-btn
                    class="botoes-filtro"
                    :color="filtro.todos ? 'amber' : ''"
                    :dark="filtro.todos"
                    @click="selecionaFiltro('TODOS')"
                >Todos</v-btn>
                <v-btn
                    class="botoes-filtro"
                    :color="filtro.mobileStock ? 'var(--cor-primaria-mobile-stock)' : ''"
                    :dark="filtro.mobileStock"
                    @click="selecionaFiltro('MS')"
                >Mobile Stock</v-btn>
                <v-btn
                    class="botoes-filtro"
                    :dark="filtro.pendente"
                    @click="selecionaFiltro('PE')"
                >Pontos Desativados</v-btn>
                <v-btn
                    class="botoes-filtro"
                    :color="filtro.meuLook ? 'var(--cor-secundaria-meulook)' : ''"
                    :dark="filtro.meuLook"
                    @click="selecionaFiltro('ML')"
                >Meu Look</v-btn>
            </v-row>
            <br/>
            <v-data-table
                :custom-filter="filtroTabela"
                :custom-sort="ordenamentoCustomizado"
                :footer-props="{ itemsPerPageOptions: [5, 10, 15, 30] }"
                :headers="headerProdutos"
                :items="pegaListaItens()"
                :item-class="analisaArea"
                :loading="isLoading"
                :search="pesquisa"
            >
                <template v-slot:top>
                    <div id="barra-pesquisa">
                        <v-text-field
                            filled
                            label="Pesquisar na tabela"
                            v-model="pesquisa"
                        />
                    </div>
                </template>

                <template v-slot:item.id_entrega="{ item }">
                    <a target="_blank" :href="`detalhes-entrega.php?id=${item.id_entrega}`">
                        {{ item.id_entrega }}
                    </a>
                </template>

                <template v-slot:item.id_transacao="{ item }">
                    <a target="_blank" :href="`transacao-detalhe.php?id=${item.id_transacao}`">
                        {{ item.id_transacao }}
                    </a>
                </template>

                <template v-slot:item.foto_produto="{ item }">
                    <v-img class="mx-auto" height="4rem" width="5rem" :aspect-ratio="16/9" :src="item.foto_produto" />
                </template>

                <template v-slot:item.id_produto="{ item }">
                    <a target="_blank" :href="`fornecedores-produtos.php?id=${item.id_produto}`">{{ item.id_produto }}</a> - {{ item.nome_tamanho }}
                </template>

                <template v-slot:item.fornecedor="{ item }">
                    <span>({{ item.fornecedor.id_colaborador }}) {{ item.fornecedor.nome }}</span>
                </template>

                <template v-slot:item.transportador="{ item }">
                    <span>({{ item.transportador.id_colaborador }}) {{ item.transportador.nome }}</span>
                </template>

                <template v-slot:item.mais_detalhes="{ item }">
                    <v-btn dark @click="gerirModalMaisDetalhes(item)">
                        <v-icon>mdi-account-details</v-icon>
                    </v-btn>
                </template>

                <template v-slot:item.forcar_entrega="{ item }">
                    <v-chip
                        dark
                        color="red darken-2"
                        v-if="item.cliente.saldo < 0"
                    >SALDO NEGATIVO
                    </v-chip>
                    <v-btn
                        dark
                        color="green darken-2"
                        :loading="isLoadingEntregando"
                        v-else
                        @click="forcarEntrega(item)"
                    >
                        <v-icon>mdi-truck-fast</v-icon>
                    </v-btn>
                </template>
            </v-data-table>
        </div>
    </template>

    <!-- Modal para detalhes do cliente -->
    <v-dialog width="40rem" v-model="abreModalMaisDetalhes">
        <v-card v-if="abreModalMaisDetalhes && !!maisDetalhes">
            <v-card-text class="pt-5">
                <h5>
                    <v-icon>{{ iconesDetalhes('ENTREGA') }}</v-icon>
                    Detalhes da Entrega:
                </h5>
                <ul>
                    <li
                        :key="index"
                        v-for="(informacao, index) in maisDetalhes.entrega"
                        v-if="!!informacao"
                    >
                        <b>{{ formataIndiceDetalhes(index) }}:</b>
                        <span>{{ informacao }}</span>
                    </li>
                </ul>
                <hr>
                <h5>
                    <v-icon>{{ iconesDetalhes('CLIENTE') }}</v-icon>
                    Detalhes do cliente:
                </h5>
                <ul>
                    <li
                        :key="index"
                        v-for="(informacao, index) in maisDetalhes.cliente"
                        v-if="!!informacao && index !== 'WhatsApp'"
                    >
                        <b>{{ formataIndiceDetalhes(index) }}:</b>
                        <a
                            @click="abrirModalQrCode(maisDetalhes.cliente.WhatsApp)"
                            v-if="index === 'Telefone'"
                        >
                            {{ informacao }}
                        </a>
                        <span v-else>{{ informacao }}</span>
                    </li>
                </ul>
                <hr>
                <h5>
                    <v-icon>{{ iconesDetalhes(maisDetalhes.transportador.Titulo) }}</v-icon>
                    Detalhes do Transportador:
                </h5>
                <ul>
                    <li
                        :key="index"
                        v-for="(informacao, index) in maisDetalhes.transportador"
                        v-if="!!informacao && !['WhatsApp', 'Titulo'].includes(index)"
                    >
                        <b>{{ formataIndiceDetalhes(index) }}:</b>
                        <a
                            @click="abrirModalQrCode(maisDetalhes.transportador.WhatsApp)"
                            v-if="index === 'Telefone'"
                        >
                            {{ informacao }}
                        </a>
                        <span v-else>{{ informacao }}</span>
                    </li>
                </ul>
                <div v-if="!!maisDetalhes.ponto_coleta">
                    <hr>
                    <h5>
                        <v-icon>{{ iconesDetalhes('PONTO_COLETA') }}</v-icon>
                        Detalhes do Ponto de Coleta:
                    </h5>
                    <ul>
                        <li
                            :key="index"
                            v-for="(informacao, index) in maisDetalhes.ponto_coleta"
                            v-if="!!informacao && index !== 'WhatsApp'"
                        >
                            <b>{{ formataIndiceDetalhes(index) }}:</b>
                            <a
                                @click="abrirModalQrCode(maisDetalhes.ponto_coleta.WhatsApp)"
                                v-if="index === 'Telefone'"
                            >
                                {{ informacao }}
                            </a>
                            <span v-else>{{ informacao }}</span>
                        </li>
                    </ul>
                </div>
            </v-card-text>
            <v-card-actions class="justify-content-center">
                <v-btn text color="error" @click="gerirModalMaisDetalhes()">FECHAR</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal para QrCode -->
    <v-dialog width="unset" v-model="abreModalQrCode">
        <v-expand-x-transition v-if="codigoQR !== ''">
            <v-card class="mx-auto" height="250" width="250" v-show="abreModalQrCode">
                <v-img :src="codigoQR" />
            </v-card>
        </v-expand-x-transition>
    </v-dialog>

    <v-snackbar
        :color="snackbar.cor"
        v-model="snackbar.ativar"
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style>
    #titulo {
        margin-bottom: 0.25rem;
    }
    .botoes-filtro {
        margin: 0 2rem;
    }
    #infos-card {
        margin: 0 1rem;
    }
    #barra-pesquisa {
        margin: 0 3rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="js/tools/formataTelefone.js"></script>
<script src="js/tools/formataCep.js"></script>
<script type="module" src="js/monitora-produtos-atrasados.js<?= $versao ?>"></script>
