<?php

use MobileStock\helper\Globals;

require_once __DIR__ . '/cabecalho.php';

acessoUsuarioAdministrador();
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="//unpkg.com/leaflet/dist/leaflet.css" rel="stylesheet"/>
    <script src="//unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="//unpkg.com/vue2-leaflet"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta charset="UTF-8">
</head>

<style>
    .divisao-dupla {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .container-seletores {
        display: grid;
        gap: 0.5rem;
        grid-template-columns: 1fr 1fr 1fr;
    }

    .flex-preco-radio {
        display: flex;
    }

    .tarifa-entrega {
        display: flex;
        margin-top: 0.4rem;
        align-items: center;
    }

    .medida-tarifa-entrega {
        font-size: 1.15rem;
    }

    .container-adicionar-cidade {
        display: flex;
        margin: 2rem;
        gap: 2rem;
    }

    .imagem-documentos {
        width: 100%;
    }

    .mapa-raio {
        width: 100%;
        height: 44rem;
    }

    .painel-mapa-raio {
        position: absolute;
        display: flex;
        top: 0;
        left: 0;
        z-index: 500;
        width: 4rem;
        margin-top: 1rem;
        padding: 1rem;
        gap: 1rem;
        flex-direction: column;
    }

    .painel-mapa-raio > .v-btn {
        width: 4rem !important;
        height: 4rem !important;
        border-radius: 50%;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.12), 0 2px 2px 0 rgba(0, 0, 0, 0.24);
    }

    .valores-gerais {
        gap: 1rem;
    }

    .fonte {
        font-size: 1.05rem;
    }

    .id-entregas {
        max-width: 18.75rem;
        font-weight: bold;
    }

    .leaflet-div-icon {
        background: white;
        border-radius: 50%;
        padding: 0.3rem !important;
        filter: invert(1);
    }

    .leaflet-div-dragable {
        cursor: grab;
        filter: drop-shadow(2px 4px 6px var(--cor-fundo-preto));
    }

    .marker-ativo {
        filter: drop-shadow(2px 4px 6px var(--cor-fundo-vermelho));
    }
</style>

<v-app id="gerenciarPontosVUE">
    <v-main>
        <v-overlay v-model="carregando">
            <v-progress-circular indeterminate></v-progress-circular>
        </v-overlay>
        <div>
            <h2 class="text-center">Gerenciar Pontos de Retirada</h2>
        </div>
        <div>
            <v-card>
                <v-tabs-slider></v-tabs-slider>
                <v-tabs
                    centered
                    dark
                    fixed-tabs
                    icons-and-text
                    v-model="areaAtual.id"
                >
                    <v-tab
                        :key="index"
                        v-cloak
                        v-for="(area, index) in areasDisponiveis"
                    >
                        <span>{{ formataNomeArea(area.nome) }}</span>
                        <v-icon>{{ area.icone }}</v-icon>
                    </v-tab>
                </v-tabs>
                <v-tabs-items v-model="areasDisponiveis">
                    <div v-show="areaAtual.nome === 'PONTO_RETIRADA'">
                        <br />
                        <v-data-table
                            :custom-filter="pesquisarNaTabela"
                            :footer-props="{'items-per-page-options': [30, 50, 100, 200, -1]}"
                            :headers="PONTO_RETIRADA_headers"
                            :items="PONTO_RETIRADA_lista"
                            :items-per-page="30"
                            :loading="carregando"
                            :search="PONTO_RETIRADA_pesquisa"
                        >
                            <template v-slot:top>
                                <div class="mx-2">
                                    <v-text-field
                                        dense
                                        outlined
                                        append-icon="mdi-magnify"
                                        label="Pesquisar"
                                        :loading="carregando"
                                        v-model="PONTO_RETIRADA_pesquisa"
                                    ></v-text-field>
                                </div>
                            </template>

                            <template v-slot:item.razao_social="{ item }">
                                <span>
                                    ({{ item.id_colaborador }}) {{ item.razao_social }}
                                </span>
                            </template>

                            <template v-slot:item.cidade="{ item }">
                                <span>
                                    {{ item.cidade }} - {{ item.uf }}
                                </span>
                            </template>

                            <template v-slot:item.telefone="{ item }">
                                <v-btn
                                    icon
                                    color="var(--cor-padrao-whatsapp)"
                                    :disabled="carregando"
                                    @click="gerirModalContato(item)"
                                >
                                    <v-icon>mdi-whatsapp</v-icon>
                                </v-btn>
                            </template>

                            <template v-slot:item.eh_ponto_coleta="{ item }">
                                <v-btn
                                    block
                                    :color="item.eh_ponto_coleta ? 'var(--cor-secundaria-meulook)' : 'light-green darken-2'"
                                    :dark="!carregando"
                                    :disabled="carregando"
                                    :loading="carregando"
                                    @click="gerirPontoColeta(item)"
                                >
                                    <span v-if="item.eh_ponto_coleta">
                                        <v-icon>mdi-close-circle</v-icon>&ensp;Desativar
                                    </span>
                                    <span v-else>
                                        <v-icon>mdi-check-circle</v-icon>&ensp;Ativar
                                    </span>
                                </v-btn>
                            </template>

                            <template v-slot:item.acoes="{ item }">
                                <v-btn
                                    icon
                                    :color="item.dias_margem_erro === null ? 'warning' : ''"
                                    :disabled="carregando"
                                    @click="PONTO_RETIRADA_gerirDados(item)"
                                >
                                    <v-icon>mdi-menu</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </div>
                    <div v-show="areaAtual.nome === 'ENTREGADORES'">
                        <br />
                        <v-data-table
                            key="id_colaborador"
                            :custom-filter="pesquisarNaTabela"
                            :footer-props="{'items-per-page-options': [30, 50, 100, 200, -1]}"
                            :headers="ENTREGADORES_headers"
                            :items="ENTREGADORES_lista"
                            :items-per-page="30"
                            :loading="carregando"
                            :search="ENTREGADORES_pesquisa"
                        >
                            <template v-slot:top>
                                <div class="d-flex mx-2">
                                    <v-text-field
                                        dense
                                        outlined
                                        append-icon="mdi-magnify"
                                        class="mr-1"
                                        label="Pesquisar"
                                        :loading="carregando"
                                        v-model="ENTREGADORES_pesquisa"
                                    ></v-text-field>
                                    <v-btn
                                        class="ml-1 text-decoration-none"
                                        href="tabela-precos-cidades.php"
                                        :dark="!carregando"
                                        :disabled="carregando"
                                    >Preços Adicionais Cidades</v-btn>
                                </div>
                            </template>

                            <template v-slot:item.razao_social="{ item }">
                                <span>
                                    ({{ item.id_colaborador }}) {{ item.razao_social }}
                                </span>
                            </template>

                            <template v-slot:item.cidades="{ item }">
                                <v-btn
                                    class="ma-2"
                                    :color="ENTREGADORES_alertaDadosFaltantes(item) ? 'warning' : ''"
                                    :dark="!carregando"
                                    :disabled="carregando"
                                    :loading="carregando"
                                    @click="ENTREGADORES_gerirModalCidades(item)"
                                >Listar</v-btn>
                            </template>

                            <template v-slot:item.foto_documento_habilitacao="{ item }">
                                <v-btn
                                    icon
                                    :disabled="carregando"
                                    @click="ENTREGADORES_buscaDocumentos(item)"
                                >
                                    <v-icon>mdi-image-search</v-icon>
                                </v-btn>
                            </template>

                            <template v-slot:item.telefone="{ item }">
                                <v-btn
                                    icon
                                    color="var(--cor-padrao-whatsapp)"
                                    :disabled="carregando"
                                    @click="gerirModalContato(item)"
                                >
                                    <v-icon>mdi-whatsapp</v-icon>
                                </v-btn>
                            </template>

                            <template v-slot:item.ponto_coleta="{ item }">
                                <a
                                    v-if="!item.eh_ponto_coleta"
                                    @click="ENTREGADORES_gerirModalConfigPontoColeta(item)"
                                >{{ item.ponto_coleta }}</a>
                                <span v-else>{{ item.ponto_coleta }}</span>
                            </template>

                            <template v-slot:item.categoria="{ item }">
                                <v-btn
                                    block
                                    :color="item.categoria === 'PE' ? 'success' : 'error'"
                                    :dark="!carregando"
                                    :disabled="carregando"
                                    :loading="carregando"
                                    @click="ENTREGADORES_mudarSituacao(item)"
                                >
                                    <span v-if="item.categoria === 'PE'">Aprovar</span>
                                    <span v-else>Pausar</span>
                                </v-btn>
                            </template>

                            <template v-slot:item.eh_ponto_coleta="{ item }">
                                <v-btn
                                    block
                                    :color="item.eh_ponto_coleta ? 'var(--cor-secundaria-meulook)' : 'light-green darken-2'"
                                    :dark="!carregando && item.categoria === 'ML'"
                                    :disabled="carregando || item.categoria === 'PE'"
                                    :loading="carregando"
                                    @click="gerirPontoColeta(item)"
                                >
                                    <span v-if="item.eh_ponto_coleta">
                                        <v-icon>mdi-close-circle</v-icon>&ensp;Desativar
                                    </span>
                                    <span v-else>
                                        <v-icon>mdi-check-circle</v-icon>&ensp;Ativar
                                    </span>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </div>
                    <div v-show="areaAtual.nome === 'PONTOS_COLETA'">
                        <br />
                        <v-data-table
                            key="id_colaborador"
                            :custom-filter="pesquisarNaTabela"
                            :footer-props="{'items-per-page-options': [30, 50, 100, 200, -1]}"
                            :headers="PONTOS_COLETA_headers"
                            :items="PONTOS_COLETA_lista"
                            :items-per-page="30"
                            :loading="carregando"
                            :search="PONTOS_COLETA_pesquisa"
                            :item-class="item => !item.tem_grupo ? 'bg-danger' : ''"
                        >
                            <template v-slot:top>
                                <div class="mx-2">
                                    <v-text-field
                                        dense
                                        outlined
                                        append-icon="mdi-magnify"
                                        label="Pesquisar"
                                        :loading="carregando"
                                        v-model="PONTOS_COLETA_pesquisa"
                                    ></v-text-field>
                                </div>
                            </template>

                            <template v-slot:item.tipo_ponto="{ item }">
                                <v-icon>{{ PONTOS_COLETA_iconeTipoPonto(item) }}</v-icon>
                            </template>

                            <template v-slot:item.razao_social="{ item }">
                                <span>
                                    ({{ item.id_colaborador }}) {{ item.razao_social }}
                                </span>
                            </template>

                            <template v-slot:item.cidade="{ item }">
                                <span>
                                    {{ item.cidade }} - {{ item.uf }}
                                </span>
                            </template>

                            <template v-slot:item.valor_custo_frete="{ item }">
                                <span >
                                {{ converteValorEmReais(item.valor_custo_frete) }}
                                </span>
                            </template>

                            <template v-slot:item.porcentagem_frete="{ item }">
                                <v-tooltip top>
                                    <template v-slot:activator="{ on, attrs }">
                                        <span
                                            class="font-weight-bold"
                                            v-bind="attrs"
                                            v-on="on"
                                        >
                                            <p>{{ item.porcentagem_frete }}%</p>
                                        </span>
                                    </template>
                                    <span v-if="item.entregas.length">Entregas: {{ item.entregas }}</span>
                                    <span v-else>Esse ponto de coleta não possui entregas suficiente para calcular corretamente.</span>
                                </v-tooltip>
                            </template>

                            <template v-slot:item.telefone="{ item }">
                                <v-btn
                                    icon
                                    color="var(--cor-padrao-whatsapp)"
                                    :disabled="carregando"
                                    @click="gerirModalContato(item)"
                                >
                                    <v-icon>mdi-whatsapp</v-icon>
                                </v-btn>
                            </template>

                            <template v-slot:item.tarifa_envio="{ item }">
                                <v-btn
                                    :dark="!carregando"
                                    :disabled="carregando"
                                    :loading="carregando"
                                    @click="PONTOS_COLETA_detalhes(item)"
                                >
                                    <v-icon>mdi-percent-outline</v-icon>
                                </v-btn>
                            </template>

                            <template v-slot:item.prazos="{ item }">
                                <v-btn
                                    :color="!item.possui_horario ? 'warning' : ''"
                                    :dark="!carregando"
                                    :loading="carregando"
                                    :disabled="carregando"
                                    @click="PONTOS_COLETA_buscaAgenda(item)"
                                >
                                    <v-icon>mdi-book</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </div>
                </v-tabs-items>
            </v-card>
        </div>
    </v-main>

    <!-- Modal central para mensagem WhatsApp -->
    <v-dialog
        width="38rem"
        v-cloak
        v-model="modalContato.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h5 v-cloak>Contato Ponto</h5>
            </v-card-title>
            <v-card-text>
                <div class="text-center">
                    <v-img
                        class="mx-auto"
                        height="16rem"
                        width="16rem"
                        :alt="modalContato.link"
                        :src="'<?= Globals::geraQRCODE('') ?>' + modalContato.qrCode"
                    ></v-img>
                    <a target="_blank" :href="modalContato.link">
                        Enviar mensagem para o ponto
                    </a>
                </div>
            </v-card-text>
        </v-card>
    </v-dialog>

    <!-- Modal alterar dados do PONTO_RETIRADA -->
    <v-dialog
        persistent
        width="40rem"
        v-model="PONTO_RETIRADA_alterarDados.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Alterar Dados Ponto Parado ({{ PONTO_RETIRADA_alterarDados.idPonto }})</h6>
            </v-card-title>
            <v-card-text>
                <v-select
                    filled
                    label="Situação"
                    :items="PONTO_RETIRADA_situacoes"
                    v-model="PONTO_RETIRADA_alterarDados.categoria"
                ></v-select>
                <v-text-field
                    filled
                    label="Nome do ponto"
                    placeholder="Ex.: Ponto do Juca"
                    v-model="PONTO_RETIRADA_alterarDados.nome"
                ></v-text-field>
                <div class="align-items-center flex-preco-radio justify-content-around m-0">
                    <div>
                        <v-text-field
                            filled
                            label="Preço do ponto"
                            placeholder="3.00"
                            type="number"
                            width="100%"
                            v-model="PONTO_RETIRADA_alterarDados.preco"
                        ></v-text-field>
                    </div>
                    <div>
                        <v-text-field
                            filled
                            label="Prazo para Forçar Entrega"
                            step="1"
                            type="number"
                            width="100%"
                            v-model="PONTO_RETIRADA_alterarDados.prazoForcarEntrega"
                        ></v-text-field>
                        <v-text-field
                            filled
                            label="Margem de Erro"
                            step="1"
                            type="number"
                            width="100%"
                            v-model="PONTO_RETIRADA_alterarDados.dias_margem_erro"
                        ></v-text-field>
                    </div>
                    <div>
                        <v-radio-group
                            column
                            label="Emitir NF-e?"
                            v-model="PONTO_RETIRADA_alterarDados.emitirNota"
                        >
                            <v-radio
                                label="Sim"
                                value="1"
                            ></v-radio>
                            <v-radio
                                label="Não"
                                value="0"
                            ></v-radio>
                        </v-radio-group>
                    </div>
                </div>
                <v-text-field
                    filled
                    label="E-mail do Ponto"
                    type="email"
                    v-model="PONTO_RETIRADA_alterarDados.email"
                ></v-text-field>
                <v-text-field
                    filled
                    label="Número de telefone"
                    maxlength="15"
                    placeholder="Ex.: (99) 99999-9999"
                    v-model="PONTO_RETIRADA_alterarDados.telefone"
                ></v-text-field>
                <v-text-field
                    filled
                    label="Horário de funcionamento"
                    placeholder="Ex.: Segunda à Sexta das 08:00 às 18:00"
                    v-model="PONTO_RETIRADA_alterarDados.horarioFuncionamento"
                ></v-text-field>
                <v-text-field
                    filled
                    label="Informações"
                    placeholder="Ex: Rua X, nº Y, Bairro Z"
                    v-model="PONTO_RETIRADA_alterarDados.informacoes"
                ></v-text-field>
            </v-card-text>
            <v-card-actions class="flex justify-content-around">
                <div>
                    <v-btn
                        text
                        color="error"
                        :disabled="carregando"
                        v-cloak
                        @click="PONTO_RETIRADA_gerirDados()"
                    >Cancelar</v-btn>
                </div>
                <div>
                    <v-btn
                        color="primary"
                        :disabled="carregando || !PONTO_RETIRADA_houveAlteracoesDados"
                        v-cloak
                        @click="PONTO_RETIRADA_salvarAlteracoes()"
                    >Salvar</v-btn>
                </div>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal configuração cidades ENTREGADORES -->
    <v-dialog
        persistent
        width="auto"
        v-model="ENTREGADORES_configCidades.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Alterar Cidades Entregador ({{ ENTREGADORES_configCidades.idPonto }})</h6>
            </v-card-title>
            <v-card-text>
                <v-data-table
                    disable-pagination
                    hide-default-footer
                    :headers="ENTREGADORES_headersCidades"
                    :items="ENTREGADORES_configCidades.cidades"
                    :loading="carregando"
                >
                    <template v-slot:item.apelido="{ item }">
                        <v-text-field
                            solo
                            type="text"
                            :placeholder="item?.apelido || 'Sem Apelido'"
                            v-model="item.apelido"
                            @input="ENTREGADORES_debounceSalvaNovaConfigCidade(item)"
                        ></v-text-field>
                    </template>

                    <template v-slot:item.id_cidade="{ item }">
                        <v-btn
                            text
                            color="primary"
                            :disabled="carregando || item.raio === null"
                            @click="ENTREGADORES_buscaCoberturaCidade(item)"
                        >Editar Raio</v-btn>
                    </template>

                    <template v-slot:item.valor="{ item }">
                        <div class="tarifa-entrega">
                            <v-text-field
                                solo
                                placeholder="0.00"
                                type="number"
                                v-model="item.valor"
                                @input="ENTREGADORES_debounceSalvaNovaConfigCidade(item)"
                            >
                                <template v-slot:prepend-inner>
                                    <span class="mr-1 medida-tarifa-entrega">R$</span>
                                </template>
                            </v-text-field>
                        </div>
                    </template>

                    <template v-slot:item.dias_entregar_cliente="{ item }">
                        <v-text-field
                            solo
                            type="number"
                            v-model="item.dias_entregar_cliente"
                            @input="ENTREGADORES_debounceSalvaNovaConfigCidade(item)"
                        ></v-text-field>
                    </template>

                    <template v-slot:item.dias_margem_erro="{ item }">
                        <v-text-field
                            solo
                            type="number"
                            v-model="item.dias_margem_erro"
                            @input="ENTREGADORES_debounceSalvaNovaConfigCidade(item)"
                        ></v-text-field>
                    </template>

                    <template v-slot:item.prazo_forcar_entrega="{ item }">
                        <v-text-field
                            solo
                            type="number"
                            v-model="item.prazo_forcar_entrega"
                            @input="ENTREGADORES_debounceSalvaNovaConfigCidade(item)"
                        ></v-text-field>
                    </template>

                    <template v-slot:item.esta_ativo="{ item }">
                        <div class="d-flex justify-content-center">
                            <v-switch
                                v-model="item.esta_ativo"
                                @change="ENTREGADORES_gerirModalConfirma(item, true, item.esta_ativo)"
                            ></v-switch>
                        </div>
                    </template>
                </v-data-table>
            </v-card-text>
            <v-card-actions class="d-flex justify-content-around">
                <v-btn
                    color="primary"
                    v-cloak
                    @click="ENTREGADORES_gerirModalAdicionarCidade(true)"
                >Adicionar Cidade</v-btn>
                <v-btn
                    text
                    color="error"
                    v-cloak
                    @click="ENTREGADORES_gerirModalCidades()"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal adicionar cidade para ENTREGADORES -->
    <v-dialog
        persistent
        width="40rem"
        v-model="ENTREGADORES_novaCidade.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Adicionar Cidade</h6>
            </v-card-title>
            <v-card-text>
                <div class="d-flex container-adicionar-cidade">
                    <v-autocomplete
                        class="mt-1"
                        label="Cidade"
                        no-data-text="Nenhuma cidade encontrada"
                        prepend-inner-icon="mdi-map-marker"
                        :disabled="carregando"
                        :items="ENTREGADORES_listaCidades"
                        :loading="carregando"
                        :search-input.sync="pesquisaCidade"
                        v-model="ENTREGADORES_novaCidade.idCidade"
                    ></v-autocomplete>
                    <div class="w-25 tarifa-entrega">
                        <v-text-field
                            dense
                            solo
                            placeholder="0.00"
                            type="number"
                            :disabled="!ENTREGADORES_novaCidade.idCidade || carregando"
                            :loading="carregando"
                            v-model="ENTREGADORES_novaCidade.tarifaCidade"
                        >
                            <template v-slot:prepend-inner>
                                <span class="medida-tarifa-entrega">R$</span>
                            </template>
                        </v-text-field>
                    </div>
                </div>
            </v-card-text>
            <v-card-actions class="d-flex justify-content-around">
                <v-btn
                    text
                    color="primary"
                    :disabled="!ENTREGADORES_novaCidade.idCidade || carregando"
                    :loading="carregando"
                    v-cloak
                    @click="ENTREGADORES_adicionarCidade()"
                >Adicionar</v-btn>
                <v-btn
                    text
                    color="error"
                    :disabled="carregando"
                    v-cloak
                    @click="ENTREGADORES_gerirModalAdicionarCidade(false)"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Confirmação alterar situação cidade ENTREGADORES -->
    <v-dialog
        persistent
        width="25em"
        v-model="ENTREGADORES_modalConfirmacao.mostrar"
    >
        <v-card>
            <v-container class="px-4">
                <p v-cloak>Tem certeza de que deseja alterar a situação das entregas para esta cidade?</p>
                <v-card-actions class="d-flex justify-content-around">
                    <v-btn
                        text
                        color="primary"
                        :disabled="carregando"
                        :loading="carregando"
                        v-cloak
                        @click="ENTREGADORES_alterarSituacaoCidade()"
                    >Confirmar</v-btn>
                    <v-btn
                        text
                        color="error"
                        :disabled="carregando"
                        :loading="carregando"
                        v-cloak
                        @click="ENTREGADORES_gerirModalConfirma(ENTREGADORES_modalConfirmacao.cidade, false)"
                    >Cancelar</v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>

    <!-- Modal de documentos ENTREGADORES -->
    <v-dialog
        persistent
        width="50rem"
        v-model="ENTREGADORES_documentos.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Documentos</h6>
            </v-card-title>
            <v-card-text>
                <v-img
                    contain
                    class="imagem-documentos"
                    max-height="20rem"
                    :src="ENTREGADORES_documentos.foto_documento_habilitacao"
                ></v-img>
                <br />
                <v-img
                    contain
                    class="imagem-documentos"
                    max-height="20rem"
                    :src="ENTREGADORES_documentos.foto_documento_veiculo"
                ></v-img>
            </v-card-text>
            <v-card-actions class="justify-content-center">
                <v-btn
                    text
                    color="error"
                    v-cloak
                    @click="() => ENTREGADORES_documentos = { mostrar: false }"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal configurar Ponto de Coleta ENTREGADORES -->
    <v-dialog
        persistent
        width="36em"
        v-model="ENTREGADORES_configPontoColeta.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Definir do Ponto de Coleta de <b>{{ ENTREGADORES_configPontoColeta.razaoSocial }}</b></h6>
            </v-card-title>
            <v-card-text>
                <v-autocomplete
                    dense
                    label="Ponto de Coleta"
                    no-data-text="Nenhum Ponto de Coleta Encontrado"
                    :disabled="carregando"
                    :items="ENTREGADORES_listaPontosColeta"
                    :loading="carregando"
                    :search-input.sync="pesquisaPontoColeta"
                    v-model="ENTREGADORES_configPontoColeta.pontoColeta"
                ></v-autocomplete>
            </v-card-text>
            <v-card-actions class="justify-content-around">
                <v-btn
                    color="primary"
                    :disabled="carregando || !ENTREGADORES_configPontoColeta.pontoColeta"
                    :loading="carregando"
                    v-cloak
                    @click="ENTREGADORES_salvarPontoColeta()"
                >Confirmar</v-btn>
                <v-btn
                    text
                    color="error"
                    :disabled="carregando"
                    v-cloak
                    @click="ENTREGADORES_gerirModalConfigPontoColeta()"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal configura raio ENTREGADORES -->
    <v-dialog
        persistent
        max-width="90%"
        v-cloak
        v-model="ENTREGADORES_configRaio.mostrar && ENTREGADORES_configRaio.raios.length > 0"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Definir Raio de Entrega</h6>
            </v-card-title>
            <l-map
                class="mapa-raio"
                :options="ENTREGADORES_configRaio.options"
                :zoom="ENTREGADORES_configRaio.zoom"
                :center="ENTREGADORES_configRaio.center"
            >
                <div
                    class="painel-mapa-raio"
                    v-if="ENTREGADORES_configRaio.raioSelecionado"
                >
                    <v-btn
                        small
                        color="primary"
                        @click="ENTREGADORES_modificaAlcanceRaio(true)"
                    >
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>
                    <v-btn
                        small
                        color="primary"
                        @click="ENTREGADORES_modificaAlcanceRaio(false)"
                    >
                        <v-icon>mdi-minus</v-icon>
                    </v-btn>
                </div>
                <l-tile-layer :url="ENTREGADORES_configRaio.url"></l-tile-layer>
                <l-marker
                    v-for="(marker, index) in ENTREGADORES_configRaio.raios"
                    :key="index"
                    :lat-lng="[marker.latitude, marker.longitude]"
                    :draggable="marker?.eh_colaborador_atual"
                    @update:lat-lng="(event) => ENTREGADORES_modificaMarcador(event, marker)"
                >
                    <l-tooltip
                        :content="marker.nome_tipo_frete + ` - ${marker.raio} metros`"
                    ></l-tooltip>
                    <l-icon
                        :icon-size="[44,44]"
                        :icon-anchor="dynamicAnchor"
                        :icon-url="ENTREGADOR_retornaVisualRaio(marker, true)"
                        :class-name="marker.eh_colaborador_atual ?
                            `leaflet-div-dragable ${marker.id_raio === ENTREGADORES_configRaio.raioSelecionado
                                ? 'marker-ativo' : ''}` : 'leaflet-div-icon'"
                    />
                </l-marker>
                <l-polygon
                    color="green"
                    v-if="ENTREGADORES_configRaio.limites !== null"
                    :lat-lngs="ENTREGADORES_configRaio.limites"
                ></l-polygon>
                <l-circle
                    v-for="(marker, index) in ENTREGADORES_configRaio.raios"
                    :color="ENTREGADOR_retornaVisualRaio(marker)"
                    :fill-color="ENTREGADOR_retornaVisualRaio(marker)"
                    :key="index"
                    :lat-lng="[marker.latitude, marker.longitude]"
                    :radius="marker.raio"
                ></l-circle>
            </l-map>
            <v-card-actions class="justify-content-end">
                <v-btn
                    color="primary"
                    :disabled="carregando || !ENTREGADORES_houveAlteracoesRaio()"
                    :loading="carregando"
                    @click="ENTREGADORES_editarDadosRaios()"
                >Salvar</v-btn>
                <v-btn
                    text
                    color="error"
                    :disabled="carregando"
                    @click="ENTREGADORES_buscaCoberturaCidade()"
                >Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal configuração tarifa PONTOS_COLETA -->
    <v-dialog
        persistent
        width="64rem"
        v-model="PONTOS_COLETA_configValores.mostrar"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Definir Tarifa envio para o Ponto de Coleta ({{ PONTOS_COLETA_configValores.id }})</h6>
            </v-card-title>
            <v-card-text>
                <div class="d-flex justify-content-around">
                    <div
                        class="d-flex flex-column text-center"
                        :key="index"
                        v-for="(entrega, index) in PONTOS_COLETA_configValores.entregas"
                    >
                        <span
                            class="fonte font-weight-bold"
                            v-cloak
                        >Entregas: </span>
                        <span
                            class="id-entregas"
                            v-cloak
                        > {{ entrega.ids_entrega }} </span>
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <span
                                    class="fonte"
                                    v-bind="attrs"
                                    v-on="on"
                                >{{ entrega.data_expedicao }}</span>
                            </template>
                            <span v-cloak>Data de expedição da entrega.</span>
                        </v-tooltip>
                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <span
                                    class="fonte font-weight-bold"
                                    v-cloak
                                    v-bind="attrs"
                                    v-on="on"
                                >{{ converteValorEmReais(entrega.valor_custo_produto) }}</span>
                            </template>
                            <span v-cloak>Valor dos produtos da entrega.</span>
                        </v-tooltip>
                    </div>
                </div>
                <br />
                <div class="align-items-center d-flex flex-column mt-4 valores-gerais">
                    <span class="fonte font-weight-bold" v-cloak>Média: {{ converteValorEmReais(PONTOS_COLETA_configValores.media_valor_entregas) }}</span>
                    <span class="d-flex font-weight-bold fonte tarifa-entrega">
                        <span class="mr-1" v-cloak>
                            Custo Frete:
                        </span>
                        <v-text-field
                            dense
                            solo
                            placeholder="0.00"
                            type="number"
                            step="0.01"
                            v-model="PONTOS_COLETA_configValores.valor_custo_frete"
                        >
                            <template v-slot:prepend-inner>
                                <span class="medida-tarifa-entrega">R$</span>
                            </template>
                        </v-text-field>
                    </span>
                    <div class="align-items-center d-flex flex-column">
                        <span class="fonte font-weight-bold">
                            <v-tooltip top>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-icon
                                        v-bind="attrs"
                                        v-on="on"
                                        @click="PONTOS_COLETA_gerirModalFormulaCalculo(true)"
                                    >mdi-information</v-icon>
                                </template>
                                <span v-cloak>Clique para entender como esse valor é calculado.</span>
                            </v-tooltip>
                            <span v-cloak>
                                Percentual Frete: {{ PONTOS_COLETA_configValores.porcentagem_frete }}%
                            </span>
                        </span>
                        <v-switch
                            dense
                            class="ml-2"
                            label="Evento pode recalcular"
                            :disabled="carregando"
                            :loading="carregando"
                            v-model="PONTOS_COLETA_configValores.deve_recalcular"
                        ></v-switch>
                    </div>
                </div>
            </v-card-text>
            <v-card-actions class="justify-content-around">
                <v-btn
                    color="primary"
                    :disabled="carregando || !PONTOS_COLETA_houveAlteracoesTarifa"
                    :loading="carregando"
                    v-cloak
                    @click="PONTOS_COLETA_atualizarTarifaPontoColeta()"
                >Atualizar</v-btn>
                <v-btn
                    :dark="!carregando"
                    :disabled="carregando"
                    :loading="carregando"
                    v-cloak
                    @click="PONTOS_COLETA_atualizarTarifaPontoColeta(
                        PONTOS_COLETA_configValores.porcentagem_frete > 0
                            ? 0
                            : 10
                    )"
                >
                    Travar em {{ PONTOS_COLETA_configValores.porcentagem_frete > 0 ? 0 : 10 }}%
                </v-btn>
                <v-btn
                    text
                    color="error"
                    :disabled="carregando"
                    v-cloak
                    @click="PONTOS_COLETA_configValores = { mostrar: false }"
                >Cancelar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal sumário fórmula PONTOS_COLETA -->
    <v-dialog
        pesistent
        width="64rem"
        v-model="PONTOS_COLETA_modalFormulaTarifa"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h6 v-cloak>Fórmula para cálculo da tarifa de envio.</h6>
            </v-card-title>
            <v-card-text>
                <ul class="fonte">
                    <li class="mt-1" v-cloak>
                        Para enviar encomendas para um local específico, é preciso cobrar uma taxa
                        para cobrir os custos do transporte.
                    </li>
                    <li class="mt-1" v-cloak>
                        Para saber quanto cobrar, é necessário descobrir o valor médio dos últimos
                        3 dias que tiveram entregas para esse local, somando o valor dos produtos e dividindo pelo
                        número de entregas.
                    </li>
                    <li class="mt-1" v-cloak>
                        Por exemplo, se o valor dos últimos 3 dias que tiveram entregas foram de R$ 100, R$ 200 e R$ 300, o
                        valor médio seria R$ 200.
                    </li>
                    <li class="mt-1" v-cloak>
                        Em seguida, é preciso verificar a tabela de custos das transportadoras
                        para essa região e dividir esse valor pela média dos últimos 3 dias que tiveram entregas.
                    </li>
                    <li class="mt-1" v-cloak>
                        O resultado disso é a porcentagem que deve ser cobrada dos clientes que escolherem essa determinada opção de envio para cobrir os
                        custos de transporte.
                    </li>
                </ul>
            </v-card-text>
            <v-card-actions class="justify-content-center">
                <v-btn
                    text
                    color="error"
                    v-cloak
                    @click="PONTOS_COLETA_gerirModalFormulaCalculo(false)"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal agenda separação PONTOS_COLETA -->
    <v-dialog
        persistent
        width="72rem"
        v-if="PONTOS_COLETA_modalAgendaHorarios && !!PONTOS_COLETA_configurarAgenda"
        v-model="PONTOS_COLETA_modalAgendaHorarios"
    >
        <v-card>
            <v-card-title class="justify-content-center">
                <h5 v-cloak>
                    Configuração de Prazos do Ponto Coleta
                    ({{ PONTOS_COLETA_configurarAgenda?.id }} - {{ PONTOS_COLETA_configurarAgenda?.nome }})
                </h5>
            </v-card-title>
            <v-card-text>
                <div class="d-flex flex-column align-items-center">
                    <span v-cloak>Dias para chegar ao destino</span>
                    <v-text-field
                        dense
                        solo
                        type="number"
                        width="20rem"
                        :disabled="carregando"
                        :loading="carregando"
                        v-model="PONTOS_COLETA_configurarAgenda.dias_pedido_chegar"
                        @input="PONTOS_COLETA_debounceSalvaNovoPrazo"
                    ></v-text-field>
                </div>
                <hr />
                <div>
                    <h6 class="font-weight-bold text-center" v-cloak>Agenda</h6>
                    <br />
                    <div class="divisao-dupla">
                        <div class="border-right pr-4">
                            <v-data-table
                                hide-default-footer
                                :headers="PONTOS_COLETA_headersHorarios"
                                :items="PONTOS_COLETA_configurarAgenda.horarios"
                                :items-per-page="-1"
                            >
                                <template v-slot:item.dia="{ item }">
                                    <span v-cloak>{{ PONTOS_COLETA_conversorDia(item).text }}</span>
                                </template>

                                <template v-slot:item.frequencia="{ item }">
                                    <span v-cloak>{{ PONTOS_COLETA_conversorFrequencia(item).text }}</span>
                                </template>

                                <template v-slot:item.remove="{ item }">
                                    <v-btn
                                        icon
                                        :disabled="carregando"
                                        :loading="carregando"
                                        @click="PONTOS_COLETA_removeHorarioAgenda(item)"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </template>
                            </v-data-table>
                        </div>
                        <div class="border-left text-center">
                            <div class="container-seletores pl-4">
                                <v-select
                                    solo
                                    label="Horários Disponíveis"
                                    :disabled="carregando"
                                    :items="PONTOS_COLETA_seletores.horarios"
                                    :loading="carregando"
                                    v-model="PONTOS_COLETA_novoHorario.horario"
                                ></v-select>
                                <v-select
                                    solo
                                    label="Dia da Semana"
                                    :disabled="carregando"
                                    :items="PONTOS_COLETA_seletores.dias"
                                    :loading="carregando"
                                    v-model="PONTOS_COLETA_novoHorario.dia"
                                ></v-select>
                                <v-select
                                    solo
                                    label="Frequência"
                                    :disabled="carregando"
                                    :items="PONTOS_COLETA_seletores.frequencias"
                                    :loading="carregando"
                                    v-model="PONTOS_COLETA_novoHorario.frequencia"
                                ></v-select>
                            </div>
                            <br />
                            <v-btn
                                :dark="!PONTOS_COLETA_desabilitarAdicionarHorario"
                                :disabled="PONTOS_COLETA_desabilitarAdicionarHorario"
                                :loading="carregando"
                                @click="PONTOS_COLETA_adicionaHorarioAgenda"
                                v-cloak
                            >Adicionar Novo Horário</v-btn>
                        </div>
                    </div>
                </div>
            </v-card-text>
            <hr />
            <v-card-actions class="justify-content-around">
                <v-btn
                    text
                    color="error"
                    :disabled="carregando"
                    v-cloak
                    @click="PONTOS_COLETA_gerirModalConfigsAgenda()"
                >Fechar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Snackbar alertas -->
    <v-snackbar
        :color="snackbar.cor"
        v-cloak
        v-model="snackbar.mostrar"
    >
        {{ snackbar.texto }}
    </v-snackbar>
</v-app>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script type="module" src="js/gerenciar-pontos.js<?= $versao ?>"></script>
<script src="js/whatsapp.js"></script>
<script src="js/MobileStockApi.js"></script>
<script src="js/tools/formataCep.js"></script>
<script src="js/tools/removeAcentos.js"></script>
<script src="js/tools/formataTelefone.js"></script>
