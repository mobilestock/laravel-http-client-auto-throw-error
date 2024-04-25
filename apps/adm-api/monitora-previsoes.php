<?php

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<v-app id="monitoraPrevisoesVUE">
    <v-main>
        <h2 class="text-center">Monitora Previsões</h2>
        <h4 class="text-center" v-if="!!diaAtual && !!horaAtual">{{ diaAtual.text }} - {{ horaAtual }}</h4>
        <div class="divisor-duplo px-2">
            <v-text-field
                solo
                label="Pesquisar o ID do produto"
                type="number"
                :disabled="carregandoPesquisa"
                :loading="carregandoPesquisa"
                v-model="pesquisaProduto"
            ></v-text-field>
            <v-text-field
                solo
                label="Pesquisar o ID colaborador de um ponto/entregador"
                type="number"
                :disabled="carregandoPesquisa"
                :loading="carregandoPesquisa"
                v-model="pesquisaTransportador"
            ></v-text-field>
        </div>
        <div>
            <div
                class="cursor-pointer d-flex justify-content-center"
                @click="() => mostrarHorariosSeparacao = !mostrarHorariosSeparacao"
            >
                <h6 class="m-0">Horários da Separação</h6>
                <v-icon v-if="mostrarHorariosSeparacao">mdi-chevron-up</v-icon>
                <v-icon v-else>mdi-chevron-down</v-icon>
            </div>
            <div class="text-center" v-if="carregandoHorarios" v-show="mostrarHorariosSeparacao">
                <span>Carregando horários...</span>
            </div>
            <div class="horarios-separacao" v-else v-show="mostrarHorariosSeparacao">
                <v-chip
                    :key="index"
                    :color="horarioEhMaiorQue(horaAtual, horario) ? 'red' : ''"
                    :outlined="horarioEhMaiorQue(horaAtual, horario)"
                    v-for="(horario, index) in horariosSeparacao"
                >
                    {{ horario }}
                </v-chip>
            </div>
        </div>
        <hr />
        <div class="text-center" v-if="cidades?.length <= 0 || carregandoPesquisa">
            <div class="spinner-border" role="status" v-if="carregandoPesquisa"></div>
            <h3 v-else>Pesquise pelo produto e ponto/entregador que deseja monitorar</h3>
        </div>
        <div  v-else>
            <div class="divisor-duplo px-2">
                <v-card>
                    <v-card-title class="h4 justify-content-center">Fulfillment</v-card-title>
                    <div class="p-2">
                        <v-data-table
                            hide-default-footer
                            key="id_raio"
                            :headers="FULFILLMENT_headers"
                            :item-class="(item) => alertaDiasIndefinidos(item) ? 'bg-warning cursor-pointer' : 'cursor-pointer'"
                            :items="cidades"
                            :items-per-page="-1"
                            @click:row="(item) => cidadeSelecionada = item"
                        >
                            <template v-slot:item.nome="{ item }">
                                <span>{{ item.nome }} - {{ item.uf }}</span>
                            </template>

                            <template v-slot:item.dias_enviar_pedido="{ item }">
                                <span v-if="!alertaDiasIndefinidos(item)">{{ diasEnviarAoPontoColeta() }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.dias_pedido_chegar="{ item }">
                                <span>{{ informacoesPontoColeta.dias_pedido_chegar }}</span>
                            </template>

                            <template
                                v-if="informacoesTransportador?.tipo_ponto === 'PM'"
                                v-slot:item.dias_entregar_cliente="{ item }"
                            >
                                <span v-if="item.dias_entregar_cliente !== null">{{ item.dias_entregar_cliente }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.previsao_minima="{ item }">
                                <span class="text-success font-weight-bold" v-if="!alertaDiasIndefinidos(item)">
                                    {{ calculaPrevisaoMinima(item, 'FULFILLMENT') }}
                                </span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.dias_margem_erro="{ item }">
                                <span v-if="item.dias_margem_erro !== null">{{ item.dias_margem_erro }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.previsao_maxima="{ item }">
                                <span class="text-danger font-weight-bold" v-if="!alertaDiasIndefinidos(item)">
                                    {{ calculaPrevisaoMaxima(item, 'FULFILLMENT') }}
                                </span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>
                        </v-data-table>
                    </div>
                    <hr />
                    <div class="text-center">
                        <template v-if="!alertaDiasIndefinidos(cidadeSelecionada)">
                            <v-icon color="success">mdi-truck</v-icon>
                            &ensp;
                            <span>{{ textoPrevisao('FULFILLMENT') }}</span>
                        </template>
                        <span class="text-danger" v-else>Não foi possível carregar a previsão de entrega</span>
                    </div>
                    <br />
                </v-card>
                <v-card>
                    <v-card-title class="h4 justify-content-center">Externo</v-card-title>
                    <div class="p-2">
                        <v-data-table
                            hide-default-footer
                            key="id_raio"
                            :headers="EXTERNO_headers"
                            :item-class="(item) => alertaDiasIndefinidos(item) ? 'bg-warning cursor-pointer' : 'cursor-pointer'"
                            :items="cidades"
                            :items-per-page="-1"
                            @click:row="(item) => cidadeSelecionada = item"
                        >
                            <template v-slot:item.nome="{ item }">
                                <span>{{ item.nome }} - {{ item.uf }}</span>
                            </template>

                            <template v-slot:item.media_envio_externo="{ item }">
                                <span>{{ informacoesProduto.EXTERNO }}</span>
                            </template>

                            <template v-slot:item.dias_enviar_pedido="{ item }">
                                <span v-if="!alertaDiasIndefinidos(item)">{{ diasEnviarAoPontoColeta() }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.dias_pedido_chegar="{ item }">
                                <span>{{ informacoesPontoColeta.dias_pedido_chegar }}</span>
                            </template>

                            <template
                                v-if="informacoesTransportador?.tipo_ponto === 'PM'"
                                v-slot:item.dias_entregar_cliente="{ item }"
                            >
                                <span v-if="item.dias_entregar_cliente !== null">{{ item.dias_entregar_cliente }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.previsao_minima="{ item }">
                                <span class="text-success font-weight-bold" v-if="!alertaDiasIndefinidos(item)">
                                    {{ calculaPrevisaoMinima(item, 'EXTERNO') }}
                                </span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.dias_margem_erro="{ item }">
                                <span v-if="item.dias_margem_erro !== null">{{ item.dias_margem_erro }}</span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>

                            <template v-slot:item.previsao_maxima="{ item }">
                                <span class="text-danger font-weight-bold" v-if="!alertaDiasIndefinidos(item)">
                                    {{ calculaPrevisaoMaxima(item, 'EXTERNO') }}
                                </span>
                                <v-icon color="error" v-else>mdi-alert-box</v-icon>
                            </template>
                        </v-data-table>
                    </div>
                    <hr />
                    <div class="text-center">
                        <template v-if="!alertaDiasIndefinidos(cidadeSelecionada)">
                            <v-icon color="success">mdi-truck</v-icon>
                            &ensp;
                            <span>{{ textoPrevisao('EXTERNO') }}</span>
                        </template>
                        <template v-else>
                            <span class="text-danger">Não foi possível carregar a previsão de entrega</span>
                        </template>
                    </div>
                    <br />
                </v-card>
            </div>
            <hr />
            <div class="p-2">
                <v-card>
                    <v-card-text class="divisor-quadruplo">
                        <div class="border-right">
                            <div class="align-center d-flex flex-column">
                                <img alt="FOTO_FORNECEDOR" :src="informacoesFornecedor.foto" />
                                <h5>Fornecedor</h5>
                            </div>
                            <ul>
                                <li>
                                    <b>Nome:</b>
                                    <span>{{ informacoesFornecedor.nome }}</span>
                                </li>
                                <li v-if="!!informacoesFornecedor.telefone">
                                    <b>Telefone:</b>
                                    <a @click="() => gerirModalQrCode(informacoesFornecedor.whatsapp)">
                                        {{ formatadorTelefone(informacoesFornecedor.telefone) }}
                                    </a>
                                </li>
                                <li>
                                    <b>Reputação:</b>
                                    <v-chip dark small :color="corPorReputacao(informacoesFornecedor.reputacao)">
                                        {{ limpaTexto(informacoesFornecedor.reputacao) }}
                                    </v-chip>
                                </li>
                            </ul>
                        </div>
                        <div class="border-left border-right">
                            <div class="align-center d-flex flex-column">
                                <img alt="FOTO_PRODUTO" :src="informacoesProduto.foto" />
                                <h5>Produto</h5>
                            </div>
                            <ul>
                                <li>
                                    <b>ID produto:</b>
                                    <a
                                        target="_blank"
                                        :href="`fornecedores-produtos.php?id=${informacoesProduto.id_produto}`"
                                    >
                                        {{ informacoesProduto.id_produto }}
                                    </a>
                                </li>
                                <li>
                                    <b>Descrição:</b>
                                    <span>{{ informacoesProduto.descricao }}</span>
                                </li>
                            </ul>
                            <div class="d-flex justify-content-around">
                                <v-chip
                                    dark
                                    small
                                    color="success"
                                    v-if="informacoesProduto.possui_estoque_fulfillment"
                                >
                                    Tem estoque fulfillment
                                </v-chip>
                                <v-chip
                                    dark
                                    small
                                    color="success"
                                    v-if="informacoesProduto.possui_estoque_externo"
                                >
                                    Tem estoque externo
                                </v-chip>
                            </div>
                        </div>
                        <div class="border-left border-right">
                            <div class="align-center d-flex flex-column">
                                <img alt="FOTO_TRANSPORTADOR" :src="informacoesTransportador.foto" />
                                <h5>{{ conversorTipoPonto(informacoesTransportador.tipo_ponto) }}</h5>
                            </div>
                            <ul>
                                <li>
                                    <b>Nome Colaborador:</b>
                                    <span>{{ informacoesTransportador.nome }}</span>
                                </li>
                                <li v-if="!!informacoesTransportador.telefone">
                                    <b>Telefone:</b>
                                    <a @click="() => gerirModalQrCode(informacoesTransportador.whatsapp)">
                                        {{ formatadorTelefone(informacoesTransportador.telefone) }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="border-left">
                            <div class="align-center d-flex flex-column">
                                <img alt="FOTO_PONTO_COLETA" :src="informacoesPontoColeta.foto" />
                                <h5>Ponto de Coleta</h5>
                            </div>
                            <ul>
                                <li>
                                    <b>Nome Colaborador:</b>
                                    <span>{{ informacoesPontoColeta.nome }}</span>
                                </li>
                                <li v-if="!!informacoesPontoColeta.telefone">
                                    <b>Telefone:</b>
                                    <a @click="() => gerirModalQrCode(informacoesPontoColeta.whatsapp)">
                                        {{ formatadorTelefone(informacoesPontoColeta.telefone) }}
                                    </a>
                                </li>
                            </ul>
                            <template v-if="this.informacoesPontoColeta?.horarios.length">
                                <div class="cursor-pointer d-flex ml-2">
                                    <h6 class="m-0" @click="() => mostrarAgendaPontoColeta = !mostrarAgendaPontoColeta">
                                        Agenda para buscar produtos
                                    </h6>
                                    <v-icon v-if="mostrarAgendaPontoColeta">mdi-chevron-up</v-icon>
                                    <v-icon v-else>mdi-chevron-down</v-icon>
                                </div>
                                <ul v-show="mostrarAgendaPontoColeta">
                                    <li
                                        :key="index"
                                        v-for="(item, index) in informacoesPontoColeta.horarios"
                                    >{{ item.dia }} - {{ item.horario }} - {{ item.frequencia }}</li>
                                </ul>
                            </template>
                            <div class="d-flex justify-content-around" v-else>
                                <v-chip dark color="error">Não possui horário agendado</v-chip>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </div>
        </div>
    </v-main>

    <!-- Modal para QrCode -->
    <v-dialog width="unset" v-model="abirModalQrCode">
        <v-card class="mx-auto" height="250" width="250" v-if="abirModalQrCode">
            <v-img :src="qrCode" />
        </v-card>
    </v-dialog>

    <!-- Snackbar alertas -->
    <v-snackbar
        :color="snackbar.cor"
        v-model="snackbar.ativar"
        v-cloak
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .horarios-separacao {
        display: flex;
        flex-direction: row;
        justify-content: center;
        gap: 0.5rem;
    }
    .divisor-duplo {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-gap: 0.5rem;
    }
    .divisor-quadruplo {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }
    img {
        height: 7rem;
        width: 7rem;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/monitora-previsoes.js<?= $versao ?>"></script>
<script src="js/tools/formataTelefone.js"></script>
